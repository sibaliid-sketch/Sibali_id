#!/bin/bash

# Image Optimization Script for Sibali.id
# Purpose: Pipeline optimasi gambar: konversi, resizing, dan preset kualitas
# Function: Bulk convert to WebP/AVIF, generate responsive sizes, apply quality presets

set -e

# Configuration
SOURCE_DIR="${SOURCE_DIR:-/var/www/html/storage/app/public/images}"
OUTPUT_DIR="${OUTPUT_DIR:-/var/www/html/storage/app/public/optimized}"
LOG_FILE="/var/log/sibali/image_optimization.log"
MANIFEST_FILE="$OUTPUT_DIR/manifest.json"
QUALITY_PRESET="${QUALITY_PRESET:-80}"
MAX_WIDTH="${MAX_WIDTH:-1920}"
PRESERVE_EXIF="${PRESERVE_EXIF:-false}"

# Responsive sizes
SIZES=(320 640 960 1280 1920)

# Ensure directories exist
mkdir -p "$OUTPUT_DIR" "$(dirname "$LOG_FILE")"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Check dependencies
check_dependencies() {
    local missing_deps=()

    if ! command -v convert >/dev/null 2>&1; then
        missing_deps+=("ImageMagick (convert)")
    fi

    if ! command -v cwebp >/dev/null 2>&1; then
        missing_deps+=("WebP tools (cwebp)")
    fi

    if ! command -v avifenc >/dev/null 2>&1; then
        missing_deps+=("AVIF tools (avifenc)")
    fi

    if [ ${#missing_deps[@]} -gt 0 ]; then
        log "ERROR: Missing dependencies: ${missing_deps[*]}"
        log "Please install required packages:"
        log "  Ubuntu/Debian: apt-get install imagemagick webp libavif-bin"
        log "  CentOS/RHEL: yum install ImageMagick webp-tools libavif"
        exit 1
    fi
}

# Get image info
get_image_info() {
    local file="$1"
    local width height format

    # Use ImageMagick to get dimensions
    read -r width height format <<< "$(identify -format "%w %h %m" "$file" 2>/dev/null || echo "0 0 UNKNOWN")"

    echo "$width $height $format"
}

# Optimize single image
optimize_image() {
    local input_file="$1"
    local filename=$(basename "$input_file")
    local name="${filename%.*}"
    local output_base="$OUTPUT_DIR/$name"

    log "Processing: $filename"

    # Get original dimensions
    read -r orig_width orig_height format <<< "$(get_image_info "$input_file")"

    if [ "$orig_width" = "0" ] || [ "$orig_height" = "0" ]; then
        log "WARNING: Could not read image dimensions for $filename"
        return 1
    fi

    local optimized_files=()

    # Generate responsive sizes
    for size in "${SIZES[@]}"; do
        if [ "$orig_width" -le "$size" ]; then
            # Original is smaller, skip upscaling
            continue
        fi

        local output_file="$output_base-${size}.webp"
        local temp_file="$output_base-${size}.tmp"

        # Resize and convert to WebP
        if convert "$input_file" \
                   -resize "${size}x${size}>" \
                   -quality "$QUALITY_PRESET" \
                   -strip \
                   "$temp_file" 2>/dev/null; then

            cwebp -q "$QUALITY_PRESET" "$temp_file" -o "$output_file" 2>/dev/null
            rm -f "$temp_file"

            if [ -f "$output_file" ]; then
                optimized_files+=("$output_file")
                log "  Generated: ${size}px WebP"
            fi
        fi

        # Generate AVIF version if requested
        if [ "${GENERATE_AVIF:-true}" = "true" ]; then
            local avif_file="$output_base-${size}.avif"
            if avifenc --min 20 --max 40 "$temp_file" "$avif_file" 2>/dev/null; then
                optimized_files+=("$avif_file")
                log "  Generated: ${size}px AVIF"
            fi
        fi
    done

    # Generate original size WebP
    local orig_webp="$output_base.webp"
    if cwebp -q "$QUALITY_PRESET" "$input_file" -o "$orig_webp" 2>/dev/null; then
        optimized_files+=("$orig_webp")
        log "  Generated: Original WebP"
    fi

    # Generate original size AVIF
    if [ "${GENERATE_AVIF:-true}" = "true" ]; then
        local orig_avif="$output_base.avif"
        if avifenc --min 20 --max 40 "$input_file" "$orig_avif" 2>/dev/null; then
            optimized_files+=("$orig_avif")
            log "  Generated: Original AVIF"
        fi
    fi

    # Calculate savings
    local orig_size=$(stat -f%z "$input_file" 2>/dev/null || stat -c%s "$input_file")
    local total_optimized_size=0

    for opt_file in "${optimized_files[@]}"; do
        if [ -f "$opt_file" ]; then
            local opt_size=$(stat -f%z "$opt_file" 2>/dev/null || stat -c%s "$opt_file")
            total_optimized_size=$((total_optimized_size + opt_size))
        fi
    done

    local savings_percent="0"
    if [ "$orig_size" -gt 0 ]; then
        savings_percent=$(echo "scale=2; (1 - $total_optimized_size / $orig_size) * 100" | bc -l 2>/dev/null || echo "0")
    fi

    # Return optimization info
    echo "$filename $orig_size $total_optimized_size $savings_percent ${#optimized_files[@]}"
}

# Update manifest
update_manifest() {
    local manifest_entries=()

    # Read existing manifest if it exists
    if [ -f "$MANIFEST_FILE" ]; then
        manifest_entries=$(jq -r '.images[] | @json' "$MANIFEST_FILE" 2>/dev/null || echo "")
    fi

    # Add new entries
    find "$OUTPUT_DIR" -name "*.webp" -o -name "*.avif" | while read -r file; do
        local rel_path="${file#$OUTPUT_DIR/}"
        local orig_file="$SOURCE_DIR/$(basename "$file" | sed 's/-[0-9]*\.\(webp\|avif\)$//' | sed 's/\.\(webp\|avif\)$//').*"

        # Find original file
        orig_file=$(find "$SOURCE_DIR" -name "$(basename "$orig_file")" | head -1)

        if [ -f "$orig_file" ]; then
            local orig_size=$(stat -f%z "$orig_file" 2>/dev/null || stat -c%s "$orig_file")
            local opt_size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file")
            local savings=$(echo "scale=2; (1 - $opt_size / $orig_size) * 100" | bc -l 2>/dev/null || echo "0")

            manifest_entries+=$(cat << EOF
{
  "original": "$(basename "$orig_file")",
  "optimized": "$rel_path",
  "original_size": $orig_size,
  "optimized_size": $opt_size,
  "savings_percent": $savings,
  "optimized_at": "$(date)"
}
EOF
)
        fi
    done

    # Write new manifest
    cat > "$MANIFEST_FILE" << EOF
{
  "generated_at": "$(date)",
  "quality_preset": $QUALITY_PRESET,
  "images": [
$(echo "$manifest_entries" | paste -sd ",")
  ]
}
EOF
}

# Main execution
log "Starting image optimization pipeline"

check_dependencies

# Find images to process
find "$SOURCE_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" -o -iname "*.gif" \) | while read -r image_file; do
    optimize_image "$image_file"
done

update_manifest

log "Image optimization completed. Manifest: $MANIFEST_FILE"

# Optional: Invalidate CDN cache
if [ -n "${CDN_INVALIDATION_URL:-}" ]; then
    log "Invalidating CDN cache..."
    curl -X POST "$CDN_INVALIDATION_URL" \
         -H "Content-Type: application/json" \
         -d "{\"paths\":[\"/images/*\"]}" \
         >/dev/null 2>&1 || true
fi

exit 0
