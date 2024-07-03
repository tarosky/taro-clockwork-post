<?php
/**
 * Support for block editor.
 *
 * @package tscp
 */

/**
 * Register block editor assets.
 */
add_action( 'enqueue_block_editor_assets', function () {
	// Register script
	wp_enqueue_script( 'tscp-editor-input', tscp_asset_url( 'js/editor-input.js' ), [ 'wp-plugins', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-i18n', 'wp-compose', 'wp-element', 'wp-api-fetch' ], tscp_version(), true );
	wp_enqueue_style( 'tscp-editor-input', tscp_asset_url( 'css/editor-input.css' ), [ 'wp-components' ], tscp_version() );
	// translations.
	wp_set_script_translations( 'tscp-editor-input', 'tscp' );
	// Register variables.
	wp_localize_script( 'tscp-editor-input', 'TscpEditorInput', [
		'postTypes' => tscp_post_types(),
	] );
} );

/**
 * Register REST API for custom fields.
 */
add_action( 'rest_api_init', function () {

	$permission_callback = function ( WP_REST_Request $request ) {
		return current_user_can( 'edit_post', $request->get_param( 'post_id' ) );
	};

	$args = [
		'post_type' => [
			'required'          => true,
			'type'              => 'string',
			'validate_callback' => function ( $post_type ) {
				return tscp_post_type_can_expire( $post_type );
			},
		],
		'post_id'   => [
			'required'          => true,
			'type'              => 'int',
			'validate_callback' => function ( $post_id ) {
				return is_numeric( $post_id ) && get_post( $post_id );
			},
		],
	];

	register_rest_route( 'clockwork/v1', '(?P<post_type>[^/]+)/(?P<post_id>\d+)/expiration', [
		[
			'methods'             => 'GET',
			'args'                => $args,
			'permission_callback' => $permission_callback,
			'callback'            => function ( WP_REST_Request $request ) {
				$post_id = $request->get_param( 'post_id' );
				return new WP_REST_Response( [
					'should_expires' => (bool) get_post_meta( $post_id, '_tscp_should_expire', true ),
					'expires'        => get_post_meta( $post_id, '_tscp_expires', true ),
				] );
			},
		],
		[
			'methods'             => 'POST',
			'args'                => array_merge( $args, [
				'should'  => [
					'required' => true,
					'type'     => 'bool',
				],
				'expires' => [
					'required'          => true,
					'type'              => 'string',
					'validate_callback' => function ( $date ) {
						return empty( $date ) || preg_match( '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/u', $date );
					},
				],
			] ),
			'permission_callback' => $permission_callback,
			'callback'            => function ( WP_REST_Request $request ) {
				$post_id       = $request->get_param( 'post_id' );
				$should_expire = $request->get_param( 'should' );
				$expires_at    = $request->get_param( 'expires' );
				update_post_meta( $post_id, '_tscp_should_expire', $should_expire );
				update_post_meta( $post_id, '_tscp_expires', $expires_at );
				if ( ! $should_expire ) {
					$message = __( 'This post won\'t be expired.', 'tscp' );
				} elseif ( empty( $expires_at ) ) {
					$message = __( 'This post should be expired but no date set.', 'tscp' );
				} else {
					// translators: %s is expired at.
					$message = sprintf( __( 'This post will be expired at %s.', 'tscp' ), mysql2date( get_option( 'date_format' ) . ' H:i', $expires_at ) );
				}
				return new WP_REST_Response( [
					'message'        => $message,
					'should_expires' => $should_expire,
					'expires'        => $expires_at,
				] );
			},
		],
	] );
} );
