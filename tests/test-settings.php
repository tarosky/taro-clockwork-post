<?php
/**
 * Settings test
 *
 * @package tscp
 */

/**
 * Settings sanitize callback test case.
 */
class Tscp_Settings_Test extends WP_UnitTestCase {

	/**
	 * Test sanitize_callback filters invalid post types.
	 */
	public function test_sanitize_post_types() {
		$result = tscp_sanitize_post_types( [ 'post', 'nonexistent_type', 123, 'page' ] );

		$this->assertContains( 'post', $result );
		$this->assertContains( 'page', $result );
		$this->assertNotContains( 'nonexistent_type', $result );
		$this->assertNotContains( 123, $result );
	}

	/**
	 * Test sanitize_callback with empty input.
	 */
	public function test_sanitize_empty_input() {
		$result = tscp_sanitize_post_types( [] );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test sanitize_callback with null input.
	 */
	public function test_sanitize_null_input() {
		$result = tscp_sanitize_post_types( null );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test sanitize_callback with all invalid values.
	 */
	public function test_sanitize_all_invalid() {
		$result = tscp_sanitize_post_types( [ 'fake_type', 42, true, '' ] );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}
}
