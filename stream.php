<?php
/**
 * Plugin Name: Stream
 * Plugin URI: https://wp-stream.com/
 * Description: Stream tracks logged-in user activity so you can monitor every change made on your WordPress site in beautifully organized detail. All activity is organized by context, action and IP address for easy filtering. Developers can extend Stream with custom connectors to log any kind of action.
 * Version: 3.0.1
 * Author: Stream
 * Author URI: https://wp-stream.com/
 * License: GPLv2+
 * Text Domain: stream
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 WP Stream Pty Ltd (https://wp-stream.com/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

if ( ! version_compare( PHP_VERSION, '5.3', '>=' ) ) {
	load_plugin_textdomain( 'stream', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	add_action( 'shutdown', 'wp_stream_fail_php_version' );
} else {
	require __DIR__ . '/classes/class-plugin.php';
	$GLOBALS['wp_stream'] = new WP_Stream\Plugin();
}

/**
 * Invoked when the PHP version check fails
 * Load up the translations and add the error message to the admin notices.
 */
function wp_stream_fail_php_version() {
	load_plugin_textdomain( 'stream', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	$message      = esc_html__( 'Stream requires PHP version 5.3+, plugin is currently NOT ACTIVE.', 'stream' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );

	echo wp_kses_post( $html_message );
}

/**
 * Helper for external plugins which wish to use Stream
 *
 * @return WP_Stream\Plugin
 */
function wp_stream_get_instance() {
	return $GLOBALS['wp_stream'];
}

// function remote_request($url, $args){
//   if( get_option('site_key')==false) {

//     $args = array(
//       'headers'   => array(),
//       'method'    => 'GET',
//       'body'      => '',
//       'sslverify' => true,
//     );
//     // $args['headers']['Stream-Site-API-Key'] = $this->api_key;
//     $args['headers']['Content-Type'] = 'application/json';
//     $request = wp_remote_request( $url, $args );
//     if ( ! is_wp_error( $request ) ) {
//       $data = apply_filters( 'wp_stream_api_request_data', json_decode( $request['body'] ), $url, $args );

//       if ( 200 == $request['response']['code'] || 201 == $request['response']['code'] ) {
//         error_log("got data: $data");
//       } else {
//         // Disconnect if unauthorized or no longer exists, loose comparison needed
//         if ( 403 == $request['response']['code'] || 410 == $request['response']['code'] ) {
//           error_log("got error: $request['response']['code']");
//         }

//       }
//     }
//   }
// }

// function detect_plugin_activation(  $plugin, $network_activation ) {
//   if(substr($plugin, -10) == 'stream.php'){
//     wp_stream_get_instance()->
//   } else {
//     error_log("Yep its there.");
//     // update_option('site_key','ABCDEFG');
//   }
// }
// add_action( 'activated_plugin', 'detect_plugin_activation', 10, 2 );
