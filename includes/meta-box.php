<?php
/**
 * Render meta box
 *
 * @package tscp
 */

add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_style( 'tscp-admin-helper', tscp_asset_url( 'css/admin.css' ), [], tscp_version() );
} );

// Register meta box for specified posts
add_action( 'add_meta_boxes', function( $post_type ) {
	if ( false !== array_search( $post_type, (array) get_option( 'tscp_post_types', [ 'post' ] ) ) ) {
		add_action( 'post_submitbox_misc_actions', function( $post ) {
			wp_nonce_field( 'tscp_date', '_tscpnonce', false );
			$date_time = get_post_meta( $post->ID, '_tscp_expires', true );
			if ( ! $date_time ) {
				$now = new DateTime();
				$now->add( new DateInterval( sprintf( 'P1MT%sH', get_option( 'gmt_offset' ) ) ) );
				$one_month_later = $now->format( 'Y-m-d H:i:s' );
				$date_time       = apply_filters( 'tspc_default_expires', $one_month_later, $post );
			}
			list( $date, $time ) = explode( ' ', $date_time );
			list( $year, $month, $day ) = explode( '-', $date );
			list( $hour, $minute ) = explode( ':', $time );
			?>
			<div class="misc-pub-section misc-pub-tscp">
				<span class="tscp-wrapper">
				<input class="tscp-toggler" type="checkbox" id="tscp-should-expire" value="1"
					   name="tscp-should-expire" <?php checked( get_post_meta( $post->ID, '_tscp_should_expire', true ) ) ?> />
				<label class="tscp-field-label" for="tscp-should-expire">
					<?php esc_html_e( 'Expires at specified time', 'tscp' ) ?>
				</label>
				<span class="tscp-date-selector">
					<?php
					$year_input  = sprintf( '<input type="text" name="tscp-year" class="tscp-long" value="%s" />', esc_attr( $year ) );
					$month_input = '<select name="tscp-month" class="tscp-month">';
					for ( $i = 1; $i <= 12; $i ++ ) {
						$month_str   = mysql2date( 'M', str_replace( '-00-', sprintf( '-%02d-', $i ), date_i18n( 'Y-00-d' ) ) );
						$month_input .= sprintf( '<option value="%02d" %s>%s</option>', $i, selected( $i == $month, true, false ), $month_str );
					}
					$month_input  .= '</select>';
					$day_input    = sprintf( '<input type="text" name="tscp-day" class="tscp-short" value="%s" />', esc_attr( $day ) );
					$hour_input   = sprintf( '<input type="text" name="tscp-hour" class="tscp-short" value="%s" />', esc_attr( $hour ) );
					$minute_input = sprintf( '<input type="text" name="tscp-minute" class="tscp-short" value="%s" />', esc_attr( $minute ) );
					// translators: %1$s month, %2$s date, %3$s, year, %4$s hour, %5$s minute
					printf( _x( '%1$s %2$s, %3$s @ %4$s:%5$s', 'date-input', 'tscp' ),
						$month_input,
						$day_input,
						$year_input,
						$hour_input,
						$minute_input
					);
					?>
				</span>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<span class="description">
						<?php printf( __( 'You can choose post type to be expired at <a href="%s">setting</a>.', 'tscp' ), admin_url( 'wp-admin/options-reading.php' ) ) ?>
					</span>
					<?php endif; ?>
				</span>
			</div>
			<?php
		} );
	}
} );

// Save post
add_action( 'save_post', function( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['_tscpnonce'] ) || ! wp_verify_nonce( $_POST['_tscpnonce'], 'tscp_date' ) ) {
		return;
	}
	// Save flag
	if ( isset( $_POST['tscp-should-expire'] ) && $_POST['tscp-should-expire'] ) {
		update_post_meta( $post_id, '_tscp_should_expire', 1 );
	} else {
		delete_post_meta( $post_id, '_tscp_should_expire' );
	}
	// Build data and save it.
	$date = sprintf(
		'%04d-%02d-%02d %02d:%02d:59',
		$_POST['tscp-year'], $_POST['tscp-month'], $_POST['tscp-day'],
		$_POST['tscp-hour'], $_POST['tscp-minute']
	);
	update_post_meta( $post_id, '_tscp_expires', $date );
}, 10, 2 );


// Register post custom column
add_action( 'admin_init', function() {
	$post_types = array_filter( (array) get_option( 'tscp_post_types', [ 'post' ] ), 'post_type_exists' );
	// Register post column
	add_filter( 'manage_posts_columns', function( $columns, $post_type ) use ( $post_types ) {
		if ( false !== array_search( $post_type, $post_types ) ) {
			$new_columns = [];
			foreach ( $columns as $key => $val ) {
				$new_columns[ $key ] = $val;
				if ( 'date' == $key ) {
					$new_columns['expires'] = sprintf( '<span class="dashicons dashicons-clock" title="%s"></span>', esc_attr__( 'Expires', 'tscp' ) );
				}
			}
			return $new_columns;
		} else {
			return $columns;
		}
	}, 10, 2 );
	foreach ($post_types as $post_type ) {
		add_action( "manage_{$post_type}_posts_custom_column", function( $column, $post_id ) use ( $post_type ) {
			switch ( $column ) {
				case 'expires':
					$will_expire = tscp_will_expire( $post_id );
					if ( is_wp_error( $will_expire ) ) {
						printf(
							'<span title="%s" class="dashicons dashicons-no"></span>',
							esc_attr( sprintf(
								'%s: %s',
								$will_expire->get_error_message(),
								get_post_meta( $post_id, '_tscp_expires', true )
							) )
						);
					} elseif ( ! $will_expire ) {
						printf( '<span title="%s" class="dashicons dashicons-minus"></span>', '' );
					} else {
						printf( '<span title="%s" class="dashicons dashicons-yes"></span>', esc_attr( get_post_meta( $post_id, '_tscp_expires', true ) ) );
					}
					break;
				default:
					// Do nothing
					break;
			}
		}, 10, 2 );
	}
} );
