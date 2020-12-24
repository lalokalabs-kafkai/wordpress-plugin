<?php
/**
 * Plugin Name: Kafkai - AI Writer Plugin
 * Description: Plugin to generate and/or import articles from Kafkai. It also fetches the corresponding featured image along with YouTube video for the article and adds it to the database.
 * Version: @##VERSION##@
 * Runtime: 7.2+
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
