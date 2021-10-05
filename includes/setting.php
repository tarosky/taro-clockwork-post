<?php
/**
 * Settings API
 *
 * @package tscp
 */

// Register setting fields
add_action( 'admin_init', function() {
	add_settings_section(
		'tscp_setting',
		__( 'Post Expiration Setting', 'tscp' ),
		function() {
			printf(
				'<p class="description">%s</p>',
				__( 'These settings are used by Taro Clockwork Post for post expiration.', 'tscp' )
			);
		},
		'reading'
	);

	// Add settings to section
	add_settings_field(
		'tscp_post_types',
		__( 'Post Types', 'tscp' ),
		function() {
			$post_types = tscp_post_types();
			foreach ( get_post_types( [ 'public' => true ], OBJECT ) as $post_type ) {
				if ( 'attachment' === $post_type->name ) {
					continue;
				}
				printf(
					'<label style="display: inline-block; margin: 0 1em 1em 0;"><input type="checkbox" name="tscp_post_types[]" value="%s" %s /> %s</label>',
					esc_attr( $post_type->name ),
					checked( in_array( $post_type->name, $post_types, true ), true, false ),
					esc_html( $post_type->label )
				);
			}
			printf( '<p class="description">%s</p>', esc_html__( 'Specified post types will have expiration field.', 'tscp' ) );
		},
		'reading',
		'tscp_setting'
	);

	// Automatic save.
	register_setting( 'reading', 'tscp_post_types' );

} );


