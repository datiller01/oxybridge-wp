#!/bin/bash
#
# Gradient Background API Test Script
# Tests gradient background properties via the OxyBridge REST API.
#

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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
    ((PASSED++))
}

log_fail() {
    echo -e "${RED}FAIL${NC}: $1"
    ((FAILED++))
}

log_info() {
    echo -e "  INFO: $1"
}

get_auth() {
    echo "-u ${USERNAME}:${APP_PASSWORD}"
}

cleanup() {
    echo ""
    echo "=== Cleanup ==="
    for page_id in "${CREATED_PAGE_IDS[@]}"; do
        if [ -n "$page_id" ]; then
            DELETE_RESULT=$(curl -sk -o /dev/null -w "%{http_code}" -X DELETE \
                "${SITE_URL}/wp-json/wp/v2/pages/${page_id}?force=true" \
                $(get_auth))
            log_info "Deleted page ${page_id}: HTTP ${DELETE_RESULT}"
        fi
    done
}

echo "============================================="
echo "Gradient Background API Tests"
echo "============================================="
echo "Site URL: ${SITE_URL}"
echo "API Base: ${API_BASE}"
echo ""

echo "=== Test 1: Basic Linear Gradient ==="
REQUEST_DATA='{"title": "Gradient Test 1 - Linear", "status": "draft", "tree": {"type": "Section", "gradientType": "linear", "gradientDirection": "to bottom", "gradientColors": ["#ff0000", "#0000ff"]}}'

RESPONSE=$(curl -sk -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

if [ "$HTTP_CODE" = "201" ]; then
    PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
    log_pass "Linear gradient - Status: ${HTTP_CODE}, Page ID: ${PAGE_ID}"
    CREATED_PAGE_IDS+=("$PAGE_ID")
else
    log_fail "Linear gradient - Expected 201, Got: ${HTTP_CODE}"
    log_info "Response: ${RESPONSE}"
fi

echo ""
echo "=== Test 2: Radial Gradient ==="
REQUEST_DATA='{"title": "Gradient Test 2 - Radial", "status": "draft", "tree": {"type": "Section", "gradientType": "radial", "gradientColors": ["#ffffff", "#000000"]}}'

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

RESPONSE=$(curl -sk -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

if [ "$HTTP_CODE" = "201" ]; then
    PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
    log_pass "Radial gradient - Status: ${HTTP_CODE}, Page ID: ${PAGE_ID}"
    CREATED_PAGE_IDS+=("$PAGE_ID")
else
    log_fail "Radial gradient - Expected 201, Got: ${HTTP_CODE}"
fi

echo ""
echo "=== Test 3: Gradient with Multiple Color Stops ==="
REQUEST_DATA='{"title": "Gradient Test 3 - Multi Color", "status": "draft", "tree": {"type": "Section", "gradientType": "linear", "gradientDirection": "to right", "gradientColors": ["#ff0000", "#ffff00", "#00ff00", "#00ffff", "#0000ff"]}}'

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

RESPONSE=$(curl -sk -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

if [ "$HTTP_CODE" = "201" ]; then
    PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
    log_pass "Multi-color gradient - Status: ${HTTP_CODE}, Page ID: ${PAGE_ID}"
    CREATED_PAGE_IDS+=("$PAGE_ID")
else
    log_fail "Multi-color gradient - Expected 201, Got: ${HTTP_CODE}"
fi

echo ""
echo "=== Test 4: Gradient with CSS Gradient String ==="
REQUEST_DATA='{"title": "Gradient Test 4 - CSS String", "status": "draft", "tree": {"type": "Section", "backgroundGradient": "linear-gradient(45deg, #ff0000, #00ff00)"}}'

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

RESPONSE=$(curl -sk -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

if [ "$HTTP_CODE" = "201" ]; then
    PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
    log_pass "CSS gradient string - Status: ${HTTP_CODE}, Page ID: ${PAGE_ID}"
    CREATED_PAGE_IDS+=("$PAGE_ID")
else
    log_fail "CSS gradient string - Expected 201, Got: ${HTTP_CODE}"
fi

echo ""
echo "=== Test 5: Gradient with Blend Mode ==="
REQUEST_DATA='{"title": "Gradient Test 5 - Blend Mode", "status": "draft", "tree": {"type": "Section", "gradientType": "linear", "gradientDirection": "to bottom right", "gradientColors": ["#ff6b6b", "#4ecdc4"], "backgroundBlendMode": "overlay"}}'

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

RESPONSE=$(curl -sk -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

if [ "$HTTP_CODE" = "201" ]; then
    PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
    log_pass "Gradient with blend mode - Status: ${HTTP_CODE}, Page ID: ${PAGE_ID}"
    CREATED_PAGE_IDS+=("$PAGE_ID")
else
    log_fail "Gradient with blend mode - Expected 201, Got: ${HTTP_CODE}"
fi

echo ""
echo "=== Test 6: Gradient with Overlay ==="
REQUEST_DATA='{"title": "Gradient Test 6 - Overlay", "status": "draft", "tree": {"type": "Section", "backgroundGradient": "linear-gradient(180deg, #667eea, #764ba2)", "overlayColor": "rgba(0,0,0,0.3)", "overlayOpacity": "0.5"}}'

HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

RESPONSE=$(curl -sk -X POST "${API_BASE}/pages" \
    $(get_auth) \
    -H "Content-Type: application/json" \
    -d "${REQUEST_DATA}")

if [ "$HTTP_CODE" = "201" ]; then
    PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
    log_pass "Gradient with overlay - Status: ${HTTP_CODE}, Page ID: ${PAGE_ID}"
    CREATED_PAGE_IDS+=("$PAGE_ID")
else
    log_fail "Gradient with overlay - Expected 201, Got: ${HTTP_CODE}"
fi

cleanup

echo ""
echo "============================================="
echo "Test Summary"
echo "============================================="
echo -e "${GREEN}Passed: ${PASSED}${NC}"
echo -e "${RED}Failed: ${FAILED}${NC}"
echo ""

if [ "$FAILED" -eq 0 ]; then
    echo -e "${GREEN}All gradient tests passed!${NC}"
    exit 0
else
    echo -e "${RED}Some gradient tests failed.${NC}"
    exit 1
fi
