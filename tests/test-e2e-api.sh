#!/bin/bash
#
# Oxybridge REST API E2E Test Script
#
# This script tests the complete REST API workflow using curl.
# It requires a WordPress site with Oxybridge installed and authentication configured.
#
# Usage: ./test-e2e-api.sh <site_url> <username> <app_password>
#
# Example:
#   ./test-e2e-api.sh http://localhost admin "xxxx xxxx xxxx xxxx xxxx xxxx"
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counters
PASSED=0
FAILED=0

# Configuration
SITE_URL="${1:-http://localhost}"
USERNAME="${2:-admin}"
APP_PASSWORD="${3:-}"
API_BASE="${SITE_URL}/wp-json/oxybridge/v1"

# Cleanup tracking
CREATED_PAGE_ID=""
CREATED_CLONE_ID=""

# Print colored output
log_pass() {
    echo -e "${GREEN}✓ PASS${NC}: $1"
    ((PASSED++))
}

log_fail() {
    echo -e "${RED}✗ FAIL${NC}: $1"
    ((FAILED++))
}

log_warn() {
    echo -e "${YELLOW}! WARN${NC}: $1"
}

log_info() {
    echo -e "  INFO: $1"
}

# Check prerequisites
check_prereqs() {
    echo "============================================="
    echo "Oxybridge REST API E2E Tests"
    echo "============================================="
    echo ""
    echo "Site URL: ${SITE_URL}"
    echo "API Base: ${API_BASE}"
    echo "Username: ${USERNAME}"
    echo ""

    # Check for curl
    if ! command -v curl &> /dev/null; then
        echo "Error: curl is required but not installed."
        exit 1
    fi

    # Check for jq
    if ! command -v jq &> /dev/null; then
        echo "Warning: jq not found. JSON parsing will be limited."
        HAS_JQ=false
    else
        HAS_JQ=true
    fi

    # Check authentication
    if [ -z "$APP_PASSWORD" ]; then
        echo "Warning: No app password provided. Tests requiring authentication may fail."
        echo "Usage: $0 <site_url> <username> <app_password>"
        echo ""
    fi
}

# Build auth header
get_auth() {
    if [ -n "$APP_PASSWORD" ]; then
        echo "-u ${USERNAME}:${APP_PASSWORD}"
    fi
}

# Test: Health Check (no auth required)
test_health_check() {
    echo ""
    echo "=== Test: Health Check ==="

    RESPONSE=$(curl -s "${API_BASE}/health")

    if $HAS_JQ; then
        STATUS=$(echo "$RESPONSE" | jq -r '.status // empty')
    else
        STATUS=$(echo "$RESPONSE" | grep -o '"status":"[^"]*"' | head -1 | cut -d'"' -f4)
    fi

    if [ "$STATUS" = "ok" ]; then
        log_pass "Health check returned status 'ok'"

        if $HAS_JQ; then
            VERSION=$(echo "$RESPONSE" | jq -r '.version // "unknown"')
            OXYGEN_ACTIVE=$(echo "$RESPONSE" | jq -r '.oxygen_active // "unknown"')
            log_info "Plugin version: ${VERSION}"
            log_info "Oxygen active: ${OXYGEN_ACTIVE}"
        fi
    else
        log_fail "Health check failed or returned unexpected status"
        log_info "Response: ${RESPONSE}"
        return 1
    fi
}

# Test: Info Endpoint
test_info_endpoint() {
    echo ""
    echo "=== Test: Info Endpoint ==="

    RESPONSE=$(curl -s "${API_BASE}/info")
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${API_BASE}/info")

    if [ "$HTTP_CODE" = "200" ]; then
        log_pass "Info endpoint returned 200"

        if $HAS_JQ; then
            BUILDER=$(echo "$RESPONSE" | jq -r '.builder // "unknown"')
            CAPABILITIES=$(echo "$RESPONSE" | jq -r '.capabilities | length // 0')
            log_info "Builder: ${BUILDER}"
            log_info "Capabilities count: ${CAPABILITIES}"
        fi
    else
        log_fail "Info endpoint returned ${HTTP_CODE}"
        return 1
    fi
}

# Test: Create Page
test_create_page() {
    echo ""
    echo "=== Test: Create Page with Design ==="

    if [ -z "$APP_PASSWORD" ]; then
        log_warn "Skipping - authentication required"
        return 0
    fi

    # Create test tree JSON
    TREE_JSON=$(cat <<'EOF'
{
    "root": {
        "id": "e2e-root-shell",
        "data": {
            "type": "EssentialElements\\Root"
        },
        "children": [{
            "id": "e2e-section-shell",
            "data": {
                "type": "EssentialElements\\Section"
            },
            "children": [{
                "id": "e2e-heading-shell",
                "data": {
                    "type": "EssentialElements\\Heading",
                    "properties": {
                        "content": {
                            "text": "E2E Shell Test Page",
                            "tag": "h1"
                        }
                    }
                },
                "children": []
            }]
        }]
    }
}
EOF
)

    # Create page request
    REQUEST_DATA=$(cat <<EOF
{
    "title": "Shell E2E Test - $(date +%Y%m%d%H%M%S)",
    "status": "draft",
    "post_type": "page",
    "enable_oxygen": true,
    "tree": ${TREE_JSON}
}
EOF
)

    RESPONSE=$(curl -s -X POST "${API_BASE}/pages" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${REQUEST_DATA}")

    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/pages" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${REQUEST_DATA}")

    if [ "$HTTP_CODE" = "201" ]; then
        if $HAS_JQ; then
            CREATED_PAGE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
        else
            CREATED_PAGE_ID=$(echo "$RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
        fi

        if [ -n "$CREATED_PAGE_ID" ]; then
            log_pass "Page created with ID: ${CREATED_PAGE_ID}"
        else
            log_fail "Page created but couldn't parse ID"
            return 1
        fi
    else
        log_fail "Create page returned ${HTTP_CODE}"
        log_info "Response: ${RESPONSE}"
        return 1
    fi
}

# Test: Read Document
test_read_document() {
    echo ""
    echo "=== Test: Read Document ==="

    if [ -z "$CREATED_PAGE_ID" ]; then
        log_warn "Skipping - no page ID available"
        return 0
    fi

    RESPONSE=$(curl -s "${API_BASE}/documents/${CREATED_PAGE_ID}" $(get_auth))
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${API_BASE}/documents/${CREATED_PAGE_ID}" $(get_auth))

    if [ "$HTTP_CODE" = "200" ]; then
        log_pass "Document retrieved successfully"

        if $HAS_JQ; then
            HAS_ROOT=$(echo "$RESPONSE" | jq -r '.tree.root.id // empty')
            ELEMENT_COUNT=$(echo "$RESPONSE" | jq -r '.element_count // 0')
            log_info "Has root: $([ -n \"$HAS_ROOT\" ] && echo 'Yes' || echo 'No')"
            log_info "Element count: ${ELEMENT_COUNT}"
        fi
    else
        log_fail "Read document returned ${HTTP_CODE}"
        return 1
    fi
}

# Test: Render Document
test_render_document() {
    echo ""
    echo "=== Test: Render Document ==="

    if [ -z "$CREATED_PAGE_ID" ]; then
        log_warn "Skipping - no page ID available"
        return 0
    fi

    RESPONSE=$(curl -s "${API_BASE}/render/${CREATED_PAGE_ID}" $(get_auth))
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${API_BASE}/render/${CREATED_PAGE_ID}" $(get_auth))

    if [ "$HTTP_CODE" = "200" ]; then
        log_pass "Document rendered successfully"

        # Check for HTML content
        if echo "$RESPONSE" | grep -q "html\|HTML\|E2E"; then
            log_info "Response contains HTML-like content"
        fi
    else
        log_fail "Render document returned ${HTTP_CODE}"
        return 1
    fi
}

# Test: Clone Page
test_clone_page() {
    echo ""
    echo "=== Test: Clone Page ==="

    if [ -z "$CREATED_PAGE_ID" ]; then
        log_warn "Skipping - no page ID available"
        return 0
    fi

    CLONE_DATA='{"title": "Cloned Test Page", "status": "draft"}'

    RESPONSE=$(curl -s -X POST "${API_BASE}/clone/${CREATED_PAGE_ID}" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${CLONE_DATA}")

    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/clone/${CREATED_PAGE_ID}" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${CLONE_DATA}")

    if [ "$HTTP_CODE" = "201" ]; then
        if $HAS_JQ; then
            CREATED_CLONE_ID=$(echo "$RESPONSE" | jq -r '.id // empty')
        else
            CREATED_CLONE_ID=$(echo "$RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
        fi

        log_pass "Page cloned with ID: ${CREATED_CLONE_ID}"
    else
        log_fail "Clone page returned ${HTTP_CODE}"
        return 1
    fi
}

# Test: Validate Tree
test_validate_tree() {
    echo ""
    echo "=== Test: Validate Tree ==="

    VALID_TREE='{"tree": {"root": {"id": "test", "data": {"type": "EssentialElements\\Root"}, "children": []}}}'

    RESPONSE=$(curl -s -X POST "${API_BASE}/validate" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${VALID_TREE}")

    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/validate" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${VALID_TREE}")

    if [ "$HTTP_CODE" = "200" ]; then
        log_pass "Validate endpoint returned 200"

        if $HAS_JQ; then
            IS_VALID=$(echo "$RESPONSE" | jq -r '.valid // false')
            log_info "Tree valid: ${IS_VALID}"
        fi
    else
        log_fail "Validate returned ${HTTP_CODE}"
        return 1
    fi
}

# Test: List Pages
test_list_pages() {
    echo ""
    echo "=== Test: List Pages ==="

    RESPONSE=$(curl -s "${API_BASE}/pages?per_page=5&status=any" $(get_auth))
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${API_BASE}/pages?per_page=5&status=any" $(get_auth))

    if [ "$HTTP_CODE" = "200" ]; then
        log_pass "List pages returned 200"

        if $HAS_JQ; then
            PAGE_COUNT=$(echo "$RESPONSE" | jq -r '.pages | length // 0')
            TOTAL=$(echo "$RESPONSE" | jq -r '.total // 0')
            log_info "Pages in response: ${PAGE_COUNT}"
            log_info "Total pages: ${TOTAL}"
        fi
    else
        log_fail "List pages returned ${HTTP_CODE}"
        return 1
    fi
}

# Test: Discovery Endpoints
test_discovery_endpoints() {
    echo ""
    echo "=== Test: Discovery Endpoints ==="

    ENDPOINTS=("breakpoints" "colors" "variables" "fonts" "classes" "schema")

    for endpoint in "${ENDPOINTS[@]}"; do
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${API_BASE}/${endpoint}" $(get_auth))

        if [ "$HTTP_CODE" = "200" ]; then
            log_pass "${endpoint} endpoint returned 200"
        else
            log_fail "${endpoint} endpoint returned ${HTTP_CODE}"
        fi
    done
}

# Cleanup
cleanup() {
    echo ""
    echo "=== Cleanup ==="

    if [ -n "$CREATED_CLONE_ID" ] && [ -n "$APP_PASSWORD" ]; then
        # Delete via WordPress REST API
        DELETE_RESULT=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE \
            "${SITE_URL}/wp-json/wp/v2/pages/${CREATED_CLONE_ID}?force=true" \
            $(get_auth))
        log_info "Deleted cloned page ${CREATED_CLONE_ID}: HTTP ${DELETE_RESULT}"
    fi

    if [ -n "$CREATED_PAGE_ID" ] && [ -n "$APP_PASSWORD" ]; then
        DELETE_RESULT=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE \
            "${SITE_URL}/wp-json/wp/v2/pages/${CREATED_PAGE_ID}?force=true" \
            $(get_auth))
        log_info "Deleted test page ${CREATED_PAGE_ID}: HTTP ${DELETE_RESULT}"
    fi
}

# Print summary
print_summary() {
    echo ""
    echo "============================================="
    echo "Test Summary"
    echo "============================================="
    echo ""
    echo "Total: $((PASSED + FAILED))"
    echo -e "${GREEN}Passed: ${PASSED}${NC}"
    echo -e "${RED}Failed: ${FAILED}${NC}"
    echo ""

    if [ "$FAILED" -eq 0 ]; then
        echo -e "${GREEN}All tests passed!${NC}"
        exit 0
    else
        echo -e "${RED}Some tests failed.${NC}"
        exit 1
    fi
}

# Main execution
main() {
    check_prereqs

    test_health_check
    test_info_endpoint
    test_list_pages
    test_discovery_endpoints
    test_validate_tree

    # These tests require authentication
    if [ -n "$APP_PASSWORD" ]; then
        test_create_page
        test_read_document
        test_render_document
        test_clone_page
        cleanup
    else
        echo ""
        log_warn "Skipping authenticated tests - no app password provided"
    fi

    print_summary
}

# Run main function
main
