<?php
/**
 * Admin class for the plugin.
 */

namespace Niteo\Kafkai\Plugin;

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
		$menu = add_menu_page(
			esc_html__( 'Import Article from Kafkai', 'kafkai-wp' ),
			esc_html__( 'Kafkai', 'kafkai-wp' ),
			'manage_options',
			Config::PLUGIN_PREFIX . 'admin',
			array( $this, 'import' ),
			'dashicons-format-aside',
			26
		);

		// Submenu page
		$sub_menu = add_submenu_page(
			Config::PLUGIN_PREFIX . 'admin',
			esc_html__( 'Generate Post with Kafkai', 'kafkai-wp' ),
			esc_html__( 'Generate Post', 'kafkai-wp' ),
			'manage_options',
			Config::PLUGIN_PREFIX . 'generate',
			array( $this, 'generate' ),
		);

		// Settings page
		$settings = add_submenu_page(
			Config::PLUGIN_PREFIX . 'admin',
			esc_html__( 'Kafkai Settings', 'kafkai-wp' ),
			esc_html__( 'Settings', 'kafkai-wp' ),
			'manage_options',
			Config::PLUGIN_PREFIX . 'settings',
			array( $this, 'settings' ),
		);

		// Load JS conditionally
		add_action( 'load-' . $menu, array( $this, 'load_scripts' ) );
		add_action( 'load-' . $sub_menu, array( $this, 'load_scripts' ) );
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
		require_once Config::$plugin_path . 'inc/admin/views/import.php';
	}

	/**
	 * Displays generate page for the plugin.
	 *
	 * @return void
	 */
	public function generate() : void {
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
		$this->get_options();

		require_once Config::$plugin_path . 'inc/admin/views/settings.php';
	}

}
