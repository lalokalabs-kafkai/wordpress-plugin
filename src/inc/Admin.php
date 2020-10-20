<?php
/**
 * Admin class for the plugin.
 */

namespace Niteo\Kafkai\Plugin;

use Niteo\Kafkai\Plugin\Admin\Articles;

/**
 * Admin options for the plugin.
 *
 * @package Niteo\Kafkai\Plugin
 */
class Admin {

	use Extend\Admin;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ), PHP_INT_MAX );
		add_filter( 'plugin_row_meta', array( $this, 'meta_links' ), 10, 2 );
	}

	/**
	 * Adds menu for the plugin.
	 *
	 * @return void
	 */
	public function add_menu() : void {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Parent menu
		add_menu_page(
			esc_html__( 'Kafkai Settings', 'kafkai-wp' ),
			esc_html__( 'Kafkai', 'kafkai-wp' ),
			'manage_options',
			Config::PLUGIN_PREFIX . 'admin',
			array( $this, 'settings' ),
			'dashicons-format-aside',
			26
		);

		// Import page
		$import_page = add_submenu_page(
			Config::PLUGIN_PREFIX . 'admin',
			esc_html__( 'Import Articles', 'kafkai-wp' ),
			esc_html__( 'Import Articles', 'kafkai-wp' ),
			'manage_options',
			Config::PLUGIN_PREFIX . 'import',
			array( $this, 'import' ),
		);

		// Generate page
		$generate_page = add_submenu_page(
			Config::PLUGIN_PREFIX . 'admin',
			esc_html__( 'Generate Article', 'kafkai-wp' ),
			esc_html__( 'Generate Article', 'kafkai-wp' ),
			'manage_options',
			Config::PLUGIN_PREFIX . 'generate',
			array( $this, 'generate' ),
		);

		// Load JS conditionally
		add_action( 'load-' . $generate_page, array( $this, 'load_scripts' ) );
		add_action( 'load-' . $import_page, array( $this, 'load_scripts' ) );
	}

	/**
	 * Adds action to load scripts via the scripts hook for admin.
	 *
	 * @return void
	 */
	public function load_scripts() : void {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Scripts for the plugin options page.
	 *
	 * @return void
	 */
	public function admin_scripts() : void {
		wp_enqueue_style( Config::PLUGIN_SLUG . '-admin', Config::$plugin_url . 'assets/admin/css/admin.css', false, Config::PLUGIN_VERSION );

		// Localize and enqueue script
		wp_enqueue_script( Config::PLUGIN_SLUG . '-admin', Config::$plugin_url . 'assets/admin/js/admin.js', array( 'jquery' ), Config::PLUGIN_VERSION, true );

		$localize = array(
			'prefix' => Config::PLUGIN_PREFIX,
			'nonce'  => wp_create_nonce( Config::PLUGIN_PREFIX . 'nonce' ),
		);

		wp_localize_script( Config::PLUGIN_SLUG . '-admin', Config::PLUGIN_PREFIX . 'admin_l10n', $localize );

		// Thickbox
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
	}

	/**
	 * Adds custom links to the meta on the plugins page.
	 *
	 * @param array  $links Array of links for the plugins
	 * @param string $file  Name of the main plugin file
	 *
	 * @return array
	 */
	public function meta_links( array $links, string $file ) {
		if ( false === strpos( $file, 'kafkai-wp.php' ) ) {
			return;
		}

		// Add website link
		$links[] = '<a href="https://kafkai.com" target="_blank">' . esc_html__( 'Website', 'kafkai-wp' ) . '</a>';

		return $links;
	}

	/**
	 * Displays import page for the plugin.
	 *
	 * @return void
	 */
	public function import() : void {
		/**
		 * Fetch generated articles for the user.
		 * All the processing such as checking for page number and state along
		 * with article fetching is done on class initialization.
		 */
		$articles = new Articles();

		// Set page number and check article state
		$articles->check_page();
		$articles->check_state();

		// Import articles
		$articles->fetch_articles();

		require_once Config::$plugin_path . 'inc/admin/views/import.php';
	}

	/**
	 * Displays generate page for the plugin.
	 *
	 * @return void
	 */
	public function generate() : void {
		// Send request for generating article
		$articles = new Articles();
		$articles->generate_article();

		require_once Config::$plugin_path . 'inc/admin/views/generate.php';
	}

	/**
	 * Displays settings page for the plugin.
	 *
	 * @return void
	 */
	public function settings() : void {
		/**
		 * Process form on submission and grab options from the database
		 * to fill the form values if available.
		 */
		$this->process_settings();

		$settings = $this->get_settings();
		$token    = $this->get_token();

		require_once Config::$plugin_path . 'inc/admin/views/settings.php';
	}

}
