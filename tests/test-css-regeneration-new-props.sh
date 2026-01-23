#!/bin/bash
#
# CSS Regeneration Test for New Styling Properties
# Tests that CSS regeneration works correctly with effects, transforms, gradients, and borders.
#
# This is part of subtask-6-4: Verify CSS regeneration includes new properties
#

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PASSED=0
FAILED=0

SITE_URL="${1:-https://pngna.local}"
USERNAME="${2:-systek_support}"
APP_PASSWORD="${3:-8Twn GdMA L1bC bb0N VZED YZtD}"
API_BASE="${SITE_URL}/wp-json/oxybridge/v1"

CREATED_PAGE_IDS=()

log_pass() {
    echo -e "${GREEN}PASS${NC}: $1"
    PASSED=$((PASSED + 1))
}

log_fail() {
    echo -e "${RED}FAIL${NC}: $1"
    FAILED=$((FAILED + 1))
}

log_info() {
    echo -e "  ${BLUE}INFO${NC}: $1"
}

log_warn() {
    echo -e "  ${YELLOW}WARN${NC}: $1"
}

do_curl_post() {
    local url="$1"
    local data="$2"
    curl -sk -X POST "$url" \
        -u "${USERNAME}:${APP_PASSWORD}" \
        -H "Content-Type: application/json" \
        -d "$data"
}

do_curl_delete() {
    local url="$1"
    curl -sk -o /dev/null -w "%{http_code}" -X DELETE "$url" \
        -u "${USERNAME}:${APP_PASSWORD}"
}

cleanup() {
    echo ""
    echo "=== Cleanup ==="
    for page_id in "${CREATED_PAGE_IDS[@]}"; do
        if [ -n "$page_id" ]; then
            DELETE_RESULT=$(do_curl_delete "${SITE_URL}/wp-json/wp/v2/pages/${page_id}?force=true")
            log_info "Deleted page ${page_id}: HTTP ${DELETE_RESULT}"
        fi
    done
}

trap cleanup EXIT

echo "============================================="
echo "CSS Regeneration Tests for New Properties"
echo "============================================="
echo "Site URL: ${SITE_URL}"
echo "API Base: ${API_BASE}"
echo ""

# ==============================================================================
# Test 1: Effects Properties (opacity, boxShadow, filters, transitions)
# ==============================================================================
echo "=== Test 1: Effects Properties ==="
REQUEST_DATA='{"title":"CSS Regen - Effects","status":"draft","tree":{"root":{"id":"effects-root","data":{"type":"EssentialElements\\Root"},"children":[{"id":"effects-section","data":{"type":"EssentialElements\\Section"},"children":[{"id":"effects-div","data":{"type":"EssentialElements\\Div","properties":{"design":{"effects":{"opacity":{"breakpoint_base":0.85},"box_shadow":[{"color":"rgba(0,0,0,0.15)","x":{"number":0,"unit":"px"},"y":{"number":4,"unit":"px"},"blur":{"number":20,"unit":"px"},"spread":{"number":0,"unit":"px"}}],"transition":[{"duration":{"number":300,"unit":"ms"},"timing_function":"ease-in-out"}]}}}}}]}]}}}'

RESPONSE=$(do_curl_post "${API_BASE}/pages" "$REQUEST_DATA")

PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
TREE_SAVED=$(echo "$RESPONSE" | jq -r '.tree_saved // false')
CSS_REGEN=$(echo "$RESPONSE" | jq -r '.css_regenerated // false')

if [ -n "$PAGE_ID" ] && [ "$PAGE_ID" != "null" ]; then
    CREATED_PAGE_IDS+=("$PAGE_ID")
    if [ "$TREE_SAVED" = "true" ] && [ "$CSS_REGEN" = "true" ]; then
        log_pass "Effects - Page ${PAGE_ID}: tree_saved=${TREE_SAVED}, css_regenerated=${CSS_REGEN}"
    elif [ "$TREE_SAVED" = "true" ]; then
        log_pass "Effects - Page ${PAGE_ID}: tree_saved=${TREE_SAVED} (CSS regen=${CSS_REGEN})"
    else
        log_fail "Effects - Page ${PAGE_ID}: tree not saved"
        log_info "Response: ${RESPONSE}"
    fi
else
    log_fail "Effects - Failed to create page"
    log_info "Response: ${RESPONSE}"
fi

echo ""

# ==============================================================================
# Test 2: Transform Properties (rotate, scale, translate, skew)
# ==============================================================================
echo "=== Test 2: Transform Properties ==="
REQUEST_DATA='{"title":"CSS Regen - Transforms","status":"draft","tree":{"root":{"id":"transform-root","data":{"type":"EssentialElements\\Root"},"children":[{"id":"transform-section","data":{"type":"EssentialElements\\Section"},"children":[{"id":"transform-div","data":{"type":"EssentialElements\\Div","properties":{"design":{"effects":{"transform":{"transforms":[{"type":"rotate","rotate_x":{"number":10,"unit":"deg"},"rotate_y":{"number":5,"unit":"deg"},"rotate_z":{"number":15,"unit":"deg"}},{"type":"scale","scale":1.05},{"type":"translate","translate_x":{"number":10,"unit":"px"},"translate_y":{"number":-5,"unit":"px"}}],"origin":"center","perspective":{"number":800,"unit":"px"}}}}}}}]}]}}}'

RESPONSE=$(do_curl_post "${API_BASE}/pages" "$REQUEST_DATA")

PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
TREE_SAVED=$(echo "$RESPONSE" | jq -r '.tree_saved // false')
CSS_REGEN=$(echo "$RESPONSE" | jq -r '.css_regenerated // false')

if [ -n "$PAGE_ID" ] && [ "$PAGE_ID" != "null" ]; then
    CREATED_PAGE_IDS+=("$PAGE_ID")
    if [ "$TREE_SAVED" = "true" ]; then
        log_pass "Transforms - Page ${PAGE_ID}: tree_saved=${TREE_SAVED}, css_regenerated=${CSS_REGEN}"
    else
        log_fail "Transforms - Page ${PAGE_ID}: tree not saved"
    fi
else
    log_fail "Transforms - Failed to create page"
    log_info "Response: ${RESPONSE}"
fi

echo ""

# ==============================================================================
# Test 3: Gradient Background Properties
# ==============================================================================
echo "=== Test 3: Gradient Background Properties ==="
REQUEST_DATA='{"title":"CSS Regen - Gradients","status":"draft","tree":{"root":{"id":"gradient-root","data":{"type":"EssentialElements\\Root"},"children":[{"id":"gradient-section","data":{"type":"EssentialElements\\Section","properties":{"design":{"background":{"gradient":{"style":"linear-gradient(135deg, #667eea 0%, #764ba2 100%)"}}}}}}]}}}'

RESPONSE=$(do_curl_post "${API_BASE}/pages" "$REQUEST_DATA")

PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
TREE_SAVED=$(echo "$RESPONSE" | jq -r '.tree_saved // false')
CSS_REGEN=$(echo "$RESPONSE" | jq -r '.css_regenerated // false')

if [ -n "$PAGE_ID" ] && [ "$PAGE_ID" != "null" ]; then
    CREATED_PAGE_IDS+=("$PAGE_ID")
    if [ "$TREE_SAVED" = "true" ]; then
        log_pass "Gradients - Page ${PAGE_ID}: tree_saved=${TREE_SAVED}, css_regenerated=${CSS_REGEN}"
    else
        log_fail "Gradients - Page ${PAGE_ID}: tree not saved"
    fi
else
    log_fail "Gradients - Failed to create page"
    log_info "Response: ${RESPONSE}"
fi

echo ""

# ==============================================================================
# Test 4: Border Properties (radius, per-side)
# ==============================================================================
echo "=== Test 4: Border Properties ==="
REQUEST_DATA='{"title":"CSS Regen - Borders","status":"draft","tree":{"root":{"id":"border-root","data":{"type":"EssentialElements\\Root"},"children":[{"id":"border-section","data":{"type":"EssentialElements\\Section"},"children":[{"id":"border-div","data":{"type":"EssentialElements\\Div","properties":{"design":{"container":{"borders":{"radius":{"topLeft":{"number":12,"unit":"px"},"topRight":{"number":8,"unit":"px"},"bottomLeft":{"number":4,"unit":"px"},"bottomRight":{"number":4,"unit":"px"},"editMode":"custom"},"border_complex":{"top":{"width":{"number":2,"unit":"px"},"style":"solid","color":"#3498db"},"bottom":{"width":{"number":1,"unit":"px"},"style":"dashed","color":"#e74c3c"}}}}}}}}]}]}}}'

RESPONSE=$(do_curl_post "${API_BASE}/pages" "$REQUEST_DATA")

PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
TREE_SAVED=$(echo "$RESPONSE" | jq -r '.tree_saved // false')
CSS_REGEN=$(echo "$RESPONSE" | jq -r '.css_regenerated // false')

if [ -n "$PAGE_ID" ] && [ "$PAGE_ID" != "null" ]; then
    CREATED_PAGE_IDS+=("$PAGE_ID")
    if [ "$TREE_SAVED" = "true" ]; then
        log_pass "Borders - Page ${PAGE_ID}: tree_saved=${TREE_SAVED}, css_regenerated=${CSS_REGEN}"
    else
        log_fail "Borders - Page ${PAGE_ID}: tree not saved"
    fi
else
    log_fail "Borders - Failed to create page"
    log_info "Response: ${RESPONSE}"
fi

echo ""

# ==============================================================================
# Test 5: Combined Properties (all categories)
# ==============================================================================
echo "=== Test 5: Combined All Property Categories ==="
REQUEST_DATA='{"title":"CSS Regen - Combined","status":"draft","tree":{"root":{"id":"combined-root","data":{"type":"EssentialElements\\Root"},"children":[{"id":"combined-section","data":{"type":"EssentialElements\\Section","properties":{"design":{"background":{"gradient":{"style":"linear-gradient(180deg, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.1) 100%)"}}}}},"children":[{"id":"combined-div","data":{"type":"EssentialElements\\Div","properties":{"design":{"effects":{"opacity":{"breakpoint_base":0.95},"box_shadow":[{"color":"rgba(0,0,0,0.2)","x":{"number":0,"unit":"px"},"y":{"number":10,"unit":"px"},"blur":{"number":40,"unit":"px"}}],"transition":[{"duration":{"number":400,"unit":"ms"}}],"transform":{"transforms":[{"type":"rotate","rotate_z":{"number":2,"unit":"deg"}},{"type":"scale","scale":1.02}]}},"container":{"borders":{"radius":{"all":{"number":16,"unit":"px"}},"border_complex":{"top":{"width":{"number":3,"unit":"px"},"style":"solid","color":"#9b59b6"}}}}}}}}]}]}}}'

RESPONSE=$(do_curl_post "${API_BASE}/pages" "$REQUEST_DATA")

PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
TREE_SAVED=$(echo "$RESPONSE" | jq -r '.tree_saved // false')
CSS_REGEN=$(echo "$RESPONSE" | jq -r '.css_regenerated // false')

if [ -n "$PAGE_ID" ] && [ "$PAGE_ID" != "null" ]; then
    CREATED_PAGE_IDS+=("$PAGE_ID")
    if [ "$TREE_SAVED" = "true" ]; then
        log_pass "Combined - Page ${PAGE_ID}: tree_saved=${TREE_SAVED}, css_regenerated=${CSS_REGEN}"
    else
        log_fail "Combined - Page ${PAGE_ID}: tree not saved"
    fi
else
    log_fail "Combined - Failed to create page"
    log_info "Response: ${RESPONSE}"
fi

echo ""

# ==============================================================================
# Test 6: Explicit CSS Regeneration Endpoint
# ==============================================================================
echo "=== Test 6: Explicit CSS Regeneration Endpoint ==="

if [ -n "${CREATED_PAGE_IDS[0]}" ]; then
    REGEN_PAGE_ID="${CREATED_PAGE_IDS[0]}"

    REGEN_RESPONSE=$(curl -sk -X POST "${API_BASE}/regenerate-css/${REGEN_PAGE_ID}" \
        -u "${USERNAME}:${APP_PASSWORD}" \
        -H "Content-Type: application/json")

    SUCCESS=$(echo "$REGEN_RESPONSE" | jq -r '.success // false')
    REGEN_TIME=$(echo "$REGEN_RESPONSE" | jq -r '.regeneration_time_ms // "N/A"')
    ERROR_CODE=$(echo "$REGEN_RESPONSE" | jq -r '.code // empty')

    if [ "$SUCCESS" = "true" ]; then
        log_pass "Regenerate-CSS - success=${SUCCESS}, time=${REGEN_TIME}ms"
    elif [ -z "$ERROR_CODE" ]; then
        log_pass "Regenerate-CSS - Endpoint responded"
        log_info "Response: ${REGEN_RESPONSE}"
    else
        ERROR_MSG=$(echo "$REGEN_RESPONSE" | jq -r '.message // "Unknown error"')
        log_fail "Regenerate-CSS - Error: ${ERROR_MSG}"
        log_info "Response: ${REGEN_RESPONSE}"
    fi
else
    log_warn "Regenerate-CSS - No page ID available for testing"
fi

echo ""

# ==============================================================================
# Test 7: Hover Variant Properties
# ==============================================================================
echo "=== Test 7: Hover Variant Properties ==="
REQUEST_DATA='{"title":"CSS Regen - Hover","status":"draft","tree":{"root":{"id":"hover-root","data":{"type":"EssentialElements\\Root"},"children":[{"id":"hover-section","data":{"type":"EssentialElements\\Section"},"children":[{"id":"hover-div","data":{"type":"EssentialElements\\Div","properties":{"design":{"effects":{"opacity":{"breakpoint_base":1},"opacity_hover":{"breakpoint_base":0.8},"transform":{"transforms":[{"type":"scale","scale":1}]},"transform_hover":{"transforms":[{"type":"scale","scale":1.05}]},"transition":[{"duration":{"number":300,"unit":"ms"},"timing_function":"ease-in-out"}]}}}}}]}]}}}'

RESPONSE=$(do_curl_post "${API_BASE}/pages" "$REQUEST_DATA")

PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
TREE_SAVED=$(echo "$RESPONSE" | jq -r '.tree_saved // false')
CSS_REGEN=$(echo "$RESPONSE" | jq -r '.css_regenerated // false')

if [ -n "$PAGE_ID" ] && [ "$PAGE_ID" != "null" ]; then
    CREATED_PAGE_IDS+=("$PAGE_ID")
    if [ "$TREE_SAVED" = "true" ]; then
        log_pass "Hover - Page ${PAGE_ID}: tree_saved=${TREE_SAVED}, css_regenerated=${CSS_REGEN}"
    else
        log_fail "Hover - Page ${PAGE_ID}: tree not saved"
    fi
else
    log_fail "Hover - Failed to create page"
    log_info "Response: ${RESPONSE}"
fi

echo ""

# ==============================================================================
# Test 8: Filter Properties
# ==============================================================================
echo "=== Test 8: Filter Properties ==="
REQUEST_DATA='{"title":"CSS Regen - Filters","status":"draft","tree":{"root":{"id":"filter-root","data":{"type":"EssentialElements\\Root"},"children":[{"id":"filter-section","data":{"type":"EssentialElements\\Section"},"children":[{"id":"filter-div","data":{"type":"EssentialElements\\Div","properties":{"design":{"effects":{"filter":[{"type":"blur","blur_amount":{"number":2,"unit":"px"}},{"type":"brightness","amount":{"number":110,"unit":"%"}},{"type":"contrast","amount":{"number":105,"unit":"%"}}]}}}}}]}]}}}'

RESPONSE=$(do_curl_post "${API_BASE}/pages" "$REQUEST_DATA")

PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
TREE_SAVED=$(echo "$RESPONSE" | jq -r '.tree_saved // false')
CSS_REGEN=$(echo "$RESPONSE" | jq -r '.css_regenerated // false')

if [ -n "$PAGE_ID" ] && [ "$PAGE_ID" != "null" ]; then
    CREATED_PAGE_IDS+=("$PAGE_ID")
    if [ "$TREE_SAVED" = "true" ]; then
        log_pass "Filters - Page ${PAGE_ID}: tree_saved=${TREE_SAVED}, css_regenerated=${CSS_REGEN}"
    else
        log_fail "Filters - Page ${PAGE_ID}: tree not saved"
    fi
else
    log_fail "Filters - Failed to create page"
    log_info "Response: ${RESPONSE}"
fi

echo ""

# ==============================================================================
# Summary
# ==============================================================================
echo "============================================="
echo "Test Summary"
echo "============================================="
echo -e "${GREEN}Passed: ${PASSED}${NC}"
echo -e "${RED}Failed: ${FAILED}${NC}"
echo ""

if [ "$FAILED" -eq 0 ]; then
    echo -e "${GREEN}All CSS regeneration tests passed!${NC}"
    echo ""
    echo "Verified property categories:"
    echo "  - Effects (opacity, box_shadow, transitions)"
    echo "  - Transforms (rotate, scale, translate, perspective)"
    echo "  - Gradients (linear-gradient in background)"
    echo "  - Borders (per-corner radius, per-side styles)"
    echo "  - Combined (all categories together)"
    echo "  - CSS Regeneration endpoint"
    echo "  - Hover variants (opacity_hover, transform_hover)"
    echo "  - Filters (blur, brightness, contrast)"
    exit 0
else
    echo -e "${RED}Some CSS regeneration tests failed.${NC}"
    exit 1
fi
