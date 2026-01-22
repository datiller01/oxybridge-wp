# Oxybridge Test Suite

This directory contains test scripts for verifying the Oxybridge REST API functionality.

## Test Files

### End-to-End Tests

#### `test-e2e-page-workflow.php`
Complete E2E test for the page creation and Oxygen design workflow.

**Requires:** WP-CLI

**Tests:**
1. Create page with Oxygen design (POST /pages)
2. Verify document saved (GET /documents/{id})
3. Verify page renders (GET /render/{id})
4. Verify CSS cache generated
5. Test clone functionality (POST /clone/{id})
6. Test validation endpoint (POST /validate)

**Usage:**
```bash
wp eval-file wp-content/plugins/oxybridge-wp/tests/test-e2e-page-workflow.php
```

#### `test-e2e-api.sh`
Shell script for HTTP-based E2E testing using curl.

**Requires:** curl, jq (optional for better JSON parsing)

**Tests:**
- Health check endpoint
- Info endpoint
- List pages endpoint
- Discovery endpoints (breakpoints, colors, variables, fonts, classes, schema)
- Validate tree endpoint
- Create page with design (authenticated)
- Read document (authenticated)
- Render document (authenticated)
- Clone page (authenticated)

**Usage:**
```bash
# Without authentication (limited tests)
./wp-content/plugins/oxybridge-wp/tests/test-e2e-api.sh http://localhost

# With authentication (full tests)
./wp-content/plugins/oxybridge-wp/tests/test-e2e-api.sh http://localhost admin "xxxx xxxx xxxx xxxx xxxx xxxx"
```

### Unit/Integration Tests

#### `test-rest-api.sh`
Shell script for testing individual REST API endpoints.

**Usage:**
```bash
./wp-content/plugins/oxybridge-wp/tests/test-rest-api.sh
```

#### `test-error-responses.php`
Comprehensive test for verifying all REST API endpoints return proper WP_Error responses.

**Requires:** WP-CLI

**Tests:**
1. **404 Not Found Errors:**
   - Non-existent document/template IDs
   - Posts without Oxygen content
   - Non-existent setting keys
   - Non-existent clone sources

2. **400 Bad Request Errors:**
   - Invalid post types for page creation
   - Invalid template types
   - Invalid parent posts for hierarchical types
   - Non-template posts accessed via template endpoint

3. **403 Forbidden Errors:**
   - Subscriber users accessing protected endpoints
   - Users without edit_posts capability

4. **Validation Errors:**
   - Null tree parameter
   - Invalid JSON strings
   - Trees missing root element
   - Trees with invalid structure

**Usage:**
```bash
wp eval-file wp-content/plugins/oxybridge-wp/tests/test-error-responses.php
```

### Verification Scripts

#### `verify-css-cache.php`
Verifies CSS regeneration works correctly for a specific post.

**Usage:**
```bash
wp eval-file wp-content/plugins/oxybridge-wp/tests/verify-css-cache.php -- <post_id>
```

#### `verify-css-regeneration.sh`
Shell script for verifying CSS regeneration via API.

**Usage:**
```bash
./wp-content/plugins/oxybridge-wp/tests/verify-css-regeneration.sh <post_id>
```

## Prerequisites

1. **WordPress Installation** with Oxybridge and Oxygen/Breakdance plugins activated
2. **WP-CLI** installed for PHP-based tests
3. **curl** for shell-based tests
4. **jq** (optional) for better JSON parsing in shell tests

## Authentication

For tests requiring authentication, you'll need:

1. Create a WordPress Application Password:
   - Go to Users > Your Profile in WordPress admin
   - Scroll to "Application Passwords"
   - Create a new password and save it

2. Pass credentials to test scripts as shown in usage examples

## Running All Tests

```bash
# Full E2E test via WP-CLI (recommended)
wp eval-file wp-content/plugins/oxybridge-wp/tests/test-e2e-page-workflow.php

# Error response verification test
wp eval-file wp-content/plugins/oxybridge-wp/tests/test-error-responses.php

# API tests via curl
./wp-content/plugins/oxybridge-wp/tests/test-e2e-api.sh http://your-site.local admin "your-app-password"
```

## Test Output

Tests will output:
- ✓ PASS - Test passed
- ✗ FAIL - Test failed with error message
- ! WARN - Warning (non-critical issue)
- INFO - Informational message

A summary at the end shows total passed/failed tests.

## Cleanup

All test scripts automatically clean up created test data (pages, templates) after running.
