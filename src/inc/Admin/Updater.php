<?php
/**
 * Niche updater for the plugin.
 */

namespace Niteo\Kafkai\Plugin\Admin;

use Niteo\Kafkai\Plugin\Config;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class for checking niches in the API and respective logos.
 *
 * @package Niteo\Kafkai\Plugin
 */
class Updater {

	/**
	 * @var array
	 */
	public $response = array();

	/**
	 * @var string
	 */
	private $_apiurl = 'https://app.kafkai.com';

	/**
	 * Check for new niches when one of the plugin page is active.
	 */
	public function __construct() {
		add_action( 'current_screen', array( $this, 'check_niches' ) );
		add_action( 'admin_notices', array( $this, 'add_notification' ) );
		add_action( Config::PLUGIN_PREFIX . 'settings', array( $this, 'add_update_niche_button' ) );
	}

	/**
	 * Queries the API for niches.
	 *
	 * @return void
	 */
	public function check_niches() : void {
		$screen = \get_current_screen();

		// Check if the request is made within the plugin.
		if ( ! $this->is_plugin_page( $screen->id ) ) {
			return;
		}

		// Check for transient.
		$transient = get_transient( Config::PLUGIN_PREFIX . 'temporary_niche_data' );

		if ( ! $transient ) {
			$data = $this->api_call();

			// API call returned false for some reason.
			// It will be retried on the next page load of the plugin.
			if ( ! $data ) {
				return;
			}

			// We have data from the API to be processed.
			$this->yaml_parse_and_check( $data );
			return;
		}

		// We have data from transient to be processed.
		$this->yaml_parse_and_check( $transient );
	}

	/**
	 * Make API call to get updated niches.
	 */
	public function api_call() {
		// Make an API call.
		$api = new Api();
		$api->set_apiurl( $this->_apiurl );
		$call = $api->call( '/openapi.yaml', 'GET' );

		if ( ! $call ) {
			return false;
		}

		// We have response from the API call.
		$data = $api->response;

		// Set transient for 24 hours.
		set_transient( Config::PLUGIN_PREFIX . 'temporary_niche_data', $data, 60 * 60 * 24 );

		return $data;
	}

	/**
	 * Convert YAML to array and check for niches.
	 *
	 * @param string $data YAML string to be parsed.
	 * @return void
	 */
	public function yaml_parse_and_check( string $data ) : void {
		if ( get_transient( Config::PLUGIN_PREFIX . 'new_niches' ) ) {
			return;
		}

		if ( function_exists( 'yaml_parse' ) ) {
			$parsed_data = yaml_parse( $data );
		} else {
			try {
				$parsed_data = Yaml::parse( $data );
			} catch ( ParseException $exception ) {
				// If WP_DEBUG is enabled, error will be logged.
				error_log( $exception->getMessage() );
			}
		}

		if ( ! isset( $parsed_data['components']['schemas']['niche']['enum'] ) ) {
			return;
		}

		// Convert niches into the right format.
		$fetched_niches = $parsed_data['components']['schemas']['niche']['enum'];
		$niches         = array();

		foreach ( $fetched_niches as $niche ) {
			if ( isset( $niches[ $niche ] ) ) {
				continue;
			}

			// Split string by uppercase for display.
			$niche_name = preg_replace( '/(?<!\ )[A-Z]/', ' $0', $niche );

			// Special replacements for Home & Family, SEO niches
			$niche_name = str_replace( 'And', 'and', $niche_name );
			$niche_name = str_replace( 'Seo', 'SEO', $niche_name );

			$niches[ $niche ] = sanitize_text_field( $niche_name );
		}

		// Check for any new niches by finding difference of two arrays.
		$new_niches = array_diff( $niches, Config::$niches );

		// Store the niches as transient for the notification to update.
		// Set to 7 days.
		if ( ! empty( $new_niches ) ) {
			set_transient( Config::PLUGIN_PREFIX . 'new_niches', $new_niches, 60 * 60 * 24 * 7 );
		}
	}

	/**
	 * Adds a notification to update niches if the transient is present.
	 *
	 * @return void
	 */
	public function add_notification() : void {
		global $current_screen;

		if ( ! $this->is_plugin_page( $current_screen->id ) ) {
			return;
		}

		$niche_transient = get_transient( Config::PLUGIN_PREFIX . 'new_niches' );

		if ( ! $niche_transient ) {
			return;
		}

		echo '<div class="notice notice-info"><p>';
		echo sprintf(
			esc_html__( 'New niches are available for the plugin. Please go to %1$sSettings page%2$s to update.', 'kafkai' ),
			'<a href="' . self_admin_url( 'admin.php?page=' . Config::PLUGIN_PREFIX . 'settings' ) . '">',
			'</a>'
		);
		echo '</p></div>';
	}

	/**
	 * Add update niche button to `Settings` page.
	 *
	 * @return void
	 */
	public function add_update_niche_button() : void {
		echo '&nbsp;<input type="submit" name="' . Config::PLUGIN_PREFIX . 'update_niches" value="' . esc_html__( 'Update Niches', 'kafkai' ) . '" class="button button-secondary">';
	}

	/**
	 * Verifies whether the current page belongs to the plugin or not.
	 *
	 * @param string $page ID of the current page
	 * @return boolean
	 */
	protected function is_plugin_page( $page ) : bool {
		return false !== strpos( $page, Config::PLUGIN_PREFIX );
	}

}
