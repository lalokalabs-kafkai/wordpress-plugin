<?php

/**
 * Plugin Name: Kafkai for WordPress
 * Description:
 * Version: 1.0.0
 * Runtime: 5.6+
 * Author: Niteo
 * Text Domain: kafkai-wp
 * Domain Path: i18n
 * Author URI: https://kafkai.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Niteo\KafKai\Plugin;

// Stop execution if the file is called directly
defined( 'ABSPATH' ) || exit;

// Composer autoloder file
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Plugin class where all the action happens.
 *
 * @category    Plugins
 * @package     Niteo\KafKai\Plugin
 * @since       1.0.0
 */
class KafKai {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

	}

	/**
	 * Attached to the activation hook.
	 */
	public function activate() {
		/**
		 * @todo Nothing to be done here for now.
		 */
	}


	/**
	 * Attached to the de-activation hook.
	 */
	public function deactivate() {
		/**
		 * @todo Nothing to be done here for now.
		 */
	}

}

// Initialize plugin
$kafkai_wp = new KafKai();

/**
 * Hooks for plugin activation & deactivation.
 */
register_activation_hook( __FILE__, array( $kafkai_wp, 'activate' ) );
register_deactivation_hook( __FILE__, array( $kafkai_wp, 'deactivate' ) );
