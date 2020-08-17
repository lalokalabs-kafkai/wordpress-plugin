<?php
/**
 * Configuration file for the plugin.
 */

namespace Niteo\KafKai\Plugin;

/**
 * Set configuration options.
 *
 * @package Niteo\KafKai\Plugin
 */
class Config {

	/**
	 * @var string
	 */
	public static $plugin_url;

	/**
	 * @var string
	 */
	public static $plugin_path;

	/**
	 * @var string
	 */
	const PLUGIN_NAME = 'Kafkai for WordPress';

	/**
	 * @var string
	 */
	const PLUGIN_BASE = 'kafkai-wp/kafkai-wp.php';

	/**
	 * @var string
	 */
	const PLUGIN_VERSION = '@##VERSION##@';

	/**
	 * @var string
	 */
	const MINIMUM_PHP_VERSION = '5.6';

	/**
	 * @var string
	 */
	const MINIMUM_WP_VERSION = '4.2.0';

	/**
	 * @var array
	 */
	public $notices = array();

	/**
	 * Class constructor.
	 */
	public function __construct() {
		self::$plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
		self::$plugin_path = plugin_dir_path( dirname( __FILE__ ) );

		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_init', array( $this, 'add_plugin_notices' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
	}

	/**
	 * Checks the environment on loading WordPress, just in case the environment changes after activation.
	 */
	public function check_environment() {
		if ( $this->is_environment_compatible() ) {
			return;
		}

		$this->deactivate_plugin();
		$this->add_admin_notice( 'bad_environment', 'error', $this->get_plugin_name() . ' has been deactivated. ' . $this->get_environment_message() );
	}

	/**
	 * Determines if the server environment is compatible with this plugin.
	 *
	 * @return bool
	 */
	public function is_environment_compatible() {
		return version_compare( PHP_VERSION, $this->get_php_version(), '>=' );
	}

	/**
	 * Adds notices for out-of-date WordPress and/or WooCommerce versions.
	 */
	public function add_plugin_notices() {
		// Check for WP version
		if ( ! $this->is_wp_compatible() ) {
			$this->add_admin_notice(
				'update_wordpress',
				'error',
				sprintf(
					esc_html__( '%1$s requires WordPress version %2$s or higher. Please %3$supdate WordPress &raquo;%4$s', 'kafkai-wp' ),
					$this->get_plugin_name(),
					$this->get_wp_version(),
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
					'</a>'
				)
			);
		}
	}

	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @param string $slug the slug for the notice
	 * @param string $class the css class for the notice
	 * @param string $message the notice message
	 */
	private function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message,
		);
	}

	/**
	 * Gets the message for display when the environment is incompatible with this plugin.
	 *
	 * @return string
	 */
	public function get_environment_message() {
		return sprintf(
			esc_html__( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'kafkai-wp' ),
			$this->get_php_version(),
			PHP_VERSION
		);
	}

	/**
	 * Determines if the WordPress compatible.
	 *
	 * @return bool
	 */
	public function is_wp_compatible() {
		if ( ! $this->get_wp_version() ) {
			return true;
		}

		return version_compare( get_bloginfo( 'version' ), $this->get_wp_version(), '>=' );
	}

	/**
	 * Returns PLUGIN_NAME.
	 */
	public function get_plugin_name() {
		return self::PLUGIN_NAME;
	}

	/**
	 * Returns PLUGIN_BASE.
	 */
	public function get_plugin_base() {
		return self::PLUGIN_BASE;
	}

	/**
	 * Returns MINIMUM_PHP_VERSION.
	 */
	public function get_php_version() {
		return self::MINIMUM_PHP_VERSION;
	}

	/**
	 * Returns MINIMUM_WP_VERSION.
	 */
	public function get_wp_version() {
		return self::MINIMUM_WP_VERSION;
	}

	/**
	 * Deactivates the plugin.
	 */
	protected function deactivate_plugin() {
		deactivate_plugins( $this->get_plugin_base() );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

}
