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

/**
 * @todo Write steps for removing plugin traces from the backend.
 */

