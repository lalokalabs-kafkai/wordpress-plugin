<?php
/**
 * File which gets called on plugin uninstall.
 *
 * @since   1.0.0
 * @package Niteo\Kafkai\Plugin
 */

namespace Niteo\Kafkai\Plugin;

// Prevent unauthorized access
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Composer autoloder file
require_once __DIR__ . '/vendor/autoload.php';

global $wpdb;

/**
 * Remove settings from the database.
 */
delete_option( 'kafkaiwp_settings' );
delete_option( 'kafkaiwp_api_user' );
delete_option( 'kafkaiwp_token' );

/**
 * Clear transients matching:
 *
 * _transient_kafkaiwp_%
 * _transient_timeout_kafkaiwp_%
 */
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE %s OR `option_name` LIKE %s",
		'%_transient_kafkaiwp_%',
		'%_transient_timeout_kafkaiwp_%'
	)
);

/**
 * @todo Add option to clear bulk transients when cache is enabled.
 */
if ( (bool) wp_using_ext_object_cache() ) {
	for ( $i = 1; $i < 11; $i++ ) {
		wp_cache_delete( Config::PLUGIN_PREFIX . 'article_All_page' . $i, 'transient' );
	}
}
