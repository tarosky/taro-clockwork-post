<?php
/**
 * REST API test
 *
 * @package tscp
 */

/**
 * REST API test case.
 */
class Tscp_Rest_Api_Test extends WP_UnitTestCase {

	/**
	 * REST Server
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/**
	 * Test route is registered.
	 */
	public function test_route_registered() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/clockwork/v1/(?P<post_type>[^/]+)/(?P<post_id>\\d+)/expiration', $routes );
	}

	/**
	 * Test GET expiration returns defaults for new post.
	 */
	public function test_get_expiration_defaults() {
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$request  = new WP_REST_Request( 'GET', '/clockwork/v1/post/' . $post_id . '/expiration' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertFalse( $data['should_expires'] );
		$this->assertEmpty( $data['expires'] );
	}

	/**
	 * Test POST saves expiration data with zero-padded date.
	 */
	public function test_post_expiration() {
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$request = new WP_REST_Request( 'POST', '/clockwork/v1/post/' . $post_id . '/expiration' );
		$request->set_body_params( [
			'should'  => true,
			'expires' => '2099-12-31 23:59:59',
		] );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertNotEmpty( $data['should_expires'] );
		$this->assertEquals( '2099-12-31 23:59:59', $data['expires'] );
		// Verify meta is saved.
		$this->assertEquals( '2099-12-31 23:59:59', get_post_meta( $post_id, '_tscp_expires', true ) );
	}

	/**
	 * Test POST with non-zero-padded date returns error (WP 6.9 bug scenario).
	 */
	public function test_post_non_padded_date_rejected() {
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$request = new WP_REST_Request( 'POST', '/clockwork/v1/post/' . $post_id . '/expiration' );
		$request->set_body_params( [
			'should'  => true,
			'expires' => '2026-4-3 14:55:59',
		] );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test POST with empty date succeeds (disabling expiration).
	 */
	public function test_post_empty_date() {
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$request = new WP_REST_Request( 'POST', '/clockwork/v1/post/' . $post_id . '/expiration' );
		$request->set_body_params( [
			'should'  => false,
			'expires' => '',
		] );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test unauthorized user cannot access.
	 */
	public function test_unauthorized_access() {
		wp_set_current_user( 0 );
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$request  = new WP_REST_Request( 'GET', '/clockwork/v1/post/' . $post_id . '/expiration' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test invalid post type returns error.
	 */
	public function test_invalid_post_type() {
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$request  = new WP_REST_Request( 'GET', '/clockwork/v1/page/' . $post_id . '/expiration' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test GET returns saved expiration data.
	 */
	public function test_get_saved_expiration() {
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		update_post_meta( $post_id, '_tscp_should_expire', 1 );
		update_post_meta( $post_id, '_tscp_expires', '2099-06-15 10:30:00' );

		$request  = new WP_REST_Request( 'GET', '/clockwork/v1/post/' . $post_id . '/expiration' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['should_expires'] );
		$this->assertEquals( '2099-06-15 10:30:00', $data['expires'] );
	}
}
