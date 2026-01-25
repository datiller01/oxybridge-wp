#!/bin/bash
#
# Create Colabs Header Template via OxyBridge API
#
# This script creates the Colabs header template using the REST API.
# It requires authentication with WordPress application password.
#
# Usage: ./test-create-colabs-header.sh <site_url> <username> <app_password>
#
# Example:
#   ./test-create-colabs-header.sh https://pngna.local admin "xxxx xxxx xxxx xxxx xxxx xxxx"
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SITE_URL="${1:-https://pngna.local}"
USERNAME="${2:-admin}"
APP_PASSWORD="${3:-}"
API_BASE="${SITE_URL}/wp-json/oxybridge/v1"

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FIXTURES_DIR="${SCRIPT_DIR}/fixtures"

# Cleanup tracking
CREATED_TEMPLATE_ID=""

# Print functions
log_success() {
    echo -e "${GREEN}✓ SUCCESS${NC}: $1"
}

log_error() {
    echo -e "${RED}✗ ERROR${NC}: $1"
}

log_warn() {
    echo -e "${YELLOW}! WARN${NC}: $1"
}

log_info() {
    echo -e "${BLUE}  INFO${NC}: $1"
}

# Build auth header
get_auth() {
    if [ -n "$APP_PASSWORD" ]; then
        echo "-u ${USERNAME}:${APP_PASSWORD}"
    fi
}

# Check prerequisites
check_prereqs() {
    echo "============================================="
    echo "Create Colabs Header Template"
    echo "============================================="
    echo ""
    echo "Site URL: ${SITE_URL}"
    echo "API Base: ${API_BASE}"
    echo "Username: ${USERNAME}"
    echo ""

    # Check for curl
    if ! command -v curl &> /dev/null; then
        log_error "curl is required but not installed."
        exit 1
    fi

    # Check for jq
    if ! command -v jq &> /dev/null; then
        log_warn "jq not found. JSON parsing will be limited."
        HAS_JQ=false
    else
        HAS_JQ=true
    fi

    # Check authentication
    if [ -z "$APP_PASSWORD" ]; then
        log_error "No app password provided. This script requires authentication."
        echo ""
        echo "Usage: $0 <site_url> <username> <app_password>"
        echo ""
        echo "To create an application password:"
        echo "1. Go to WordPress Admin > Users > Your Profile"
        echo "2. Scroll to 'Application Passwords' section"
        echo "3. Enter a name and click 'Add New Application Password'"
        exit 1
    fi

    # Check fixture file exists
    FIXTURE_FILE="${FIXTURES_DIR}/colabs-header-tree.json"
    if [ ! -f "$FIXTURE_FILE" ]; then
        log_error "Fixture file not found: ${FIXTURE_FILE}"
        exit 1
    fi
    log_success "Fixture file found: colabs-header-tree.json"

    # Test API health
    echo ""
    echo "Testing API connection..."
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${API_BASE}/health" --insecure)
    if [ "$HTTP_CODE" = "200" ]; then
        log_success "API health check passed"
    else
        log_error "API health check failed (HTTP ${HTTP_CODE})"
        exit 1
    fi
}

# Create the template
create_template() {
    echo ""
    echo "=== Creating Colabs Header Template ==="
    echo ""

    FIXTURE_FILE="${FIXTURES_DIR}/colabs-header-tree.json"

    # Read the tree JSON
    TREE_JSON=$(cat "$FIXTURE_FILE")

    # Validate JSON
    if $HAS_JQ; then
        if ! echo "$TREE_JSON" | jq empty 2>/dev/null; then
            log_error "Invalid JSON in fixture file"
            exit 1
        fi
        log_info "Fixture JSON validated"
    fi

    # Build request body
    REQUEST_BODY=$(cat <<EOF
{
    "title": "Colabs Header",
    "type": "header",
    "tree": ${TREE_JSON}
}
EOF
)

    # Make the API request
    log_info "Sending POST request to /templates..."

    RESPONSE=$(curl -s -X POST "${API_BASE}/templates" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${REQUEST_BODY}" \
        --insecure)

    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/templates" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${REQUEST_BODY}" \
        --insecure)

    echo ""

    if [ "$HTTP_CODE" = "201" ]; then
        log_success "Template created successfully (HTTP 201)"

        if $HAS_JQ; then
            CREATED_TEMPLATE_ID=$(echo "$RESPONSE" | jq -r '.template.id // empty')
            TEMPLATE_TITLE=$(echo "$RESPONSE" | jq -r '.template.title // empty')
            TEMPLATE_TYPE=$(echo "$RESPONSE" | jq -r '.template.type // empty')
            EDIT_URL=$(echo "$RESPONSE" | jq -r '.template.edit_url // empty')
            ELEMENT_COUNT=$(echo "$RESPONSE" | jq -r '.element_count // 0')
            TREE_SAVED=$(echo "$RESPONSE" | jq -r '.tree_saved // false')
            CSS_REGEN=$(echo "$RESPONSE" | jq -r '.css_regenerated // false')

            echo ""
            log_info "Template ID: ${CREATED_TEMPLATE_ID}"
            log_info "Title: ${TEMPLATE_TITLE}"
            log_info "Type: ${TEMPLATE_TYPE}"
            log_info "Element count: ${ELEMENT_COUNT}"
            log_info "Tree saved: ${TREE_SAVED}"
            log_info "CSS regenerated: ${CSS_REGEN}"
            echo ""
            log_info "Edit URL: ${EDIT_URL}"
        else
            echo "Response: ${RESPONSE}"
        fi
    else
        log_error "Template creation failed (HTTP ${HTTP_CODE})"
        echo ""
        echo "Response:"
        if $HAS_JQ; then
            echo "$RESPONSE" | jq .
        else
            echo "$RESPONSE"
        fi
        exit 1
    fi
}

# Verify template
verify_template() {
    if [ -z "$CREATED_TEMPLATE_ID" ]; then
        log_warn "No template ID to verify"
        return
    fi

    echo ""
    echo "=== Verifying Template ==="
    echo ""

    RESPONSE=$(curl -s "${API_BASE}/templates/${CREATED_TEMPLATE_ID}" \
        $(get_auth) \
        --insecure)

    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${API_BASE}/templates/${CREATED_TEMPLATE_ID}" \
        $(get_auth) \
        --insecure)

    if [ "$HTTP_CODE" = "200" ]; then
        log_success "Template retrieved successfully"

        if $HAS_JQ; then
            HAS_TREE=$(echo "$RESPONSE" | jq -r 'if .tree.root then "Yes" else "No" end')
            HAS_CHILDREN=$(echo "$RESPONSE" | jq -r 'if .tree.root.children | length > 0 then "Yes" else "No" end')

            log_info "Has tree: ${HAS_TREE}"
            log_info "Has children: ${HAS_CHILDREN}"
        fi
    else
        log_error "Failed to verify template (HTTP ${HTTP_CODE})"
    fi
}

# Print summary
print_summary() {
    echo ""
    echo "============================================="
    echo "Summary"
    echo "============================================="
    echo ""

    if [ -n "$CREATED_TEMPLATE_ID" ]; then
        log_success "Colabs Header template created successfully!"
        echo ""
        echo "Template ID: ${CREATED_TEMPLATE_ID}"
        echo "Edit URL: ${SITE_URL}/?breakdance_iframe=true&id=${CREATED_TEMPLATE_ID}"
        echo ""
        echo "To view in Oxygen Builder:"
        echo "  ${EDIT_URL:-${SITE_URL}/?breakdance_iframe=true&id=${CREATED_TEMPLATE_ID}}"
    else
        log_error "Failed to create template"
        exit 1
    fi
}

# Main execution
main() {
    check_prereqs
    create_template
    verify_template
    print_summary
}

# Run main function
main
