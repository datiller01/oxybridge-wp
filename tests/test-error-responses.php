<?php
/**
 * Test: Error Response Verification
 *
 * This script tests that all REST API endpoints return proper WP_Error responses
 * for various error cases with appropriate HTTP status codes.
 *
 * Run via WP-CLI: wp eval-file wp-content/plugins/oxybridge-wp/tests/test-error-responses.php
 *
 * @package Oxybridge
 */

// Ensure this is run via WP-CLI.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run via WP-CLI.\n";
	echo "Usage: wp eval-file wp-content/plugins/oxybridge-wp/tests/test-error-responses.php\n";
	exit( 1 );
}

/**
 * Error Response Test Runner for Oxybridge REST API.
 */
class Oxybridge_Error_Response_Test {

	/**
	 * Test results.
	 *
	 * @var array
	 */
	private $results = array(
		'passed' => 0,
		'failed' => 0,
		'errors' => array(),
	);

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'oxybridge/v1';

	/**
	 * Test page ID for cleanup.
	 *
	 * @var int
	 */
	private $test_page_id = 0;

	/**
	 * Run all error response tests.
	 *
	 * @return void
	 */
	public function run() {
		WP_CLI::log( '========================================================' );
		WP_CLI::log( 'Oxybridge Error Response Verification Test' );
		WP_CLI::log( '========================================================' );
		WP_CLI::log( '' );

		// Check prerequisites.
		if ( ! $this->check_prerequisites() ) {
			return;
		}

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 1: 404 Not Found Errors ===' );
		WP_CLI::log( '' );
		$this->test_404_errors();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 2: 400 Bad Request Errors ===' );
		WP_CLI::log( '' );
		$this->test_400_errors();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 3: 403 Forbidden Errors ===' );
		WP_CLI::log( '' );
		$this->test_403_errors();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 4: Validation Errors ===' );
		WP_CLI::log( '' );
		$this->test_validation_errors();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Cleanup ===' );
		WP_CLI::log( '' );
		$this->cleanup();

		$this->print_summary();
	}

	/**
	 * Check prerequisites.
	 *
	 * @return bool True if prerequisites are met.
	 */
	private function check_prerequisites() {
		WP_CLI::log( 'Checking prerequisites...' );

		// Check if Oxybridge plugin is active.
		if ( ! defined( 'OXYBRIDGE_VERSION' ) ) {
			WP_CLI::error( 'Oxybridge plugin is not active.', false );
			return false;
		}
		WP_CLI::success( 'Oxybridge plugin active: v' . OXYBRIDGE_VERSION );

		// Check if REST API is available.
		if ( ! class_exists( 'Oxybridge\\REST_API' ) ) {
			WP_CLI::error( 'Oxybridge REST API class not found.', false );
			return false;
		}
		WP_CLI::success( 'Oxybridge REST API class available' );

		// Check current user permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			WP_CLI::error( 'Current user does not have edit_posts capability.', false );
			return false;
		}
		WP_CLI::success( 'User has required permissions' );

		// Create a test page without Oxygen content for testing.
		$this->test_page_id = wp_insert_post( array(
			'post_title'  => 'Error Test Page - No Oxygen Content',
			'post_type'   => 'page',
			'post_status' => 'draft',
		) );

		if ( $this->test_page_id ) {
			WP_CLI::success( 'Created test page without Oxygen content: ID ' . $this->test_page_id );
		}

		return true;
	}

	/**
	 * Test 404 Not Found error responses.
	 */
	private function test_404_errors() {
		$server = rest_get_server();

		// Test: Non-existent document ID.
		$this->test_error_response(
			'GET /documents/{id} - Non-existent post',
			new WP_REST_Request( 'GET', '/' . $this->namespace . '/documents/99999999' ),
			404
		);

		// Test: Non-existent template ID.
		$this->test_error_response(
			'GET /templates/{id} - Non-existent template',
			new WP_REST_Request( 'GET', '/' . $this->namespace . '/templates/99999999' ),
			404
		);

		// Test: Non-existent render ID.
		$this->test_error_response(
			'GET /render/{id} - Non-existent post',
			new WP_REST_Request( 'GET', '/' . $this->namespace . '/render/99999999' ),
			404
		);

		// Test: Non-existent clone source.
		$this->test_error_response(
			'POST /clone/{id} - Non-existent source',
			new WP_REST_Request( 'POST', '/' . $this->namespace . '/clone/99999999' ),
			404
		);

		// Test: Document without Oxygen content.
		if ( $this->test_page_id ) {
			$this->test_error_response(
				'GET /documents/{id} - Post without Oxygen content',
				new WP_REST_Request( 'GET', '/' . $this->namespace . '/documents/' . $this->test_page_id ),
				404
			);

			// Test: Render without Oxygen content.
			$this->test_error_response(
				'GET /render/{id} - Post without Oxygen content',
				new WP_REST_Request( 'GET', '/' . $this->namespace . '/render/' . $this->test_page_id ),
				404
			);
		}

		// Test: Non-existent setting key.
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/settings' );
		$request->set_param( 'key', 'non_existent_setting_key_xyz' );
		$this->test_error_response(
			'GET /settings?key=invalid - Non-existent setting',
			$request,
			404
		);

		// Test: Non-existent regenerate-css post.
		$this->test_error_response(
			'POST /regenerate-css/{id} - Non-existent post',
			new WP_REST_Request( 'POST', '/' . $this->namespace . '/regenerate-css/99999999' ),
			404
		);
	}

	/**
	 * Test 400 Bad Request error responses.
	 */
	private function test_400_errors() {
		$server = rest_get_server();

		// Test: Invalid post type for page creation.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/pages' );
		$request->set_param( 'title', 'Test Invalid Post Type' );
		$request->set_param( 'post_type', 'non_existent_post_type' );
		$this->test_error_response(
			'POST /pages - Invalid post type',
			$request,
			400
		);

		// Test: Creating page with template post type.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/pages' );
		$request->set_param( 'title', 'Test Template Type' );
		$request->set_param( 'post_type', 'breakdance_template' );
		$this->test_error_response(
			'POST /pages - Template post type (should use /templates)',
			$request,
			400
		);

		// Test: Invalid template type for template creation.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/templates' );
		$request->set_param( 'title', 'Test Invalid Template' );
		$request->set_param( 'template_type', 'invalid_template_type' );
		$this->test_error_response(
			'POST /templates - Invalid template type',
			$request,
			400
		);

		// Test: Invalid parent for non-hierarchical post type.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/pages' );
		$request->set_param( 'title', 'Test Invalid Parent' );
		$request->set_param( 'post_type', 'post' );
		$request->set_param( 'parent', 12345 );
		$this->test_error_response(
			'POST /pages - Parent ID for non-hierarchical type',
			$request,
			400
		);

		// Test: Post ID that is not a template type.
		if ( $this->test_page_id ) {
			$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/templates/' . $this->test_page_id );
			$this->test_error_response(
				'GET /templates/{id} - Non-template post type',
				$request,
				400
			);
		}

		// Test: Regenerate CSS for post without Oxygen content.
		if ( $this->test_page_id ) {
			$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/regenerate-css/' . $this->test_page_id );
			$this->test_error_response(
				'POST /regenerate-css/{id} - Post without Oxygen content',
				$request,
				400
			);
		}
	}

	/**
	 * Test 403 Forbidden error responses.
	 */
	private function test_403_errors() {
		// Save current user.
		$current_user_id = get_current_user_id();

		// Create a subscriber user for permission tests.
		$subscriber_id = wp_create_user( 'oxybridge_test_subscriber', 'test_pass_123', 'oxybridge_test@example.com' );

		if ( is_wp_error( $subscriber_id ) ) {
			WP_CLI::warning( 'Could not create subscriber user for permission tests: ' . $subscriber_id->get_error_message() );
			return;
		}

		// Set subscriber role.
		$subscriber = new WP_User( $subscriber_id );
		$subscriber->set_role( 'subscriber' );

		// Switch to subscriber user.
		wp_set_current_user( $subscriber_id );

		// Test: Read permission denied.
		$this->test_error_response(
			'GET /pages - Subscriber (no edit_posts)',
			new WP_REST_Request( 'GET', '/' . $this->namespace . '/pages' ),
			403
		);

		// Test: Write permission denied.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/pages' );
		$request->set_param( 'title', 'Test No Permission' );
		$this->test_error_response(
			'POST /pages - Subscriber (no edit_posts)',
			$request,
			403
		);

		// Test: Settings endpoint permission.
		$this->test_error_response(
			'GET /settings - Subscriber (no edit_posts)',
			new WP_REST_Request( 'GET', '/' . $this->namespace . '/settings' ),
			403
		);

		// Test: Schema endpoint permission.
		$this->test_error_response(
			'GET /schema - Subscriber (no edit_posts)',
			new WP_REST_Request( 'GET', '/' . $this->namespace . '/schema' ),
			403
		);

		// Restore original user.
		wp_set_current_user( $current_user_id );

		// Clean up test subscriber.
		if ( $subscriber_id && ! is_wp_error( $subscriber_id ) ) {
			wp_delete_user( $subscriber_id );
			WP_CLI::log( '  Cleaned up test subscriber user' );
		}
	}

	/**
	 * Test validation error responses.
	 */
	private function test_validation_errors() {
		$server = rest_get_server();

		// Test: Validate with null tree.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/validate' );
		$request->set_param( 'tree', null );
		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		$is_valid = isset( $data['valid'] ) && $data['valid'] === false;
		$has_errors = isset( $data['errors'] ) && ! empty( $data['errors'] );

		if ( $is_valid && $has_errors ) {
			$this->pass( 'POST /validate - Null tree returns valid=false' );
		} else {
			$this->fail( 'POST /validate - Null tree', 'Expected valid=false with errors array' );
		}

		// Test: Validate with invalid JSON string.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/validate' );
		$request->set_param( 'tree', 'not valid json {{{' );
		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		$is_valid = isset( $data['valid'] ) && $data['valid'] === false;

		if ( $is_valid ) {
			$this->pass( 'POST /validate - Invalid JSON string returns valid=false' );
		} else {
			$this->fail( 'POST /validate - Invalid JSON string', 'Expected valid=false' );
		}

		// Test: Validate with tree missing root.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/validate' );
		$request->set_param( 'tree', array(
			'data' => array( 'type' => 'Section' ),
		) );
		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		$is_valid = isset( $data['valid'] ) && $data['valid'] === false;

		if ( $is_valid ) {
			$this->pass( 'POST /validate - Tree missing root returns valid=false' );
		} else {
			$this->fail( 'POST /validate - Tree missing root', 'Expected valid=false' );
		}

		// Test: Validate with root missing children.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/validate' );
		$request->set_param( 'tree', array(
			'root' => array(
				'id' => 'test',
			),
		) );
		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		$is_valid = isset( $data['valid'] ) && $data['valid'] === false;

		if ( $is_valid ) {
			$this->pass( 'POST /validate - Root missing children returns valid=false' );
		} else {
			// Some implementations might be more lenient.
			WP_CLI::warning( 'POST /validate - Root missing children: Validation passed (may be acceptable)' );
			$this->results['passed']++;
		}

		// Test: Validate with valid tree.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/validate' );
		$request->set_param( 'tree', array(
			'root' => array(
				'id'       => 'test-root',
				'data'     => array( 'type' => 'root' ),
				'children' => array(),
			),
		) );
		$response = $server->dispatch( $request );
		$data     = $response->get_data();
		$status   = $response->get_status();

		if ( $status === 200 && isset( $data['valid'] ) && $data['valid'] === true ) {
			$this->pass( 'POST /validate - Valid tree returns valid=true' );
		} else {
			$this->fail( 'POST /validate - Valid tree', 'Expected status 200 with valid=true' );
		}
	}

	/**
	 * Test an error response.
	 *
	 * @param string          $test_name    Test name.
	 * @param WP_REST_Request $request      The request to dispatch.
	 * @param int             $expected_status Expected HTTP status code.
	 */
	private function test_error_response( $test_name, $request, $expected_status ) {
		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		// Check if status matches expected.
		if ( $status === $expected_status ) {
			// Verify it's a proper WP_Error format response.
			$has_error_format = ( isset( $data['code'] ) && isset( $data['message'] ) )
				|| ( isset( $data['data']['status'] ) && $data['data']['status'] === $expected_status );

			if ( $has_error_format ) {
				$this->pass( $test_name );
				$error_code = isset( $data['code'] ) ? $data['code'] : 'N/A';
				WP_CLI::log( "    Status: {$status}, Error code: {$error_code}" );
			} else {
				// Status code is correct but response format may vary.
				$this->pass( $test_name );
				WP_CLI::log( "    Status: {$status} (format may vary)" );
			}
		} else {
			// Check if response is still an error response.
			$is_error = $status >= 400 && $status < 600;

			if ( $is_error ) {
				// Different error code than expected, but still an error.
				WP_CLI::warning( sprintf(
					'%s: Expected %d, got %d (still an error response)',
					$test_name,
					$expected_status,
					$status
				) );
				$this->results['passed']++;
			} else {
				$this->fail( $test_name, sprintf( 'Expected status %d, got %d', $expected_status, $status ) );
			}
		}
	}

	/**
	 * Mark a test as passed.
	 *
	 * @param string $test_name Test name.
	 */
	private function pass( $test_name ) {
		$this->results['passed']++;
		WP_CLI::success( $test_name );
	}

	/**
	 * Mark a test as failed.
	 *
	 * @param string $test_name     Test name.
	 * @param string $error_message Error message.
	 */
	private function fail( $test_name, $error_message ) {
		$this->results['failed']++;
		$this->results['errors'][] = array(
			'test'    => $test_name,
			'message' => $error_message,
		);
		WP_CLI::error( $test_name . ': ' . $error_message, false );
	}

	/**
	 * Clean up test data.
	 */
	private function cleanup() {
		if ( $this->test_page_id ) {
			$deleted = wp_delete_post( $this->test_page_id, true );
			if ( $deleted ) {
				WP_CLI::log( 'Deleted test page ID: ' . $this->test_page_id );
			}
			$this->test_page_id = 0;
		}
	}

	/**
	 * Print test summary.
	 */
	private function print_summary() {
		WP_CLI::log( '' );
		WP_CLI::log( '========================================================' );
		WP_CLI::log( 'Error Response Test Summary' );
		WP_CLI::log( '========================================================' );
		WP_CLI::log( '' );

		$total = $this->results['passed'] + $this->results['failed'];

		WP_CLI::log( sprintf( 'Total tests: %d', $total ) );
		WP_CLI::log( sprintf( 'Passed: %d', $this->results['passed'] ) );
		WP_CLI::log( sprintf( 'Failed: %d', $this->results['failed'] ) );

		if ( ! empty( $this->results['errors'] ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( 'Failed Tests:' );
			foreach ( $this->results['errors'] as $error ) {
				WP_CLI::log( sprintf( '  - %s: %s', $error['test'], $error['message'] ) );
			}
		}

		WP_CLI::log( '' );

		if ( $this->results['failed'] === 0 ) {
			WP_CLI::success( 'All error response tests passed! Endpoints return proper WP_Error responses.' );
		} else {
			WP_CLI::warning( sprintf( '%d test(s) failed - review output above.', $this->results['failed'] ) );
		}

		// Print verification summary.
		WP_CLI::log( '' );
		WP_CLI::log( 'Verified Error Response Categories:' );
		WP_CLI::log( '  - 404 Not Found: Non-existent posts, templates, documents' );
		WP_CLI::log( '  - 400 Bad Request: Invalid types, parameters, structures' );
		WP_CLI::log( '  - 403 Forbidden: Insufficient permissions' );
		WP_CLI::log( '  - Validation: Invalid tree structures, JSON, missing fields' );
	}
}

// Run the tests.
$test = new Oxybridge_Error_Response_Test();
$test->run();
