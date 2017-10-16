<?php
/**
 * Schedule handler
 *
 * @package tscp
 */

define( 'TSCP_SCHEDULE_NAME', 'tscp_interval' );
define( 'TSCP_EVENT_NAME', 'tscp_event_handler' );


// Register schedule
add_filter( 'cron_schedules', function ( array $schedules ) {
	/**
	 * tscp_cron_interval
	 *
	 * @package tscp
	 *
	 * @param int $second Cron interval. Default is 60.
	 *
	 * @return int
	 */
	$cron_interval                   = apply_filters( 'tscp_cron_interval', 60 );
	$schedules[ TSCP_SCHEDULE_NAME ] = [
		'interval' => $cron_interval,
		// translators: %d means cron interval in second.
		'display'  => sprintf( __( 'Post expiration interval of %d seconds.', 'tscp' ), $cron_interval ),
	];

	return $schedules;
} );

// Register cron if not.
add_action( 'init', function () {
	if ( ! wp_next_scheduled( TSCP_EVENT_NAME ) ) {
		wp_schedule_event( current_time( 'timestamp', true ), TSCP_SCHEDULE_NAME, TSCP_EVENT_NAME );
	}
} );

// Cron handler to expire all.
add_action( TSCP_EVENT_NAME, function () {
	foreach ( tscp_get_expired_posts() as $post ) {
		/**
		 * tscp_expired_status
		 *
		 * @package tscp
		 *
		 * @param string|bool $status Default is 'private'.
		 * @param WP_Post $post Post object to be expired
		 *
		 * @return string Post status. If false or empty string, no action will be executed.
		 */
		$expired_status = apply_filters( 'tscp_expired_status', 'private', $post );
		if ( $expired_status ) {
			wp_update_post( [
				'ID'          => $post->ID,
				'post_status' => $expired_status,
			] );
		}
		/**
		 * tscp_post_expired
		 *
		 * Executed after post expired.
		 *
		 * @param WP_Post $post
		 */
		do_action( 'tscp_post_expired', $post );
	}
	// Save last executed time.
	update_option( 'tscp_cron_executed', current_time( 'mysql' ) );
} );

// Register debug cron.
add_action( 'admin_notices', function () {
	if ( current_user_can( 'manage_options' ) ) {
		$last_executed = get_option( 'tscp_cron_executed', false );
		if ( ! $last_executed ) {
			$message = __( 'Taro Clockwork Post is never executed. Please check if your cron task works.', 'tscp' );
		} else {
			$executed = new DateTime( $last_executed );
			$now      = new DateTime();
			$now->add( new DateInterval( sprintf( 'PT%sH', get_option( 'gmt_offset' ) ) ) );
			$diff = $now->diff( $executed );
			if ( $diff->days > 2 ) {
				// translators: %d means days.
				$message = sprintf( __( 'Taro Clockwork Post doesn\'t work for %d days. Please check if your cron task works.', 'tscp' ), $diff->days );
			} else {
				return;
			}
		}
		printf( '<div class="error"><p>%s</p></div>', esc_html( $message ) );
	}
} );
