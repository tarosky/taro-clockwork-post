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
		// Check function.
		$posts = tscp_get_expired_posts();
		$this->assertEmpty( $posts );
		$this->assertFalse( tscp_will_expire() );
	}

	/**
	 * Test expiration.
	 */
	function test_expiration() {
		// Create post to be expired.
		$post_id = wp_insert_post( [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_title'  => 'Sample Post',
			'post_content' => 'This post will be expired.',
		] );
		$this->assertTrue( is_numeric( $post_id ) );
		// Save expiration setting.
		update_post_meta( $post_id, '_tscp_should_expire', 1 );
		$date = '1979-08-16 15:15:00';
		update_post_meta( $post_id, '_tscp_expires', $date );
		// Check if this post will be expires.
		$this->assertWPError( tscp_will_expire( $post_id ) );
		// Get expiring posts at least 1.
		$should_expires = tscp_get_expired_posts();
		$this->assertNotEmpty( $should_expires );
		// Execute expiration.
		do_action( TSCP_EVENT_NAME );
		// Check if post status is private.
		$this->assertEquals( 'private', get_post_status( $post_id ) );
	}

}
