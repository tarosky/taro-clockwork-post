<?php
/**
 * Timezone handling test
 *
 * @package tscp
 */

/**
 * Ensure date comparison works for any site timezone.
 */
class Tscp_Timezone_Test extends WP_UnitTestCase {

	/**
	 * Timezone settings to test.
	 *
	 * @return array
	 */
	public function timezone_provider() {
		return [
			'UTC-5'            => [ -5, '' ],
			'UTC+0'            => [ 0, '' ],
			'UTC+9'            => [ 9, '' ],
			'UTC+5.5'          => [ 5.5, '' ],
			'UTC-3.5'          => [ -3.5, '' ],
			'America/New_York' => [ 0, 'America/New_York' ],
			'Asia/Kolkata'     => [ 0, 'Asia/Kolkata' ],
		];
	}

	/**
	 * Set site timezone.
	 *
	 * @param float  $offset          GMT offset.
	 * @param string $timezone_string Named timezone.
	 */
	private function set_timezone( $offset, $timezone_string ) {
		update_option( 'gmt_offset', $offset );
		update_option( 'timezone_string', $timezone_string );
	}

	/**
	 * Get local time string calculated independently from the plugin.
	 *
	 * Uses wall-clock arithmetic in the target zone so that DST transitions do not affect the result.
	 *
	 * @param float  $offset          GMT offset.
	 * @param string $timezone_string Named timezone.
	 * @param string $modify          Relative modifier such as "-3 days +10 minutes".
	 * @return string
	 */
	private function local_time( $offset, $timezone_string, $modify ) {
		if ( $timezone_string ) {
			$zone = new DateTimeZone( $timezone_string );
		} else {
			$minutes = (int) round( abs( $offset ) * 60 );
			$zone    = new DateTimeZone( sprintf( '%s%02d:%02d', $offset < 0 ? '-' : '+', intdiv( $minutes, 60 ), $minutes % 60 ) );
		}
		$date = new DateTime( 'now', $zone );
		$date->modify( $modify );
		return $date->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Create a published post that should expire.
	 *
	 * @param string $expires Expiration in local time.
	 * @return int
	 */
	private function create_post( $expires ) {
		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		update_post_meta( $post_id, '_tscp_should_expire', 1 );
		update_post_meta( $post_id, '_tscp_expires', $expires );
		return $post_id;
	}

	/**
	 * Test tscp_will_expire() for each timezone.
	 *
	 * A 10 minute margin detects fractional offsets truncated to whole hours.
	 *
	 * @dataProvider timezone_provider
	 * @param float  $offset          GMT offset.
	 * @param string $timezone_string Named timezone.
	 */
	public function test_will_expire( $offset, $timezone_string ) {
		$this->set_timezone( $offset, $timezone_string );
		$future = $this->create_post( $this->local_time( $offset, $timezone_string, '+10 minutes' ) );
		$this->assertTrue( tscp_will_expire( $future ) );
		$past = $this->create_post( $this->local_time( $offset, $timezone_string, '-10 minutes' ) );
		$this->assertWPError( tscp_will_expire( $past ) );
	}

	/**
	 * Test days since last cron execution for each timezone.
	 *
	 * @dataProvider timezone_provider
	 * @param float  $offset          GMT offset.
	 * @param string $timezone_string Named timezone.
	 */
	public function test_days_since_last_execution( $offset, $timezone_string ) {
		$this->set_timezone( $offset, $timezone_string );
		delete_option( 'tscp_cron_executed' );
		$this->assertFalse( tscp_days_since_last_execution() );
		update_option( 'tscp_cron_executed', $this->local_time( $offset, $timezone_string, '-3 days +10 minutes' ) );
		$this->assertSame( 2, tscp_days_since_last_execution() );
		update_option( 'tscp_cron_executed', $this->local_time( $offset, $timezone_string, '-3 days -10 minutes' ) );
		$this->assertSame( 3, tscp_days_since_last_execution() );
	}

	/**
	 * Admin notice must not throw on negative or fractional offsets.
	 *
	 * @dataProvider timezone_provider
	 * @param float  $offset          GMT offset.
	 * @param string $timezone_string Named timezone.
	 */
	public function test_admin_notice( $offset, $timezone_string ) {
		$this->set_timezone( $offset, $timezone_string );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		update_option( 'tscp_cron_executed', $this->local_time( $offset, $timezone_string, '-5 days -10 minutes' ) );
		ob_start();
		do_action( 'admin_notices' );
		$output = ob_get_clean();
		$this->assertStringContainsString( 'doesn\'t work for 5 days', html_entity_decode( $output, ENT_QUOTES ) );
	}
}
