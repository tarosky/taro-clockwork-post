<?php
/**
 * Function test
 *
 * @package tscp
 */

/**
 * Sample test case.
 */
class Tscp_Basic_Test extends WP_UnitTestCase {

	/**
	 * Test functions
	 */
	function test_functions() {
		// Check function
		$posts = tscp_get_expired_posts();
		$this->assertEmpty( $posts );
		$this->assertFalse( tscp_will_expire() );
	}

}
