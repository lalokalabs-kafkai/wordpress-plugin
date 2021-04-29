<?php
/**
 * Configuration file for the plugin.
 */

namespace Niteo\Kafkai\Plugin;

/**
 * Set configuration options and plugin environment.
 *
 * @package Niteo\Kafkai\Plugin
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
	public static $plugin_name;

	/**
	 * @var array
	 */
	public static $niches = array();

	/**
	 * @var array
	 */
	public static $languages = array();

	/**
	 * @var string
	 */
	const PLUGIN_BASE = 'kafkai/kafkai.php';

	/**
	 * @var string
	 */
	const PLUGIN_SLUG = 'kafkai';

	/**
	 * @var string
	 */
	const PLUGIN_PREFIX = 'kafkaiwp_';

	/**
	 * @var string
	 */
	const PLUGIN_VERSION = '@##VERSION##@';

	/**
	 * @var string
	 */
	const MINIMUM_PHP_VERSION = '7.3';

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
		// Manually added niches.
		self::$niches = array(
			'Affiliate'       => esc_html__( 'Affiliate', 'kafkai' ),
			'Automotive'      => esc_html__( 'Automotive', 'kafkai' ),
			'Beauty'          => esc_html__( 'Beauty', 'kafkai' ),
			'Business'        => esc_html__( 'Business', 'kafkai' ),
			'Careers'         => esc_html__( 'Careers', 'kafkai' ),
			'CyberSecurity'   => esc_html__( 'Cyber Security', 'kafkai' ),
			'Dental'          => esc_html__( 'Dental', 'kafkai' ),
			'Dating'          => esc_html__( 'Dating', 'kafkai' ),
			'Dogs'            => esc_html__( 'Dogs', 'kafkai' ),
			'Education'       => esc_html__( 'Education', 'kafkai' ),
			'Fashion'         => esc_html__( 'Fashion', 'kafkai' ),
			'Finance'         => esc_html__( 'Finance', 'kafkai' ),
			'Food'            => esc_html__( 'Food', 'kafkai' ),
			'Gambling'        => esc_html__( 'Gambling', 'kafkai' ),
			'Gaming'          => esc_html__( 'Gaming', 'kafkai' ),
			'Gardening'       => esc_html__( 'Gardening', 'kafkai' ),
			'General'         => esc_html__( 'General', 'kafkai' ),
			'Health'          => esc_html__( 'Health', 'kafkai' ),
			'HomeAndFamily'   => esc_html__( 'Home and Family', 'kafkai' ),
			'HomeImprovement' => esc_html__( 'Home Improvement', 'kafkai' ),
			'Nutrition'       => esc_html__( 'Nutrition', 'kafkai' ),
			'OnlineMarketing' => esc_html__( 'Online Marketing', 'kafkai' ),
			'Outdoors'        => esc_html__( 'Outdoors', 'kafkai' ),
			'RealEstate'      => esc_html__( 'Real Estate', 'kafkai' ),
			'SelfImprovement' => esc_html__( 'Self Improvement', 'kafkai' ),
			'Seo'             => esc_html__( 'SEO', 'kafkai' ),
			'Sexuality'       => esc_html__( 'Sexuality', 'kafkai' ),
			'Software'        => esc_html__( 'Software', 'kafkai' ),
			'Spirituality'    => esc_html__( 'Spirituality', 'kafkai' ),
			'Sports'          => esc_html__( 'Sports', 'kafkai' ),
			'Technology'      => esc_html__( 'Technology', 'kafkai' ),
			'Travel'          => esc_html__( 'Travel', 'kafkai' ),
			'WeightLoss'      => esc_html__( 'Weight Loss', 'kafkai' ),
		);

		// Manually added languages.
		self::$languages = array(
			'Dutch'   => esc_html__( 'Dutch', 'kafkai' ),
			'English' => esc_html__( 'English', 'kafkai' ),
			'French'  => esc_html__( 'French', 'kafkai' ),
			'German'  => esc_html__( 'German', 'kafkai' ),
			'Italian' => esc_html__( 'Italian', 'kafkai' ),
			'Spanish' => esc_html__( 'Spanish', 'kafkai' ),
			'Swedish' => esc_html__( 'Swedish', 'kafkai' ),
		);

		// Plugin details.
		self::$plugin_name = esc_html__( 'Kafkai', 'kafkai' );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_init', array( $this, 'add_plugin_notices' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
	}

	/**
	 * Set URL & path to plugin directory.
	 *
	 * @return void
	 */
	public function init() : void {
		// Check for niches & languages from options.
		self::$niches    = $this->updated_data( self::$niches, 'niches' );
		self::$languages = $this->updated_data( self::$languages, 'languages' );

		self::$plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
		self::$plugin_path = plugin_dir_path( dirname( __FILE__ ) );
	}

	/**
	 * Checks for data in the options table to verify if it's
	 * the updated one.
	 *
	 * @param array  $data  Data to check against.
	 * @param string $type Data type to check for in DB.
	 * @return array
	 */
	public function updated_data( array $data, string $type ) : array {
		$db_data = get_option( self::PLUGIN_PREFIX . $type );

		// Verify $db_data count to use the updated one.
		if ( $db_data ) {
			/**
			 * Use data from the options if it contains more entries than
			 * the manually updated one.
			 */
			if ( count( $db_data ) > count( $data ) ) {
				return $db_data;
			}
		}

		return $data;
	}

	/**
	 * Checks the environment on loading WordPress, just in case the
	 * environment changes after activation.
	 *
	 * @return void
	 */
	public function check_environment() : void {
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
	public function is_environment_compatible() : bool {
		return version_compare( PHP_VERSION, $this->get_php_version(), '>=' );
	}

	/**
	 * Adds notices for out-of-date WordPress version.
	 *
	 * @return void
	 */
	public function add_plugin_notices() : void {
		if ( ! $this->is_wp_compatible() ) {
			$this->add_admin_notice(
				'update_wordpress',
				'error',
				sprintf(
					esc_html__( '%1$s requires WordPress version %2$s or higher. Please %3$supdate WordPress &raquo;%4$s', 'kafkai' ),
					$this->get_plugin_name(),
					$this->get_wp_version(),
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
					'</a>'
				)
			);
		}
	}

	/**
	 * Displays any admin notices added with add_admin_notice()
	 *
	 * @return void
	 */
	public function admin_notices() : void {
		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo '<div class="' . esc_attr( $notice['class'] ) . '">';
			echo '<p>' . wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @param string $slug the slug for the notice
	 * @param string $class the css class for the notice
	 * @param string $message the notice message
	 *
	 * @return void
	 */
	private function add_admin_notice( $slug, $class, $message ) : void {
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
	public function get_environment_message() : string {
		return sprintf(
			esc_html__( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'kafkai' ),
			$this->get_php_version(),
			PHP_VERSION
		);
	}

	/**
	 * Determines if the WordPress compatible.
	 *
	 * @return bool
	 */
	public function is_wp_compatible() : bool {
		if ( ! $this->get_wp_version() ) {
			return true;
		}

		return version_compare( get_bloginfo( 'version' ), $this->get_wp_version(), '>=' );
	}

	/**
	 * Returns PLUGIN_NAME.
	 *
	 * @return string
	 */
	public function get_plugin_name() : string {
		return self::$plugin_name;
	}

	/**
	 * Returns PLUGIN_BASE.
	 *
	 * @return string
	 */
	public function get_plugin_base() : string {
		return self::PLUGIN_BASE;
	}

	/**
	 * Returns MINIMUM_PHP_VERSION.
	 *
	 * @return string
	 */
	public function get_php_version() : string {
		return self::MINIMUM_PHP_VERSION;
	}

	/**
	 * Returns MINIMUM_WP_VERSION.
	 *
	 * @return string
	 */
	public function get_wp_version() : string {
		return self::MINIMUM_WP_VERSION;
	}

	/**
	 * Deactivates the plugin.
	 *
	 * @return void
	 */
	protected function deactivate_plugin() : void {
		deactivate_plugins( $this->get_plugin_base() );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

}
