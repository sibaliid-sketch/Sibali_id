#!/bin/bash

# Sitemap Generator Script for Sibali.id
# Purpose: Generator sitemap otomatis untuk SEO
# Function: Crawl routes, generate sitemap.xml, ping search engines

set -e

# Configuration
APP_URL="${APP_URL:-https://www.sibali.id}"
OUTPUT_DIR="${OUTPUT_DIR:-/var/www/html/public}"
SITEMAP_FILE="$OUTPUT_DIR/sitemap.xml"
LOG_FILE="/var/log/sibali/sitemap_generator.log"
LOCALES="${LOCALES:-en id}"
PING_GOOGLE="${PING_GOOGLE:-true}"
PING_BING="${PING_BING:-true}"
INCREMENTAL="${INCREMENTAL:-true}"

# Priority and changefreq mappings
declare -A PRIORITIES=(
    ["/"]=1.0
    ["/about"]=0.8
    ["/services"]=0.9
    ["/contact"]=0.7
    ["/blog"]=0.8
    ["/courses"]=0.9
)

declare -A CHANGE_FREQS=(
    ["/"]=daily
    ["/about"]=monthly
    ["/services"]=weekly
    ["/contact"]=monthly
    ["/blog"]=daily
    ["/courses"]=weekly
)

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Get routes from Laravel
get_laravel_routes() {
    cd /var/www/html

    # Get routes from artisan route:list
    php artisan route:list --columns=uri,method --no-headers | \
    grep -E "GET|HEAD" | \
    awk '{print $2}' | \
    grep -v -E "^api/|^_debugbar|^horizon|^telescope" | \
    sort | uniq
}

# Get dynamic content (blog posts, courses, etc.)
get_dynamic_content() {
    cd /var/www/html

    # Get blog posts
    php artisan tinker --execute="
        App\Models\Content::where('status', 'published')
            ->where('type', 'blog')
            ->pluck('slug')
            ->map(function(\$slug) { return '/blog/' . \$slug; })
            ->toArray()
    " 2>/dev/null | grep -E "^/" || true

    # Get courses
    php artisan tinker --execute="
        App\Models\Academic\Class::where('status', 'active')
            ->pluck('id')
            ->map(function(\$id) { return '/courses/' . \$id; })
            ->toArray()
    " 2>/dev/null | grep -E "^/" || true
}

# Generate sitemap entry
generate_sitemap_entry() {
    local url="$1"
    local priority="${PRIORITIES[$url]:-0.5}"
    local changefreq="${CHANGE_FREQS[$url]:-monthly}"
    local lastmod=$(date +%Y-%m-%d)

    cat << EOF
  <url>
    <loc>$APP_URL$url</loc>
    <lastmod>$lastmod</lastmod>
    <changefreq>$changefreq</changefreq>
    <priority>$priority</priority>
  </url>
EOF
}

# Generate multi-language sitemaps
generate_multilang_sitemap() {
    local locale="$1"
    local sitemap_content=""

    log "Generating sitemap for locale: $locale"

    # Static routes
    while read -r route; do
        if [ "$locale" = "en" ]; then
            sitemap_content+=$(generate_sitemap_entry "$route")
        else
            sitemap_content+=$(generate_sitemap_entry "/$locale$route")
        fi
    done < <(get_laravel_routes)

    # Dynamic content
    while read -r dynamic_url; do
        if [ "$locale" = "en" ]; then
            sitemap_content+=$(generate_sitemap_entry "$dynamic_url")
        else
            sitemap_content+=$(generate_sitemap_entry "/$locale$dynamic_url")
        fi
    done < <(get_dynamic_content)

    # Generate sitemap file
    local sitemap_file="$OUTPUT_DIR/sitemap-$locale.xml"

    cat > "$sitemap_file" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

$sitemap_content
</urlset>
EOF

    log "Generated sitemap: $sitemap_file ($(echo "$sitemap_content" | wc -l) URLs)"
}

# Generate sitemap index
generate_sitemap_index() {
    local index_content=""

    for locale in $LOCALES; do
        local sitemap_file="sitemap-$locale.xml"
        local lastmod=$(date +%Y-%m-%d)

        index_content+=$(cat << EOF
  <sitemap>
    <loc>$APP_URL/$sitemap_file</loc>
    <lastmod>$lastmod</lastmod>
  </sitemap>
EOF
)
    done

    cat > "$SITEMAP_FILE" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
$index_content
</sitemapindex>
EOF

    log "Generated sitemap index: $SITEMAP_FILE"
}

# Ping search engines
ping_search_engines() {
    local sitemap_url="$APP_URL/sitemap.xml"

    if [ "$PING_GOOGLE" = "true" ]; then
        log "Pinging Google..."
        curl -s "https://www.google.com/ping?sitemap=$sitemap_url" >/dev/null 2>&1 || \
        log "WARNING: Failed to ping Google"
    fi

    if [ "$PING_BING" = "true" ]; then
        log "Pinging Bing..."
        curl -s "https://www.bing.com/ping?sitemap=$sitemap_url" >/dev/null 2>&1 || \
        log "WARNING: Failed to ping Bing"
    fi
}

# Check if incremental update is needed
needs_update() {
    if [ "$INCREMENTAL" != "true" ]; then
        return 0
    fi

    # Check if content has changed recently
    local last_update_file="$OUTPUT_DIR/.sitemap_last_update"

    if [ ! -f "$last_update_file" ]; then
        return 0
    fi

    local last_update=$(cat "$last_update_file")
    local now=$(date +%s)
    local hours_since=$(( (now - last_update) / 3600 ))

    # Update if more than 24 hours have passed
    if [ $hours_since -gt 24 ]; then
        return 0
    fi

    # Check if new content exists
    cd /var/www/html
    local new_posts=$(php artisan tinker --execute="
        App\Models\Content::where('created_at', '>', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->where('status', 'published')
            ->count()
    " 2>/dev/null | grep -o '[0-9]\+' || echo "0")

    if [ "$new_posts" -gt 0 ]; then
        return 0
    fi

    return 1
}

# Main execution
log "Starting sitemap generation"

if needs_update; then
    log "Sitemap update needed"

    # Generate sitemaps for each locale
    for locale in $LOCALES; do
        generate_multilang_sitemap "$locale"
    done

    # Generate sitemap index
    generate_sitemap_index

    # Ping search engines
    ping_search_engines

    # Update timestamp
    echo $(date +%s) > "$OUTPUT_DIR/.sitemap_last_update"

    log "Sitemap generation completed"
else
    log "Sitemap is up to date, skipping generation"
fi

exit 0
