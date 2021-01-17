<?php
/**
 * Plugin Name: Kafkai - AI Writer Plugin
 * Description: Kafkai is a machine-learning algorithm that can write articles from scratch. Cutting-edge technology for marketers and SEOs.
 * Version: @##VERSION##@
 * Requires at least: 4.2
 * Requires PHP: 7.3
 * Author: Kafkai
 * Text Domain: kafkai
 * Domain Path: i18n
 * Author URI: https://kafkai.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Niteo\Kafkai\Plugin;

// Stop execution if the file is called directly
defined( 'ABSPATH' ) || exit;

// Composer autoloder file
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Plugin class where all the action happens.
 *
 * @category    Plugins
 * @package     Niteo\Kafkai\Plugin
 */
class KafKai {

	/**
	 * Class Constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		new Config();
		new Admin();
	}

	/**
	 * Loads textdomain for the plugin.
	 *
	 * @return void
	 */
	public function load_textdomain() : void {
		load_plugin_textdomain( Config::PLUGIN_SLUG, false, Config::$plugin_path . 'i18n/' );
	}

	/**
	 * Attached to the activation hook.
	 */
	public function activate() {
		/**
		 * @todo Nothing to be done here for now.
		 */
	}

}

// Initialize plugin
$plugin = new KafKai();

// Tasks to be taken care of on activation
register_activation_hook( __FILE__, array( $plugin, 'activate' ) );
