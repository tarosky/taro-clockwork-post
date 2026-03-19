<?php
/**
 * Settings API
 *
 * @package tscp
 */


defined( 'ABSPATH' ) || die();

// Register setting fields
add_action( 'admin_init', function () {
	add_settings_section(
		'tscp_setting',
		__( 'Post Expiration Setting', 'taro-clockwork-post' ),
		function () {
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'These settings are used by Taro Clockwork Post for post expiration.', 'taro-clockwork-post' )
			);
		},
		'reading'
	);

	// Add settings to section
	add_settings_field(
		'tscp_post_types',
		__( 'Post Types', 'taro-clockwork-post' ),
		function () {
			$post_types               = tscp_post_types();
			$available_post_type_list = get_post_types( [ 'show_ui' => true ], OBJECT );
			$available_post_type_list = array_values( array_filter( $available_post_type_list, function ( WP_Post_Type $post_type ) {
				return ! in_array( $post_type->name, [ 'attachment', 'wp_navigation', 'wp_block' ], true );
			} ) );
			$available_post_type_list = apply_filters( 'tscp_available_post_type_list', $available_post_type_list );
			foreach ( $available_post_type_list as $post_type ) {
				printf(
					'<label style="display: inline-block; margin: 0 1em 1em 0;"><input type="checkbox" name="tscp_post_types[]" value="%s" %s /> %s</label>',
					esc_attr( $post_type->name ),
					checked( in_array( $post_type->name, $post_types, true ), true, false ),
					esc_html( $post_type->label )
				);
			}
			printf( '<p class="description">%s</p>', esc_html__( 'Specified post types will have expiration field.', 'taro-clockwork-post' ) );
		},
		'reading',
		'tscp_setting'
	);

	// Automatic save.
	register_setting( 'reading', 'tscp_post_types', [
		'type'              => 'array',
		'sanitize_callback' => 'tscp_sanitize_post_types',
	] );
} );
