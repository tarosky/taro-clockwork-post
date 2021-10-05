<?php
/**
 * Utility functions
 *
 * @package tscp
 */

/**
 * Get post type to be expired.
 *
 * @return string[]
 */
function tscp_post_types() {
	return (array) get_option( 'tscp_post_types', [ 'post' ] );
}

/**
 * Is post type can be expired.
 *
 * @param string $post_type Post type
 * @return bool
 */
function tscp_post_type_can_expire( $post_type ) {
	return in_array( $post_type, tscp_post_types(), true );
}

/**
 * Get posts to be expired.
 *
 * @package tscp
 * @see get_posts()
 * @return array Array of posts
 */
function tscp_get_expired_posts() {
	return get_posts( [
		'post_type'        => tscp_post_types(),
		'post_status'      => 'publish',
		'posts_per_page'   => -1,
		'suppress_filters' => false,
		'meta_query'       => [
			[
				'key'   => '_tscp_should_expire',
				'value' => 1,
			],
			[
				'key'     => '_tscp_expires',
				'value'   => current_time( 'mysql' ),
				'compare' => '<',
				'type'    => 'DATETIME',
			],
		],
	] );
}

/**
 * Check if post will be expired.
 *
 * @package tscp
 * @since 1.0.0
 * @param null|int|WP_Post $post
 *
 * @return bool|WP_Error
 */
function tscp_will_expire( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		$will_expire = false;
	} elseif ( 'publish' !== $post->post_status ) {
		$will_expire = false;
	} elseif ( ! get_post_meta( $post->ID, '_tscp_should_expire', true ) ) {
		$will_expire = false;
	} else {
		$will_expire = false;
		$expires     = get_post_meta( $post->ID, '_tscp_expires', true );
		$date        = new DateTime( $expires );
		$now         = new DateTime();
		$now->add( new DateInterval( sprintf( 'PT%sH', (int) get_option( 'gmt_offset' ) ) ) );
		if ( $date > $now ) {
			$will_expire = true;
		} else {
			$will_expire = new WP_Error( 'expiration_failed', __( 'Failed expiration', 'tscp' ) );
		}
	}

	/**
	 * tscp_will_expire
	 *
	 * Detect if specified post will expires or not.
	 * @param bool|WP_Error $will_expire
	 * @param WP_Post       $post
	 */
	return apply_filters( 'tscp_will_expire', $will_expire, $post );
}
