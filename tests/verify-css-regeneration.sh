#!/bin/bash
#
# CSS Regeneration Verification Script
#
# This script verifies that the regenerate-css endpoint actually regenerates
# CSS cache for Oxygen/Breakdance pages.
#
# Requirements:
# - WordPress Application Password for authentication
# - A page/post with Oxygen/Breakdance content
# - Access to WordPress database (for meta verification)
# - wp-cli installed (optional, for database checks)
#
# Usage:
#   WORDPRESS_URL=http://pngna.local \
#   WORDPRESS_USERNAME=admin \
#   WORDPRESS_APP_PASSWORD=your_app_password \
#   POST_ID=123 \
#   bash verify-css-regeneration.sh
#

set -e

# Configuration from environment
WORDPRESS_URL="${WORDPRESS_URL:-http://localhost}"
WORDPRESS_USERNAME="${WORDPRESS_USERNAME:-admin}"
WORDPRESS_APP_PASSWORD="${WORDPRESS_APP_PASSWORD}"
POST_ID="${POST_ID}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=============================================="
echo "CSS Regeneration Verification"
echo "=============================================="
echo ""

# Validate required parameters
if [ -z "$POST_ID" ]; then
    echo -e "${RED}ERROR: POST_ID environment variable is required${NC}"
    echo ""
    echo "Usage:"
    echo "  POST_ID=123 bash verify-css-regeneration.sh"
    echo ""
    exit 1
fi

if [ -z "$WORDPRESS_APP_PASSWORD" ]; then
    echo -e "${YELLOW}WARNING: WORDPRESS_APP_PASSWORD not set - authentication may fail${NC}"
    echo ""
fi

echo "Configuration:"
echo "  WordPress URL: $WORDPRESS_URL"
echo "  Username: $WORDPRESS_USERNAME"
echo "  Post ID: $POST_ID"
echo ""

# Function to get post meta (requires wp-cli)
get_css_cache_meta() {
    local meta_key="$1"
    if command -v wp &> /dev/null; then
        # Try with --allow-root for root environments
        wp post meta get "$POST_ID" "$meta_key" --format=json 2>/dev/null || \
        wp post meta get "$POST_ID" "$meta_key" --format=json --allow-root 2>/dev/null || \
        echo "null"
    else
        echo "wp-cli not available"
    fi
}

# Function to extract version hash from cache path
extract_version_hash() {
    local cache_json="$1"
    echo "$cache_json" | grep -oP '\?v=\K[a-f0-9]+' | head -1 || echo "no-hash"
}

echo "=============================================="
echo "Step 1: Check current CSS cache state"
echo "=============================================="
echo ""

# Check for modern Oxygen 6 / Breakdance meta keys
echo "Checking CSS cache meta keys..."
echo ""

# Try oxygen_ prefix first (for Oxygen mode)
OXYGEN_CSS_CACHE=$(get_css_cache_meta "oxygen_css_file_paths_cache")
OXYGEN_DEP_CACHE=$(get_css_cache_meta "oxygen_dependency_cache")

# Try breakdance_ prefix
BD_CSS_CACHE=$(get_css_cache_meta "breakdance_css_file_paths_cache")
BD_DEP_CACHE=$(get_css_cache_meta "breakdance_dependency_cache")

# Determine which meta prefix is in use
if [ "$OXYGEN_CSS_CACHE" != "null" ] && [ "$OXYGEN_CSS_CACHE" != "wp-cli not available" ]; then
    META_PREFIX="oxygen_"
    CSS_CACHE_BEFORE="$OXYGEN_CSS_CACHE"
    DEP_CACHE_BEFORE="$OXYGEN_DEP_CACHE"
    echo "Using meta prefix: oxygen_"
elif [ "$BD_CSS_CACHE" != "null" ] && [ "$BD_CSS_CACHE" != "wp-cli not available" ]; then
    META_PREFIX="breakdance_"
    CSS_CACHE_BEFORE="$BD_CSS_CACHE"
    DEP_CACHE_BEFORE="$BD_DEP_CACHE"
    echo "Using meta prefix: breakdance_"
else
    echo -e "${YELLOW}Could not determine CSS cache state (wp-cli may not be available)${NC}"
    CSS_CACHE_BEFORE=""
fi

if [ -n "$CSS_CACHE_BEFORE" ] && [ "$CSS_CACHE_BEFORE" != "wp-cli not available" ]; then
    echo ""
    echo "CSS Cache BEFORE regeneration:"
    echo "$CSS_CACHE_BEFORE" | head -200
    echo ""

    VERSION_HASH_BEFORE=$(extract_version_hash "$CSS_CACHE_BEFORE")
    echo "Version hash before: $VERSION_HASH_BEFORE"
fi

echo ""
echo "=============================================="
echo "Step 2: Call regenerate-css endpoint"
echo "=============================================="
echo ""

# Construct the API URL
API_URL="$WORDPRESS_URL/wp-json/oxybridge/v1/regenerate-css/$POST_ID"
echo "API URL: $API_URL"
echo ""

# Make the POST request
echo "Calling regenerate-css endpoint..."
START_TIME=$(date +%s%N)

if [ -n "$WORDPRESS_APP_PASSWORD" ]; then
    RESPONSE=$(curl -s -X POST \
        -u "$WORDPRESS_USERNAME:$WORDPRESS_APP_PASSWORD" \
        -w "\n%{http_code}" \
        "$API_URL")
else
    RESPONSE=$(curl -s -X POST \
        -w "\n%{http_code}" \
        "$API_URL")
fi

END_TIME=$(date +%s%N)
ELAPSED_MS=$(( ($END_TIME - $START_TIME) / 1000000 ))

# Parse response
HTTP_CODE=$(echo "$RESPONSE" | tail -1)
RESPONSE_BODY=$(echo "$RESPONSE" | sed '$d')

echo ""
echo "HTTP Status: $HTTP_CODE"
echo "Request Time: ${ELAPSED_MS}ms"
echo ""
echo "Response:"
echo "$RESPONSE_BODY" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE_BODY"
echo ""

# Check if request was successful
if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓ Regeneration endpoint returned success${NC}"

    # Extract duration from response
    REGEN_DURATION=$(echo "$RESPONSE_BODY" | grep -oP '"duration":\s*\K\d+' || echo "unknown")
    echo "  Regeneration duration: ${REGEN_DURATION}ms"
else
    echo -e "${RED}✗ Regeneration failed with HTTP $HTTP_CODE${NC}"

    # Extract error message
    ERROR_MSG=$(echo "$RESPONSE_BODY" | grep -oP '"message":\s*"\K[^"]+' || echo "Unknown error")
    echo "  Error: $ERROR_MSG"
fi

echo ""
echo "=============================================="
echo "Step 3: Verify CSS cache was updated"
echo "=============================================="
echo ""

if [ -n "$META_PREFIX" ] && command -v wp &> /dev/null; then
    CSS_CACHE_AFTER=$(get_css_cache_meta "${META_PREFIX}css_file_paths_cache")
    DEP_CACHE_AFTER=$(get_css_cache_meta "${META_PREFIX}dependency_cache")

    echo "CSS Cache AFTER regeneration:"
    echo "$CSS_CACHE_AFTER" | head -200
    echo ""

    VERSION_HASH_AFTER=$(extract_version_hash "$CSS_CACHE_AFTER")
    echo "Version hash after: $VERSION_HASH_AFTER"
    echo ""

    # Compare before and after
    if [ "$CSS_CACHE_BEFORE" = "$CSS_CACHE_AFTER" ]; then
        echo -e "${YELLOW}⚠ CSS cache appears unchanged${NC}"
        echo "  This could mean:"
        echo "    - The page content hasn't changed"
        echo "    - The hash is content-based and content is the same"
    else
        echo -e "${GREEN}✓ CSS cache was updated${NC}"
    fi

    if [ "$VERSION_HASH_BEFORE" != "$VERSION_HASH_AFTER" ]; then
        echo -e "${GREEN}✓ Version hash changed: $VERSION_HASH_BEFORE -> $VERSION_HASH_AFTER${NC}"
    fi
else
    echo -e "${YELLOW}Cannot verify cache state (wp-cli not available)${NC}"
    echo ""
    echo "Manual verification steps:"
    echo "1. Run in WordPress: get_post_meta($POST_ID, '{prefix}css_file_paths_cache', true)"
    echo "2. Compare the version hash (?v=xxxx) before and after regeneration"
fi

echo ""
echo "=============================================="
echo "Step 4: Frontend verification (manual)"
echo "=============================================="
echo ""
echo "To complete verification, manually check:"
echo ""
echo "1. Open the page in a browser:"
echo "   $WORDPRESS_URL/?p=$POST_ID"
echo ""
echo "2. Check the browser DevTools Network tab:"
echo "   - Look for CSS files being loaded"
echo "   - Verify no 404 errors for CSS files"
echo "   - Check that CSS file URLs contain version hash"
echo ""
echo "3. Verify visual styling:"
echo "   - Page should render with correct styles"
echo "   - No unstyled content flash"
echo "   - All elements should have their styles applied"
echo ""
echo "=============================================="
echo "Verification Summary"
echo "=============================================="
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓ API Test: PASSED${NC} - Endpoint returned success"
else
    echo -e "${RED}✗ API Test: FAILED${NC} - HTTP $HTTP_CODE"
fi

if [ -n "$VERSION_HASH_AFTER" ] && [ "$VERSION_HASH_AFTER" != "no-hash" ]; then
    echo -e "${GREEN}✓ Cache Test: PASSED${NC} - CSS cache contains version hash"
else
    echo -e "${YELLOW}⚠ Cache Test: MANUAL${NC} - Verify cache manually"
fi

echo ""
echo "Done!"
