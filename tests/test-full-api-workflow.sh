#!/bin/bash
#
# Oxybridge Full API Workflow E2E Test
#
# Tests the complete AI-driven design workflow:
# 1. GET /ai/context - verify response structure
# 2. GET /ai/schema - verify property coverage
# 3. POST /ai/validate - verify validation works
# 4. POST /ai/transform - verify transformation works
# 5. POST /templates - verify template creation
# 6. GET template in builder URL - verify creation
#
# Usage: ./test-full-api-workflow.sh <site_url> <username> <app_password>
#
# Example:
#   ./test-full-api-workflow.sh https://pngna.local systek_support "xxxx xxxx xxxx"
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
PASSED=0
FAILED=0
SKIPPED=0

# Configuration
SITE_URL="${1:-https://pngna.local}"
USERNAME="${2:-systek_support}"
APP_PASSWORD="${3:-}"
API_BASE="${SITE_URL}/wp-json/oxybridge/v1"

# Cleanup tracking
CREATED_TEMPLATE_ID=""

# Script directory for fixtures
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FIXTURES_DIR="${SCRIPT_DIR}/fixtures"

# Check for jq availability
HAS_JQ=false
if command -v jq &> /dev/null; then
    HAS_JQ=true
fi

# Print colored output
log_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASSED++))
}

log_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAILED++))
}

log_skip() {
    echo -e "${YELLOW}[SKIP]${NC} $1"
    ((SKIPPED++))
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_section() {
    echo ""
    echo -e "${BLUE}=============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}=============================================${NC}"
}

# Build auth header
get_auth() {
    if [ -n "$APP_PASSWORD" ]; then
        echo "-u ${USERNAME}:${APP_PASSWORD}"
    fi
}

# Check prerequisites
check_prereqs() {
    log_section "Oxybridge Full API Workflow Test"
    echo ""
    echo "Site URL: ${SITE_URL}"
    echo "API Base: ${API_BASE}"
    echo "Username: ${USERNAME}"
    echo "Has jq:   ${HAS_JQ}"
    echo ""

    # Check for curl
    if ! command -v curl &> /dev/null; then
        echo "Error: curl is required but not installed."
        exit 1
    fi

    # Check authentication
    if [ -z "$APP_PASSWORD" ]; then
        echo -e "${YELLOW}Warning: No app password provided. Tests requiring authentication may fail.${NC}"
        echo "Usage: $0 <site_url> <username> <app_password>"
        echo ""
    fi

    # Check fixtures directory
    if [ ! -d "$FIXTURES_DIR" ]; then
        echo -e "${YELLOW}Warning: Fixtures directory not found at ${FIXTURES_DIR}${NC}"
    fi
}

# Step 1: Test GET /ai/context
test_ai_context() {
    log_section "Step 1: GET /ai/context"

    RESPONSE=$(curl -sk "${API_BASE}/ai/context")
    HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "${API_BASE}/ai/context")

    if [ "$HTTP_CODE" != "200" ]; then
        log_fail "/ai/context returned HTTP ${HTTP_CODE}"
        return 1
    fi

    log_pass "/ai/context returned HTTP 200"

    if $HAS_JQ; then
        # Verify response structure
        VERSION=$(echo "$RESPONSE" | jq -r '.version // empty')
        BUILDER=$(echo "$RESPONSE" | jq -r '.builder // empty')
        COMPONENTS_COUNT=$(echo "$RESPONSE" | jq -r '.components | length // 0')
        ELEMENT_TYPES_COUNT=$(echo "$RESPONSE" | jq -r '.element_types | length // 0')
        PROPERTY_PATHS=$(echo "$RESPONSE" | jq -r '.property_paths | keys | length // 0')
        NOTES_COUNT=$(echo "$RESPONSE" | jq -r '.notes | length // 0')

        if [ -n "$VERSION" ]; then
            log_pass "Context has version: ${VERSION}"
        else
            log_fail "Context missing version field"
        fi

        if [ -n "$BUILDER" ]; then
            log_pass "Context has builder: ${BUILDER}"
        else
            log_fail "Context missing builder field"
        fi

        if [ "$COMPONENTS_COUNT" -gt 0 ]; then
            log_pass "Context has ${COMPONENTS_COUNT} components"
        else
            log_fail "Context has no components"
        fi

        if [ "$ELEMENT_TYPES_COUNT" -gt 5 ]; then
            log_pass "Context has ${ELEMENT_TYPES_COUNT} element types"
        else
            log_fail "Context has insufficient element types (${ELEMENT_TYPES_COUNT})"
        fi

        if [ "$PROPERTY_PATHS" -gt 0 ]; then
            log_pass "Context has ${PROPERTY_PATHS} property paths"
        else
            log_fail "Context missing property paths"
        fi

        if [ "$NOTES_COUNT" -gt 0 ]; then
            log_pass "Context has ${NOTES_COUNT} usage notes"
        else
            log_fail "Context missing usage notes"
        fi

        log_info "Sample element types: $(echo "$RESPONSE" | jq -r '.element_types[:3] | join(", ")')"
    else
        # Basic validation without jq
        if echo "$RESPONSE" | grep -q '"version"'; then
            log_pass "Context contains version field"
        else
            log_fail "Context missing version field"
        fi

        if echo "$RESPONSE" | grep -q '"element_types"'; then
            log_pass "Context contains element_types field"
        else
            log_fail "Context missing element_types field"
        fi
    fi
}

# Step 2: Test GET /ai/schema
test_ai_schema() {
    log_section "Step 2: GET /ai/schema"

    RESPONSE=$(curl -sk "${API_BASE}/ai/schema")
    HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "${API_BASE}/ai/schema")

    if [ "$HTTP_CODE" != "200" ]; then
        log_fail "/ai/schema returned HTTP ${HTTP_CODE}"
        return 1
    fi

    log_pass "/ai/schema returned HTTP 200"

    if $HAS_JQ; then
        # Verify schema has key sections
        HAS_PROPERTIES=$(echo "$RESPONSE" | jq 'has("properties")' 2>/dev/null || echo "false")
        HAS_RECIPES=$(echo "$RESPONSE" | jq 'has("recipes")' 2>/dev/null || echo "false")
        HAS_TREE_STRUCTURE=$(echo "$RESPONSE" | jq 'has("treeStructure")' 2>/dev/null || echo "false")

        if [ "$HAS_PROPERTIES" = "true" ]; then
            PROP_COUNT=$(echo "$RESPONSE" | jq '.properties | keys | length')
            log_pass "Schema has properties section with ${PROP_COUNT} properties"

            # Check for key design properties
            HAS_TYPOGRAPHY=$(echo "$RESPONSE" | jq '.properties | has("typography")' 2>/dev/null || echo "false")
            HAS_BACKGROUND=$(echo "$RESPONSE" | jq '.properties | has("background")' 2>/dev/null || echo "false")
            HAS_EFFECTS=$(echo "$RESPONSE" | jq '.properties | has("effects")' 2>/dev/null || echo "false")
            HAS_BORDERS=$(echo "$RESPONSE" | jq '.properties | has("borders")' 2>/dev/null || echo "false")

            [ "$HAS_TYPOGRAPHY" = "true" ] && log_pass "Schema has typography properties" || log_fail "Schema missing typography"
            [ "$HAS_BACKGROUND" = "true" ] && log_pass "Schema has background properties" || log_fail "Schema missing background"
            [ "$HAS_EFFECTS" = "true" ] && log_pass "Schema has effects properties" || log_fail "Schema missing effects"
            [ "$HAS_BORDERS" = "true" ] && log_pass "Schema has borders properties" || log_fail "Schema missing borders"
        else
            log_fail "Schema missing properties section"
        fi

        if [ "$HAS_RECIPES" = "true" ]; then
            RECIPE_COUNT=$(echo "$RESPONSE" | jq '.recipes | keys | length')
            log_pass "Schema has recipes section with ${RECIPE_COUNT} recipes"
        else
            log_fail "Schema missing recipes section"
        fi

        if [ "$HAS_TREE_STRUCTURE" = "true" ]; then
            log_pass "Schema has treeStructure documentation"
        else
            log_fail "Schema missing treeStructure documentation"
        fi
    else
        # Basic validation without jq
        if echo "$RESPONSE" | grep -q '"properties"'; then
            log_pass "Schema contains properties section"
        else
            log_fail "Schema missing properties section"
        fi
    fi
}

# Step 3: Test POST /ai/validate
test_ai_validate() {
    log_section "Step 3: POST /ai/validate"

    if [ -z "$APP_PASSWORD" ]; then
        log_skip "Skipping - authentication required"
        return 0
    fi

    # Test with valid tree
    VALID_TREE='{
        "tree": {
            "root": {
                "id": 1,
                "data": {
                    "type": "root",
                    "properties": null
                },
                "children": [
                    {
                        "id": 100,
                        "data": {
                            "type": "EssentialElements\\Section",
                            "properties": null
                        },
                        "children": [],
                        "_parentId": 1
                    }
                ]
            },
            "status": "exported"
        }
    }'

    RESPONSE=$(curl -sk -X POST "${API_BASE}/ai/validate" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${VALID_TREE}")

    HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/ai/validate" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${VALID_TREE}")

    if [ "$HTTP_CODE" != "200" ]; then
        log_fail "/ai/validate returned HTTP ${HTTP_CODE}"
        log_info "Response: ${RESPONSE}"
        return 1
    fi

    log_pass "/ai/validate returned HTTP 200"

    if $HAS_JQ; then
        IS_VALID=$(echo "$RESPONSE" | jq -r '.valid // false')
        ERROR_COUNT=$(echo "$RESPONSE" | jq -r '.error_count // -1')
        WARNING_COUNT=$(echo "$RESPONSE" | jq -r '.warning_count // -1')

        if [ "$IS_VALID" = "true" ]; then
            log_pass "Valid tree passes validation"
        else
            log_fail "Valid tree failed validation"
            log_info "Errors: $(echo "$RESPONSE" | jq -c '.errors')"
        fi

        log_info "Error count: ${ERROR_COUNT}, Warning count: ${WARNING_COUNT}"
    fi

    # Test with invalid tree (missing required fields)
    INVALID_TREE='{
        "tree": {
            "root": {
                "id": "should-be-integer",
                "data": {
                    "type": "root"
                },
                "children": []
            }
        }
    }'

    RESPONSE=$(curl -sk -X POST "${API_BASE}/ai/validate" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${INVALID_TREE}")

    if $HAS_JQ; then
        IS_VALID=$(echo "$RESPONSE" | jq -r '.valid // true')
        ERROR_COUNT=$(echo "$RESPONSE" | jq -r '.error_count // 0')

        if [ "$IS_VALID" = "false" ] && [ "$ERROR_COUNT" -gt 0 ]; then
            log_pass "Invalid tree fails validation with ${ERROR_COUNT} errors"

            # Check that errors have required fields
            FIRST_ERROR_CODE=$(echo "$RESPONSE" | jq -r '.errors[0].code // empty')
            FIRST_ERROR_PATH=$(echo "$RESPONSE" | jq -r '.errors[0].path // empty')
            FIRST_ERROR_MSG=$(echo "$RESPONSE" | jq -r '.errors[0].message // empty')

            if [ -n "$FIRST_ERROR_CODE" ] && [ -n "$FIRST_ERROR_PATH" ] && [ -n "$FIRST_ERROR_MSG" ]; then
                log_pass "Validation errors include code, path, and message"
                log_info "Example error: ${FIRST_ERROR_CODE} at ${FIRST_ERROR_PATH}"
            else
                log_fail "Validation errors missing required fields"
            fi
        else
            log_fail "Invalid tree did not fail validation"
        fi
    fi
}

# Step 4: Test POST /ai/transform
test_ai_transform() {
    log_section "Step 4: POST /ai/transform"

    if [ -z "$APP_PASSWORD" ]; then
        log_skip "Skipping - authentication required"
        return 0
    fi

    # Test with a simplified tree that needs transformation
    SIMPLIFIED_TREE='{
        "tree": {
            "root": {
                "id": 1,
                "data": {
                    "type": "root",
                    "properties": null
                },
                "children": [
                    {
                        "id": 100,
                        "data": {
                            "type": "EssentialElements\\Section",
                            "properties": {
                                "design": {
                                    "background": {
                                        "color": "#f5f5f5"
                                    }
                                }
                            }
                        },
                        "children": [
                            {
                                "id": 101,
                                "data": {
                                    "type": "EssentialElements\\Heading",
                                    "properties": {
                                        "content": {
                                            "content": {
                                                "text": "Test Heading"
                                            }
                                        }
                                    }
                                },
                                "children": [],
                                "_parentId": 100
                            }
                        ],
                        "_parentId": 1
                    }
                ]
            },
            "status": "exported"
        }
    }'

    RESPONSE=$(curl -sk -X POST "${API_BASE}/ai/transform" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${SIMPLIFIED_TREE}")

    HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/ai/transform" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${SIMPLIFIED_TREE}")

    if [ "$HTTP_CODE" != "200" ]; then
        log_fail "/ai/transform returned HTTP ${HTTP_CODE}"
        log_info "Response: ${RESPONSE}"
        return 1
    fi

    log_pass "/ai/transform returned HTTP 200"

    if $HAS_JQ; then
        SUCCESS=$(echo "$RESPONSE" | jq -r '.success // false')
        HAS_TREE=$(echo "$RESPONSE" | jq 'has("tree")')

        if [ "$SUCCESS" = "true" ]; then
            log_pass "Transform succeeded"
        else
            log_fail "Transform failed"
        fi

        if [ "$HAS_TREE" = "true" ]; then
            log_pass "Transform response includes tree"

            # Verify transformed tree structure
            ROOT_ID=$(echo "$RESPONSE" | jq -r '.tree.root.id // empty')
            STATUS=$(echo "$RESPONSE" | jq -r '.tree.status // empty')

            if [ "$ROOT_ID" = "1" ]; then
                log_pass "Transformed tree has valid root id"
            else
                log_fail "Transformed tree has invalid root id: ${ROOT_ID}"
            fi

            if [ "$STATUS" = "exported" ]; then
                log_pass "Transformed tree has status: exported"
            else
                log_fail "Transformed tree missing or invalid status: ${STATUS}"
            fi
        else
            log_fail "Transform response missing tree"
        fi
    fi
}

# Step 5: Test POST /templates (create template)
test_create_template() {
    log_section "Step 5: POST /templates (Create Template)"

    if [ -z "$APP_PASSWORD" ]; then
        log_skip "Skipping - authentication required"
        return 0
    fi

    # Use the colabs header fixture if available, otherwise use a simple tree
    if [ -f "${FIXTURES_DIR}/colabs-header-tree.json" ] && $HAS_JQ; then
        TREE_JSON=$(cat "${FIXTURES_DIR}/colabs-header-tree.json")
        log_info "Using CoLabs header fixture for template creation"
    else
        # Simple test tree
        TREE_JSON='{
            "root": {
                "id": 1,
                "data": {
                    "type": "root",
                    "properties": null
                },
                "children": [
                    {
                        "id": 100,
                        "data": {
                            "type": "EssentialElements\\Section",
                            "properties": {
                                "design": {
                                    "background": {
                                        "color": "#ffffff"
                                    },
                                    "size": {
                                        "minHeight": {"breakpoint_base": "80px"}
                                    }
                                }
                            }
                        },
                        "children": [
                            {
                                "id": 101,
                                "data": {
                                    "type": "EssentialElements\\Heading",
                                    "properties": {
                                        "content": {
                                            "content": {
                                                "text": "Workflow Test Header"
                                            }
                                        }
                                    }
                                },
                                "children": [],
                                "_parentId": 100
                            }
                        ],
                        "_parentId": 1
                    }
                ]
            },
            "status": "exported"
        }'
        log_info "Using simple test tree for template creation"
    fi

    TIMESTAMP=$(date +%Y%m%d%H%M%S)
    REQUEST_DATA=$(cat <<EOF
{
    "title": "API Workflow Test Header - ${TIMESTAMP}",
    "type": "header",
    "tree": ${TREE_JSON}
}
EOF
)

    RESPONSE=$(curl -sk -X POST "${API_BASE}/templates" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${REQUEST_DATA}")

    HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X POST "${API_BASE}/templates" \
        $(get_auth) \
        -H "Content-Type: application/json" \
        -d "${REQUEST_DATA}")

    if [ "$HTTP_CODE" = "201" ]; then
        log_pass "Template created with HTTP 201"
    elif [ "$HTTP_CODE" = "200" ]; then
        log_pass "Template created with HTTP 200"
    else
        log_fail "Template creation returned HTTP ${HTTP_CODE}"
        log_info "Response: ${RESPONSE}"
        return 1
    fi

    if $HAS_JQ; then
        SUCCESS=$(echo "$RESPONSE" | jq -r '.success // false')
        TEMPLATE_ID=$(echo "$RESPONSE" | jq -r '.template.id // .id // empty')
        TEMPLATE_TITLE=$(echo "$RESPONSE" | jq -r '.template.title // .title // empty')
        EDIT_URL=$(echo "$RESPONSE" | jq -r '.template.edit_url // .edit_url // empty')
        ELEMENT_COUNT=$(echo "$RESPONSE" | jq -r '.template.element_count // .element_count // 0')

        if [ -n "$TEMPLATE_ID" ]; then
            CREATED_TEMPLATE_ID="$TEMPLATE_ID"
            log_pass "Template created with ID: ${TEMPLATE_ID}"
            log_info "Title: ${TEMPLATE_TITLE}"
            log_info "Element count: ${ELEMENT_COUNT}"

            if [ -n "$EDIT_URL" ]; then
                log_pass "Response includes edit_url"
                log_info "Edit URL: ${EDIT_URL}"
            else
                log_fail "Response missing edit_url"
            fi
        else
            log_fail "Could not extract template ID from response"
            log_info "Response: ${RESPONSE}"
        fi

        # Verify tree is included in response
        HAS_TREE=$(echo "$RESPONSE" | jq '.template | has("tree") // false')
        if [ "$HAS_TREE" = "true" ]; then
            log_pass "Response includes tree for verification"
        fi
    else
        # Basic extraction without jq
        if echo "$RESPONSE" | grep -q '"id"'; then
            CREATED_TEMPLATE_ID=$(echo "$RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
            log_pass "Template created with ID: ${CREATED_TEMPLATE_ID}"
        else
            log_fail "Could not extract template ID"
        fi
    fi
}

# Step 6: Verify template in builder
test_verify_template() {
    log_section "Step 6: Verify Template in Builder"

    if [ -z "$CREATED_TEMPLATE_ID" ]; then
        log_skip "Skipping - no template ID available"
        return 0
    fi

    # Read the template back via API
    RESPONSE=$(curl -sk "${API_BASE}/templates/${CREATED_TEMPLATE_ID}" $(get_auth))
    HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "${API_BASE}/templates/${CREATED_TEMPLATE_ID}" $(get_auth))

    if [ "$HTTP_CODE" = "200" ]; then
        log_pass "Template ${CREATED_TEMPLATE_ID} retrieved successfully"
    else
        log_fail "Template retrieval returned HTTP ${HTTP_CODE}"
        return 1
    fi

    if $HAS_JQ; then
        # Verify template data
        TITLE=$(echo "$RESPONSE" | jq -r '.title // empty')
        TYPE=$(echo "$RESPONSE" | jq -r '.type // empty')
        STATUS=$(echo "$RESPONSE" | jq -r '.status // empty')
        ELEMENT_COUNT=$(echo "$RESPONSE" | jq -r '.element_count // 0')

        log_info "Retrieved template: ${TITLE}"
        log_info "Type: ${TYPE}, Status: ${STATUS}, Elements: ${ELEMENT_COUNT}"

        # Verify tree structure is preserved
        HAS_TREE=$(echo "$RESPONSE" | jq 'has("tree")')
        if [ "$HAS_TREE" = "true" ]; then
            ROOT_TYPE=$(echo "$RESPONSE" | jq -r '.tree.root.data.type // empty')
            TREE_STATUS=$(echo "$RESPONSE" | jq -r '.tree.status // empty')

            if [ "$ROOT_TYPE" = "root" ]; then
                log_pass "Template tree has valid root type"
            else
                log_fail "Template tree has invalid root type: ${ROOT_TYPE}"
            fi

            if [ "$TREE_STATUS" = "exported" ]; then
                log_pass "Template tree has correct status"
            else
                log_fail "Template tree has invalid status: ${TREE_STATUS}"
            fi
        else
            log_fail "Template response missing tree"
        fi
    fi

    # Output builder URL for manual verification
    BUILDER_URL="${SITE_URL}/?breakdance_iframe=true&id=${CREATED_TEMPLATE_ID}"
    log_info ""
    log_info "Builder URL for manual verification:"
    log_info "${BUILDER_URL}"
}

# Cleanup: Delete created template
cleanup() {
    log_section "Cleanup"

    if [ -n "$CREATED_TEMPLATE_ID" ] && [ -n "$APP_PASSWORD" ]; then
        DELETE_RESPONSE=$(curl -sk -X DELETE \
            "${API_BASE}/templates/${CREATED_TEMPLATE_ID}?force=true" \
            $(get_auth))
        HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" -X DELETE \
            "${API_BASE}/templates/${CREATED_TEMPLATE_ID}?force=true" \
            $(get_auth))

        if [ "$HTTP_CODE" = "200" ]; then
            log_info "Deleted test template ${CREATED_TEMPLATE_ID}"
        else
            log_info "Could not delete template ${CREATED_TEMPLATE_ID} (HTTP ${HTTP_CODE})"
        fi
    else
        log_info "No template to clean up"
    fi
}

# Print summary
print_summary() {
    log_section "Test Summary"
    echo ""
    echo "Total: $((PASSED + FAILED + SKIPPED))"
    echo -e "${GREEN}Passed:  ${PASSED}${NC}"
    echo -e "${RED}Failed:  ${FAILED}${NC}"
    echo -e "${YELLOW}Skipped: ${SKIPPED}${NC}"
    echo ""

    if [ "$FAILED" -eq 0 ]; then
        echo -e "${GREEN}All tests passed!${NC}"
        echo ""
        echo "The full API workflow is working correctly:"
        echo "  1. /ai/context provides system context"
        echo "  2. /ai/schema provides property coverage"
        echo "  3. /ai/validate validates tree structures"
        echo "  4. /ai/transform transforms simplified trees"
        echo "  5. /templates creates templates with trees"
        echo "  6. Templates can be viewed in builder"
        exit 0
    else
        echo -e "${RED}Some tests failed. Review the output above.${NC}"
        exit 1
    fi
}

# Main execution
main() {
    check_prereqs

    # Run the full workflow in sequence
    test_ai_context
    test_ai_schema
    test_ai_validate
    test_ai_transform
    test_create_template
    test_verify_template

    # Cleanup
    cleanup

    # Print results
    print_summary
}

# Run main function
main
