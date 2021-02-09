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
