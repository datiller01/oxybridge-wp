#!/bin/bash
#
# Oxybridge REST API Test Script
#
# Tests the REST API endpoints with authentication.
# Requires a running WordPress installation with the Oxybridge plugin active.
#
# Usage:
#   ./test-rest-api.sh [WORDPRESS_URL] [USERNAME] [APP_PASSWORD]
#
# Example:
#   ./test-rest-api.sh http://localhost admin "xxxx xxxx xxxx xxxx xxxx xxxx"
#
# Environment variables can also be used:
#   WORDPRESS_URL, WORDPRESS_USERNAME, WORDPRESS_APP_PASSWORD
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
WORDPRESS_URL="${1:-${WORDPRESS_URL:-http://localhost}}"
USERNAME="${2:-${WORDPRESS_USERNAME:-admin}}"
APP_PASSWORD="${3:-${WORDPRESS_APP_PASSWORD:-}}"
API_BASE="${WORDPRESS_URL}/wp-json/oxybridge/v1"

# Counters
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_SKIPPED=0

# Print header
echo "=============================================="
echo "Oxybridge REST API Test Suite"
echo "=============================================="
echo "WordPress URL: ${WORDPRESS_URL}"
echo "API Base: ${API_BASE}"
echo "Username: ${USERNAME}"
echo "App Password: $(echo "${APP_PASSWORD}" | sed 's/./***/g' | head -c 12)..."
echo "=============================================="
echo ""

# Helper function to make authenticated requests
make_request() {
    local method="$1"
    local endpoint="$2"
    local expected_status="$3"
    local description="$4"

    echo -n "Testing: ${description}... "

    # Build auth header if credentials provided
    local auth_header=""
    if [ -n "${USERNAME}" ] && [ -n "${APP_PASSWORD}" ]; then
        # Application passwords need base64 encoding of username:password
        local auth_string="${USERNAME}:${APP_PASSWORD}"
        local auth_encoded=$(echo -n "${auth_string}" | base64)
        auth_header="-H \"Authorization: Basic ${auth_encoded}\""
    fi

    # Make the request
    local response=$(curl -s -w "\n%{http_code}" \
        -X "${method}" \
        "${API_BASE}${endpoint}" \
        -H "Content-Type: application/json" \
        -H "Authorization: Basic $(echo -n "${USERNAME}:${APP_PASSWORD}" | base64)" \
        2>/dev/null)

    # Extract status code (last line) and body (everything else)
    local status_code=$(echo "${response}" | tail -n1)
    local body=$(echo "${response}" | sed '$d')

    # Check status
    if [ "${status_code}" == "${expected_status}" ]; then
        echo -e "${GREEN}PASS${NC} (Status: ${status_code})"
        ((TESTS_PASSED++))
        return 0
    else
        echo -e "${RED}FAIL${NC} (Expected: ${expected_status}, Got: ${status_code})"
        echo "    Response: $(echo "${body}" | head -c 200)"
        ((TESTS_FAILED++))
        return 1
    fi
}

# Test unauthenticated request
test_unauthenticated() {
    local endpoint="$1"
    local expected_status="$2"
    local description="$3"

    echo -n "Testing: ${description}... "

    local response=$(curl -s -w "\n%{http_code}" \
        -X GET \
        "${API_BASE}${endpoint}" \
        -H "Content-Type: application/json" \
        2>/dev/null)

    local status_code=$(echo "${response}" | tail -n1)
    local body=$(echo "${response}" | sed '$d')

    if [ "${status_code}" == "${expected_status}" ]; then
        echo -e "${GREEN}PASS${NC} (Status: ${status_code})"
        ((TESTS_PASSED++))
        return 0
    else
        echo -e "${RED}FAIL${NC} (Expected: ${expected_status}, Got: ${status_code})"
        echo "    Response: $(echo "${body}" | head -c 200)"
        ((TESTS_FAILED++))
        return 1
    fi
}

# Test response contains expected JSON key
test_response_contains() {
    local endpoint="$1"
    local expected_key="$2"
    local description="$3"

    echo -n "Testing: ${description}... "

    local response=$(curl -s \
        -X GET \
        "${API_BASE}${endpoint}" \
        -H "Content-Type: application/json" \
        -H "Authorization: Basic $(echo -n "${USERNAME}:${APP_PASSWORD}" | base64)" \
        2>/dev/null)

    if echo "${response}" | grep -q "\"${expected_key}\""; then
        echo -e "${GREEN}PASS${NC} (Contains '${expected_key}')"
        ((TESTS_PASSED++))
        return 0
    else
        echo -e "${RED}FAIL${NC} (Missing '${expected_key}')"
        echo "    Response: $(echo "${response}" | head -c 200)"
        ((TESTS_FAILED++))
        return 1
    fi
}

echo "=== Health Check Endpoint (Public) ==="
echo ""

# Health endpoint should be publicly accessible
test_unauthenticated "/health" "200" "Health check without auth"

# Health check with auth should also work
make_request "GET" "/health" "200" "Health check with auth"

# Verify health response contains expected fields
test_response_contains "/health" "status" "Health response contains 'status'"
test_response_contains "/health" "version" "Health response contains 'version'"
test_response_contains "/health" "oxygen_active" "Health response contains 'oxygen_active'"

echo ""
echo "=== Authentication Tests ==="
echo ""

# Protected endpoints should reject unauthenticated requests
test_unauthenticated "/templates" "401" "Templates endpoint rejects unauthenticated request"
test_unauthenticated "/styles/global" "401" "Styles endpoint rejects unauthenticated request"

# Auth endpoint with credentials
make_request "POST" "/auth" "200" "Auth endpoint with valid credentials"

echo ""
echo "=== Templates Endpoint ==="
echo ""

# List templates with authentication
make_request "GET" "/templates" "200" "List templates with auth"

# Verify templates response structure
test_response_contains "/templates" "templates" "Templates response contains 'templates' array"
test_response_contains "/templates" "total" "Templates response contains 'total' count"
test_response_contains "/templates" "template_types" "Templates response contains 'template_types'"

# Template type filtering
make_request "GET" "/templates?template_type=header" "200" "Filter templates by type 'header'"
make_request "GET" "/templates?template_type=footer" "200" "Filter templates by type 'footer'"
make_request "GET" "/templates?template_type=invalid_type" "200" "Filter templates by invalid type (empty result)"

echo ""
echo "=== Global Styles Endpoint ==="
echo ""

# Global styles with authentication
make_request "GET" "/styles/global" "200" "Get global styles with auth"

# Test style category filtering
make_request "GET" "/styles/global?category=colors" "200" "Get global styles - colors category"
make_request "GET" "/styles/global?category=fonts" "200" "Get global styles - fonts category"
make_request "GET" "/styles/global?category=spacing" "200" "Get global styles - spacing category"
make_request "GET" "/styles/global?category=all" "200" "Get global styles - all categories"

# Verify global styles response structure
test_response_contains "/styles/global" "colors" "Global styles contains 'colors'"
test_response_contains "/styles/global" "fonts" "Global styles contains 'fonts'"
test_response_contains "/styles/global" "breakpoints" "Global styles contains 'breakpoints'"
test_response_contains "/styles/global" "_meta" "Global styles contains '_meta' metadata"

# Test optional parameters
make_request "GET" "/styles/global?include_variables=true" "200" "Get global styles with variables"
make_request "GET" "/styles/global?include_selectors=true" "200" "Get global styles with selectors"

echo ""
echo "=== Settings Endpoint ==="
echo ""

make_request "GET" "/settings" "200" "Get settings with auth"

echo ""
echo "=== Schema Endpoint ==="
echo ""

make_request "GET" "/schema" "200" "Get element schema with auth"

echo ""
echo "=== Pages Endpoint ==="
echo ""

make_request "GET" "/pages" "200" "List pages with auth"

echo ""
echo "=============================================="
echo "Test Results Summary"
echo "=============================================="
echo -e "Passed: ${GREEN}${TESTS_PASSED}${NC}"
echo -e "Failed: ${RED}${TESTS_FAILED}${NC}"
echo -e "Skipped: ${YELLOW}${TESTS_SKIPPED}${NC}"
echo ""

if [ ${TESTS_FAILED} -gt 0 ]; then
    echo -e "${RED}Some tests failed!${NC}"
    exit 1
else
    echo -e "${GREEN}All tests passed!${NC}"
    exit 0
fi
