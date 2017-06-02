Taro Clockwork Post
===============================================

Contributors: tarosky,Takahashi_Fumiki  
Tags: post, media, expiration  
Requires at least: 4.7.0  
Tested up to: 4.7.5  
Stable tag: 1.0.0  
License: GPLv3 or later  
License URI: http://www.gnu.org/licenses/gpl-3.0.txt  

A WordPress plugin to expire post with specified date.

## Description

You can enter expiration date on edit screen.
This plugin runs cron every minute to search expired posts and make them `private`.

**NOTICE** This plugin requires PHP 5.4 and over.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/taro-clockwork-post` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Go to `Setting > Reading` and choose post type to expiration ready.

## Frequently Asked Questions

### How to avoid post status to be private

Private is the default post status but if you want another case, use filter hook for that.

```
<?php
// Filter status if post type is `product`
add_filter( 'tscp_expired_status', function( $status, $post ) {
   if ( 'product' == $post->post_type ) {
       $status = 'no-stock';
   }
   return $status;
}, 10, 2 );
```

If status is `false`, this plugin doesn't change post status.
In such situation, you might need adding any custom field to post.
Use another action which will occur just after `tscp_epired_status`.

```
<?php
// do something just after post status is/isn't changed.
add_action( 'tscp_post_expired', function( $post ) {
   // Post is still publish,
   // But add some custom fields
   update_post_meta( $post->ID, '_not_in_front_page', true );
} );
```

### Change frequency of expiration check

If you are low-resource environment, you might need low frequency.
For example, you site allow post to be expired within 10 min.
Use hook to delay interval.

```
<?php
add_filter( 'tscp_cron_interval', function() {
  // Change interval from 60 sec to 600 sec.
  return 600;
} );
```

## Changelog

### 1.0.0

* Initial release.