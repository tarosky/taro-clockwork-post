<?php
/**
 * Live Preview のデモデータ。
 *
 * シナリオ: 架空のコーヒー豆店「Northside Coffee Roasters」の期間限定キャンペーン告知。
 * 期限が近いキャンペーン 2 件、期限切れで非公開になった 1 件、期限なしの通常記事 3 件。
 * 着地はキャンペーン記事（ID 1000）のブロックエディタで、「概要」パネルの期限 UI を見せる。
 *
 * このファイルがマスター。編集したら次で blueprint.json に埋め込むこと:
 *   php <skill>/scripts/blueprint.php embed .
 */
require '/wordpress/wp-load.php';

// runPHP はログインユーザー無しで動く。権限チェックのある API（tax_input など）が黙って無視されるので、管理者として実行する。
wp_set_current_user( 1 );

update_option( 'blogname', 'Northside Coffee Roasters' );
update_option( 'blogdescription', 'Small-batch coffee, roasted weekly' );
// 期限はサイトのローカル時刻で保存・比較される。タイムゾーンは既定の UTC のままにする
// （1.2.8 時点では gmt_offset が負だと DateInterval( 'PT-5H' ) で管理画面が Fatal error になるため）。

// 期限切れの対象投稿タイプ（既定でも post だが明示する）。
update_option( 'tscp_post_types', array( 'post' ) );

/**
 * 投稿を作る。
 *
 * @param string   $title       Title.
 * @param string   $body        Body text.
 * @param int      $days_ago    Published N days ago.
 * @param int|null $expire_days Days from now to expire (negative = already expired). Null = no expiration.
 * @param int      $import_id   Fixed post ID (0 = auto).
 * @return int
 */
$create_post = function ( $title, $body, $days_ago, $expire_days = null, $import_id = 0 ) {
	$args = array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_content' => "<!-- wp:paragraph -->\n<p>" . $body . "</p>\n<!-- /wp:paragraph -->",
		'post_date'    => wp_date( 'Y-m-d H:i:s', time() - $days_ago * DAY_IN_SECONDS ),
	);
	if ( $import_id ) {
		$args['import_id'] = $import_id;
	}
	$post_id = wp_insert_post( $args );
	if ( null !== $expire_days ) {
		update_post_meta( $post_id, '_tscp_should_expire', 1 );
		update_post_meta( $post_id, '_tscp_expires', wp_date( 'Y-m-d H:i:59', time() + $expire_days * DAY_IN_SECONDS ) );
	}
	return $post_id;
};

// 期限なしの通常記事。
$create_post( 'Meet Our New Roaster', 'Our new 15 kg drum roaster arrived this month. Here is how it changes the way we roast.', 40 );
$create_post( 'How to Dial In Your Pour-Over', 'Grind size, water temperature and bloom time: a simple routine for a better cup at home.', 25 );
$create_post( 'Single Origin vs. Blend', 'What the label tells you, and how to pick a bag that suits the way you brew.', 12 );

// 期限切れのキャンペーン。期限を過去にして公開し、最後に期限切れ処理を走らせて非公開にする。
$create_post( 'Summer Cold Brew Sale: 20% Off', 'Our summer cold brew sale has ended. Thank you to everyone who joined in!', 30, -3 );

// 期限が近いキャンペーン。着地ページは ID を固定した 1 件目。
$create_post( 'Weekend Flash Sale: Free Shipping', 'Free shipping on every order this weekend only. This announcement is set to expire automatically when the sale ends, so it disappears from the site without anyone having to remember to unpublish it.', 1, 2, 1000 );
$create_post( 'Holiday Blend Pre-Orders Are Open', 'Reserve a bag of our limited holiday blend. Pre-orders close in six days, and this post will be unpublished automatically at the deadline.', 2, 6 );

// 期限切れ処理（通常は毎分の cron）をその場で 1 回実行する。Playground の WP-Cron は当てにならないため。
do_action( 'tscp_event_handler' );

// ブロックエディタのウェルカムガイドを閉じておく（期限 UI が隠れないように）。
update_user_meta( 1, $GLOBALS['wpdb']->get_blog_prefix() . 'persisted_preferences', array(
	'core/edit-post' => array(
		'welcomeGuide' => false,
	),
	'_modified'      => gmdate( 'c' ),
) );

// 不要な既定コンテンツを片付ける。
wp_delete_post( 1, true ); // Hello world!
wp_delete_post( 2, true ); // Sample Page
