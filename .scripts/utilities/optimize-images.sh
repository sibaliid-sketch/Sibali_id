#!/bin/bash

# Image Optimization Pipeline for Sibali.id
# Converts, resizes, and optimizes images for web delivery

set -e

# Configuration
SOURCE_DIR="./storage/app/public/images"
OUTPUT_DIR="./storage/app/public/optimized"
MANIFEST_FILE="./storage/app/public/optimized/manifest.json"
LOG_FILE="./storage/logs/image_optimization_$(date +"%Y%m%d").log"

# Quality presets
QUALITY_PRESETS=("lossless" "lossy" "aggressive")
SIZES=("320" "640" "1024" "1920")

# Create directories
mkdir -p "$OUTPUT_DIR"
mkdir -p "$(dirname "$LOG_FILE")"

# Logging function
log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a "$LOG_FILE"
}

# Check if image is already optimized
is_optimized() {
    local file="$1"
    local output_base="$OUTPUT_DIR/$(basename "$file" | sed 's/\.[^.]*$//')"

    # Check for metadata tag or existing optimized versions
    if [ -f "${output_base}_320.webp" ] && [ -f "${output_base}_640.webp" ]; then
        return 0
    else
        return 1
    fi
}

# Optimize single image
optimize_image() {
    local input_file="$1"
    local filename=$(basename "$input_file")
    local base_name="${filename%.*}"
    local output_base="$OUTPUT_DIR/$base_name"

    log "Processing: $filename"

    # Skip if already optimized
    if is_optimized "$input_file"; then
        log "Skipping already optimized: $filename"
        return
    fi

    # Convert to WebP and AVIF with different sizes
    for size in "${SIZES[@]}"; do
        # WebP conversion
        if command -v cwebp &> /dev/null; then
            cwebp -q 80 -resize "$size" 0 "$input_file" -o "${output_base}_${size}.webp" 2>/dev/null || log "WebP conversion failed for $size"
        fi

        # AVIF conversion (if available)
        if command -v avifenc &> /dev/null; then
            avifenc --min 20 --max 40 --speed 6 "$input_file" "${output_base}_${size}.avif" 2>/dev/null || log "AVIF conversion failed for $size"
        fi
    done

    # Quality presets
    for preset in "${QUALITY_PRESETS[@]}"; do
        case $preset in
            "lossless")
                quality=100
                ;;
            "lossy")
                quality=85
                ;;
            "aggressive")
                quality=70
                ;;
        esac

        # JPEG optimization
        if [[ "$filename" =~ \.(jpg|jpeg)$ ]]; then
            if command -v jpegoptim &> /dev/null; then
                cp "$input_file" "${output_base}_${preset}.jpg"
                jpegoptim -m"$quality" "${output_base}_${preset}.jpg" 2>/dev/null || log "JPEG optimization failed for $preset"
            fi
        fi

        # PNG optimization
        if [[ "$filename" =~ \.png$ ]]; then
            if command -v optipng &> /dev/null; then
                cp "$input_file" "${output_base}_${preset}.png"
                optipng -o7 "${output_base}_${preset}.png" 2>/dev/null || log "PNG optimization failed for $preset"
            fi
        fi
    done

    log "Completed: $filename"
}

# Generate manifest
generate_manifest() {
    log "Generating optimization manifest..."

    # Create JSON manifest of processed files
    echo "{" > "$MANIFEST_FILE"
    echo "  \"generated_at\": \"$(date -Iseconds)\"," >> "$MANIFEST_FILE"
    echo "  \"source_dir\": \"$SOURCE_DIR\"," >> "$MANIFEST_FILE"
    echo "  \"output_dir\": \"$OUTPUT_DIR\"," >> "$MANIFEST_FILE"
    echo "  \"optimized_files\": [" >> "$MANIFEST_FILE"

    first=true
    find "$OUTPUT_DIR" -type f \( -name "*.webp" -o -name "*.avif" -o -name "*_lossless.*" -o -name "*_lossy.*" -o -name "*_aggressive.*" \) | while read -r file; do
        if [ "$first" = true ]; then
            first=false
        else
            echo "," >> "$MANIFEST_FILE"
        fi

        relative_path="${file#$OUTPUT_DIR/}"
        size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null || echo "0")
        echo "    {\"path\": \"$relative_path\", \"size\": $size}" >> "$MANIFEST_FILE"
    done

    echo "  ]" >> "$MANIFEST_FILE"
    echo "}" >> "$MANIFEST_FILE"

    log "Manifest generated: $MANIFEST_FILE"
}

# Invalidate CDN cache (placeholder for CDN integration)
invalidate_cdn() {
    log "Invalidating CDN cache..."
    # TODO: Integrate with CDN API (Cloudflare, AWS CloudFront, etc.)
    # Example: curl -X POST "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/purge_cache" -H "Authorization: Bearer $TOKEN" -d '{"purge_everything":true}'
    log "CDN invalidation placeholder - implement based on your CDN provider"
}

# Main execution
log "Starting image optimization pipeline"

# Find and process images
find "$SOURCE_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" -o -iname "*.gif" \) | while read -r file; do
    optimize_image "$file"
done

generate_manifest
invalidate_cdn

log "Image optimization pipeline completed"
log "Check $LOG_FILE for details"
