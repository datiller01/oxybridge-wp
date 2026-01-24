<?php
/**
 * Test: Tree Integrity Verification
 *
 * This script tests that tree integrity methods properly add _nextNodeId
 * and other required properties for IO-TS validation in Breakdance/Oxygen Builder.
 *
 * Run via WP-CLI: wp eval-file wp-content/plugins/oxybridge-wp/tests/test-tree-integrity.php
 *
 * @package Oxybridge
 */

// Ensure this is run via WP-CLI.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run via WP-CLI.\n";
	echo "Usage: wp eval-file wp-content/plugins/oxybridge-wp/tests/test-tree-integrity.php\n";
	exit( 1 );
}

/**
 * Tree Integrity Test Runner for Oxybridge REST API.
 */
class Oxybridge_Tree_Integrity_Test {

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
	 * Test template ID for cleanup.
	 *
	 * @var int
	 */
	private $test_template_id = 0;

	/**
	 * Test page ID for cleanup.
	 *
	 * @var int
	 */
	private $test_page_id = 0;

	/**
	 * Run all tree integrity tests.
	 *
	 * @return void
	 */
	public function run() {
		WP_CLI::log( '========================================================' );
		WP_CLI::log( 'Oxybridge Tree Integrity Verification Test' );
		WP_CLI::log( '========================================================' );
		WP_CLI::log( '' );

		// Check prerequisites.
		if ( ! $this->check_prerequisites() ) {
			return;
		}

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 1: ensure_tree_integrity() ===' );
		WP_CLI::log( '' );
		$this->test_ensure_tree_integrity();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 2: create_empty_tree() ===' );
		WP_CLI::log( '' );
		$this->test_create_empty_tree();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 3: calculate_next_node_id() ===' );
		WP_CLI::log( '' );
		$this->test_calculate_next_node_id();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 4: get_document_tree() ===' );
		WP_CLI::log( '' );
		$this->test_get_document_tree();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 5: Validator Integration ===' );
		WP_CLI::log( '' );
		$this->test_validator_integration();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test Group 6: API Response Structure ===' );
		WP_CLI::log( '' );
		$this->test_api_response_structure();

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

		// Check if Validator class is available.
		if ( ! class_exists( 'Oxybridge\\Validator' ) ) {
			WP_CLI::error( 'Oxybridge Validator class not found.', false );
			return false;
		}
		WP_CLI::success( 'Oxybridge Validator class available' );

		// Check current user permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			WP_CLI::error( 'Current user does not have edit_posts capability.', false );
			return false;
		}
		WP_CLI::success( 'User has required permissions' );

		return true;
	}

	/**
	 * Test ensure_tree_integrity() method.
	 *
	 * Verifies that the method adds _nextNodeId and exportedLookupTable.
	 */
	private function test_ensure_tree_integrity() {
		// Create a test controller instance to access protected methods.
		$controller = $this->get_test_controller();

		// Test 1: Tree without _nextNodeId should get it added.
		$tree_without_next_id = array(
			'root' => array(
				'id'       => 'el-root',
				'data'     => array(
					'type'       => 'root',
					'properties' => null,
				),
				'children' => array(),
			),
		);

		$result = $this->call_protected_method( $controller, 'ensure_tree_integrity', array( $tree_without_next_id ) );

		if ( isset( $result['_nextNodeId'] ) && is_int( $result['_nextNodeId'] ) ) {
			$this->pass( 'ensure_tree_integrity() adds _nextNodeId' );
			WP_CLI::log( "    _nextNodeId value: {$result['_nextNodeId']}" );
		} else {
			$this->fail( 'ensure_tree_integrity() adds _nextNodeId', 'Property missing or invalid type' );
		}

		// Test 2: exportedLookupTable should be added.
		if ( isset( $result['exportedLookupTable'] ) ) {
			// Check it's an empty stdClass (not array) for proper JSON encoding.
			$json = json_encode( $result['exportedLookupTable'] );
			if ( $json === '{}' ) {
				$this->pass( 'ensure_tree_integrity() adds exportedLookupTable as empty object' );
			} else {
				$this->fail( 'ensure_tree_integrity() adds exportedLookupTable as empty object', "Got JSON: {$json}" );
			}
		} else {
			$this->fail( 'ensure_tree_integrity() adds exportedLookupTable', 'Property missing' );
		}

		// Test 3: Tree with existing _nextNodeId should preserve it.
		$tree_with_next_id = array(
			'root' => array(
				'id'       => 'el-root',
				'data'     => array(
					'type'       => 'root',
					'properties' => null,
				),
				'children' => array(),
			),
			'_nextNodeId' => 100,
		);

		$result = $this->call_protected_method( $controller, 'ensure_tree_integrity', array( $tree_with_next_id ) );

		if ( isset( $result['_nextNodeId'] ) && $result['_nextNodeId'] === 100 ) {
			$this->pass( 'ensure_tree_integrity() preserves existing _nextNodeId' );
		} else {
			$this->fail( 'ensure_tree_integrity() preserves existing _nextNodeId', "Expected 100, got {$result['_nextNodeId']}" );
		}

		// Test 4: Root type normalization (EssentialElements\Root -> root).
		$tree_with_namespaced_type = array(
			'root' => array(
				'id'       => 'el-root',
				'data'     => array(
					'type'       => 'EssentialElements\\Root',
					'properties' => array(),
				),
				'children' => array(),
			),
		);

		$result = $this->call_protected_method( $controller, 'ensure_tree_integrity', array( $tree_with_namespaced_type ) );

		if ( isset( $result['root']['data']['type'] ) && $result['root']['data']['type'] === 'root' ) {
			$this->pass( 'ensure_tree_integrity() normalizes root type to lowercase' );
		} else {
			$this->fail( 'ensure_tree_integrity() normalizes root type', "Got: {$result['root']['data']['type']}" );
		}

		// Test 5: Root properties normalization (empty array -> null).
		if ( $result['root']['data']['properties'] === null ) {
			$this->pass( 'ensure_tree_integrity() normalizes root properties to null' );
		} else {
			$this->fail( 'ensure_tree_integrity() normalizes root properties', 'Properties not null' );
		}

		// Test 6: Status should be set.
		if ( isset( $result['status'] ) && $result['status'] === 'exported' ) {
			$this->pass( 'ensure_tree_integrity() sets status to exported' );
		} else {
			$this->fail( 'ensure_tree_integrity() sets status', 'Status not set correctly' );
		}
	}

	/**
	 * Test create_empty_tree() method.
	 */
	private function test_create_empty_tree() {
		$controller = $this->get_test_controller();

		$tree = $this->call_protected_method( $controller, 'create_empty_tree', array() );

		// Test 1: Tree should have root.
		if ( isset( $tree['root'] ) ) {
			$this->pass( 'create_empty_tree() has root' );
		} else {
			$this->fail( 'create_empty_tree() has root', 'Missing root' );
		}

		// Test 2: Tree should have _nextNodeId.
		if ( isset( $tree['_nextNodeId'] ) && is_int( $tree['_nextNodeId'] ) ) {
			$this->pass( 'create_empty_tree() includes _nextNodeId' );
			WP_CLI::log( "    _nextNodeId value: {$tree['_nextNodeId']}" );
		} else {
			$this->fail( 'create_empty_tree() includes _nextNodeId', 'Property missing or invalid' );
		}

		// Test 3: Root should have correct structure.
		$has_correct_structure = isset( $tree['root']['id'] ) &&
			isset( $tree['root']['data'] ) &&
			isset( $tree['root']['children'] ) &&
			is_array( $tree['root']['children'] );

		if ( $has_correct_structure ) {
			$this->pass( 'create_empty_tree() root has correct structure (id, data, children)' );
		} else {
			$this->fail( 'create_empty_tree() root structure', 'Missing required keys' );
		}

		// Test 4: Root type should be 'root'.
		if ( isset( $tree['root']['data']['type'] ) && $tree['root']['data']['type'] === 'root' ) {
			$this->pass( 'create_empty_tree() root type is lowercase "root"' );
		} else {
			$this->fail( 'create_empty_tree() root type', 'Type not "root"' );
		}

		// Test 5: Root properties should be null.
		if ( array_key_exists( 'properties', $tree['root']['data'] ) && $tree['root']['data']['properties'] === null ) {
			$this->pass( 'create_empty_tree() root properties is null' );
		} else {
			$this->fail( 'create_empty_tree() root properties', 'Properties not null' );
		}
	}

	/**
	 * Test calculate_next_node_id() method.
	 */
	private function test_calculate_next_node_id() {
		$controller = $this->get_test_controller();

		// Test 1: Empty tree should return 1.
		$empty_tree = array(
			'root' => array(
				'id'       => 'el-root',
				'data'     => array( 'type' => 'root' ),
				'children' => array(),
			),
		);

		$next_id = $this->call_protected_method( $controller, 'calculate_next_node_id', array( $empty_tree ) );

		if ( $next_id >= 1 ) {
			$this->pass( 'calculate_next_node_id() returns >= 1 for empty tree' );
			WP_CLI::log( "    Value: {$next_id}" );
		} else {
			$this->fail( 'calculate_next_node_id() for empty tree', "Expected >= 1, got {$next_id}" );
		}

		// Test 2: Tree with numeric IDs should return max + 1.
		$tree_with_elements = array(
			'root' => array(
				'id'       => 'el-root',
				'data'     => array( 'type' => 'root' ),
				'children' => array(
					array(
						'id'       => 'el-5',
						'data'     => array( 'type' => 'Section' ),
						'children' => array(
							array(
								'id'   => 'el-10',
								'data' => array( 'type' => 'Text' ),
							),
						),
					),
					array(
						'id'   => 'el-3',
						'data' => array( 'type' => 'Section' ),
					),
				),
			),
		);

		$next_id = $this->call_protected_method( $controller, 'calculate_next_node_id', array( $tree_with_elements ) );

		if ( $next_id === 11 ) {
			$this->pass( 'calculate_next_node_id() returns max ID + 1 (11)' );
		} else {
			$this->fail( 'calculate_next_node_id() for tree with elements', "Expected 11, got {$next_id}" );
		}

		// Test 3: Tree with purely numeric IDs.
		$tree_numeric_ids = array(
			'root' => array(
				'id'       => 1,
				'data'     => array( 'type' => 'root' ),
				'children' => array(
					array(
						'id'       => 5,
						'data'     => array( 'type' => 'Section' ),
						'children' => array(
							array( 'id' => 7, 'data' => array( 'type' => 'Text' ) ),
						),
					),
				),
			),
		);

		$next_id = $this->call_protected_method( $controller, 'calculate_next_node_id', array( $tree_numeric_ids ) );

		if ( $next_id === 8 ) {
			$this->pass( 'calculate_next_node_id() handles purely numeric IDs (8)' );
		} else {
			$this->fail( 'calculate_next_node_id() for numeric IDs', "Expected 8, got {$next_id}" );
		}
	}

	/**
	 * Test get_document_tree() returns tree with _nextNodeId.
	 */
	private function test_get_document_tree() {
		$controller = $this->get_test_controller();

		// Create a test page with Oxygen content.
		$this->test_page_id = wp_insert_post( array(
			'post_title'  => 'Tree Integrity Test Page',
			'post_type'   => 'page',
			'post_status' => 'draft',
		) );

		if ( ! $this->test_page_id ) {
			$this->fail( 'Create test page', 'Could not create test page' );
			return;
		}

		WP_CLI::log( '  Created test page ID: ' . $this->test_page_id );

		// Save a tree using the controller.
		$test_tree = array(
			'root' => array(
				'id'       => 'el-root',
				'data'     => array(
					'type'       => 'root',
					'properties' => null,
				),
				'children' => array(
					array(
						'id'   => 'el-1',
						'data' => array( 'type' => 'Section' ),
						'children' => array(),
					),
				),
			),
		);

		$saved = $this->call_protected_method( $controller, 'save_document_tree', array( $this->test_page_id, $test_tree ) );

		if ( $saved ) {
			$this->pass( 'save_document_tree() returns true' );
		} else {
			$this->fail( 'save_document_tree()', 'Save returned false' );
			return;
		}

		// Retrieve the tree and check for _nextNodeId.
		$retrieved_tree = $this->call_protected_method( $controller, 'get_document_tree', array( $this->test_page_id ) );

		if ( $retrieved_tree === false ) {
			$this->fail( 'get_document_tree() returns tree', 'Returned false' );
			return;
		}

		$this->pass( 'get_document_tree() returns tree data' );

		// Check for _nextNodeId.
		if ( isset( $retrieved_tree['_nextNodeId'] ) && is_int( $retrieved_tree['_nextNodeId'] ) ) {
			$this->pass( 'get_document_tree() returns tree with _nextNodeId' );
			WP_CLI::log( "    _nextNodeId: {$retrieved_tree['_nextNodeId']}" );
		} else {
			$this->fail( 'get_document_tree() returns _nextNodeId', 'Property missing' );
		}

		// Check for exportedLookupTable.
		if ( isset( $retrieved_tree['exportedLookupTable'] ) ) {
			$this->pass( 'get_document_tree() returns tree with exportedLookupTable' );
		} else {
			$this->fail( 'get_document_tree() returns exportedLookupTable', 'Property missing' );
		}

		// Verify root structure.
		if ( isset( $retrieved_tree['root']['id'] ) &&
			 isset( $retrieved_tree['root']['data'] ) &&
			 isset( $retrieved_tree['root']['children'] ) ) {
			$this->pass( 'get_document_tree() returns valid root structure' );
		} else {
			$this->fail( 'get_document_tree() root structure', 'Missing required keys' );
		}
	}

	/**
	 * Test Validator class integration.
	 */
	private function test_validator_integration() {
		$validator = new \Oxybridge\Validator();

		// Test 1: validate_tree_has_next_node_id() with valid tree.
		$valid_tree = array(
			'root' => array(
				'id'       => 'el-root',
				'data'     => array( 'type' => 'root' ),
				'children' => array(),
			),
			'_nextNodeId' => 1,
		);

		$result = $validator->validate_tree_has_next_node_id( $valid_tree );

		if ( $result['valid'] === true ) {
			$this->pass( 'Validator accepts tree with _nextNodeId' );
		} else {
			$this->fail( 'Validator accepts valid tree', 'Returned invalid' );
		}

		// Test 2: validate_tree_has_next_node_id() without _nextNodeId.
		$invalid_tree = array(
			'root' => array(
				'id'       => 'el-root',
				'data'     => array( 'type' => 'root' ),
				'children' => array(),
			),
		);

		$result = $validator->validate_tree_has_next_node_id( $invalid_tree );

		if ( $result['valid'] === false && ! empty( $result['errors'] ) ) {
			$this->pass( 'Validator rejects tree without _nextNodeId' );
			WP_CLI::log( '    Error: ' . $result['errors'][0]['message'] );
		} else {
			$this->fail( 'Validator rejects invalid tree', 'Should have returned errors' );
		}

		// Test 3: validate_iots_response() with valid document response.
		$valid_response = array(
			'document' => array(
				'tree' => array(
					'root' => array(
						'id'       => 'el-root',
						'data'     => array( 'type' => 'root' ),
						'children' => array(),
					),
					'_nextNodeId' => 1,
				),
			),
		);

		$result = $validator->validate_iots_response( $valid_response );

		if ( $result['valid'] === true && $result['response_type'] === 'document' ) {
			$this->pass( 'Validator accepts valid IO-TS document response' );
		} else {
			$this->fail( 'Validator accepts valid document response', 'Returned invalid' );
		}

		// Test 4: validate_iots_response() without _nextNodeId.
		$invalid_response = array(
			'document' => array(
				'tree' => array(
					'root' => array(
						'id'       => 'el-root',
						'data'     => array( 'type' => 'root' ),
						'children' => array(),
					),
					// Missing _nextNodeId.
				),
			),
		);

		$result = $validator->validate_iots_response( $invalid_response );

		if ( $result['valid'] === false ) {
			$this->pass( 'Validator rejects IO-TS response without _nextNodeId' );
			$has_next_node_error = false;
			foreach ( $result['errors'] as $error ) {
				if ( strpos( $error['code'], 'next_node_id' ) !== false ) {
					$has_next_node_error = true;
					WP_CLI::log( '    Error code: ' . $error['code'] );
					break;
				}
			}
			if ( ! $has_next_node_error ) {
				WP_CLI::warning( '    Note: Error code does not mention next_node_id specifically' );
			}
		} else {
			$this->fail( 'Validator rejects response without _nextNodeId', 'Should have returned errors' );
		}

		// Test 5: validate_iots_response() with error response.
		$error_response = array(
			'errorType'      => 'post_does_not_exist',
			'backToAdminUrl' => '/wp-admin/',
		);

		$result = $validator->validate_iots_response( $error_response );

		if ( $result['valid'] === true && $result['response_type'] === 'error' ) {
			$this->pass( 'Validator accepts valid IO-TS error response' );
		} else {
			$this->fail( 'Validator accepts error response', 'Returned invalid' );
		}
	}

	/**
	 * Test API response structure via REST requests.
	 */
	private function test_api_response_structure() {
		$server = rest_get_server();

		// Create a template for testing.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/templates' );
		$request->set_param( 'title', 'Tree Integrity Test Template' );
		$request->set_param( 'template_type', 'header' );

		$response = $server->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		if ( $status !== 200 && $status !== 201 ) {
			$this->fail( 'POST /templates creates template', "Status: {$status}" );
			return;
		}

		$this->pass( 'POST /templates creates template' );

		// Store template ID for cleanup.
		if ( isset( $data['id'] ) ) {
			$this->test_template_id = $data['id'];
			WP_CLI::log( '    Created template ID: ' . $this->test_template_id );
		}

		// Test 1: Response should include tree.
		if ( isset( $data['tree'] ) ) {
			$this->pass( 'Template response includes tree' );
		} else {
			$this->fail( 'Template response includes tree', 'Missing tree' );
			return;
		}

		// Test 2: Tree should have _nextNodeId.
		if ( isset( $data['tree']['_nextNodeId'] ) && is_int( $data['tree']['_nextNodeId'] ) ) {
			$this->pass( 'Template tree includes _nextNodeId' );
			WP_CLI::log( "    _nextNodeId: {$data['tree']['_nextNodeId']}" );
		} else {
			$this->fail( 'Template tree includes _nextNodeId', 'Property missing or invalid' );
		}

		// Test 3: Tree should have root.
		if ( isset( $data['tree']['root'] ) ) {
			$this->pass( 'Template tree includes root' );
		} else {
			$this->fail( 'Template tree includes root', 'Missing root' );
		}

		// Test 4: GET template should also include _nextNodeId.
		if ( $this->test_template_id ) {
			$request  = new WP_REST_Request( 'GET', '/' . $this->namespace . '/templates/' . $this->test_template_id );
			$response = $server->dispatch( $request );
			$data     = $response->get_data();

			if ( isset( $data['tree']['_nextNodeId'] ) ) {
				$this->pass( 'GET /templates/{id} includes _nextNodeId' );
			} else {
				$this->fail( 'GET /templates/{id} includes _nextNodeId', 'Missing property' );
			}
		}

		// Test 5: Validate response with Validator class.
		if ( isset( $data['tree'] ) ) {
			$validator = new \Oxybridge\Validator();
			$result = $validator->validate_tree_has_next_node_id( $data['tree'] );

			if ( $result['valid'] === true ) {
				$this->pass( 'GET /templates/{id} response passes IO-TS validation' );
			} else {
				$this->fail( 'Response passes IO-TS validation', $result['errors'][0]['message'] ?? 'Validation failed' );
			}
		}
	}

	/**
	 * Get a test controller instance.
	 *
	 * @return object Controller instance.
	 */
	private function get_test_controller() {
		// Use the REST_Documents controller which extends REST_Controller.
		if ( class_exists( 'Oxybridge\\REST\\REST_Documents' ) ) {
			return new \Oxybridge\REST\REST_Documents();
		}

		// Fallback: Create anonymous class extending REST_Controller.
		return new class extends \Oxybridge\REST\REST_Controller {
			public function register_routes(): void {}
		};
	}

	/**
	 * Call a protected method on an object using reflection.
	 *
	 * @param object $object     The object.
	 * @param string $method     The method name.
	 * @param array  $parameters Parameters to pass.
	 * @return mixed The method result.
	 */
	private function call_protected_method( $object, $method, array $parameters = array() ) {
		$reflection = new ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
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

		if ( $this->test_template_id ) {
			$deleted = wp_delete_post( $this->test_template_id, true );
			if ( $deleted ) {
				WP_CLI::log( 'Deleted test template ID: ' . $this->test_template_id );
			}
			$this->test_template_id = 0;
		}
	}

	/**
	 * Print test summary.
	 */
	private function print_summary() {
		WP_CLI::log( '' );
		WP_CLI::log( '========================================================' );
		WP_CLI::log( 'Tree Integrity Test Summary' );
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
			WP_CLI::success( 'All tree integrity tests passed! Trees correctly include _nextNodeId for IO-TS validation.' );
		} else {
			WP_CLI::warning( sprintf( '%d test(s) failed - review output above.', $this->results['failed'] ) );
		}

		// Print verification summary.
		WP_CLI::log( '' );
		WP_CLI::log( 'Verified Tree Integrity Features:' );
		WP_CLI::log( '  - ensure_tree_integrity() adds _nextNodeId and exportedLookupTable' );
		WP_CLI::log( '  - create_empty_tree() includes _nextNodeId' );
		WP_CLI::log( '  - calculate_next_node_id() correctly calculates max ID + 1' );
		WP_CLI::log( '  - get_document_tree() returns trees with _nextNodeId' );
		WP_CLI::log( '  - Validator class validates _nextNodeId presence' );
		WP_CLI::log( '  - API responses include _nextNodeId for IO-TS validation' );
	}
}

// Run the tests.
$test = new Oxybridge_Tree_Integrity_Test();
$test->run();
