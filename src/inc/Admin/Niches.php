<?php
/**
 * Niches for the plugin.
 */

namespace Niteo\Kafkai\Plugin\Admin;

use Niteo\Kafkai\Plugin\Config;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class for checking niches & languages via the API.
 *
 * @package Niteo\Kafkai\Plugin
 */
class Niches {

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
		add_action( 'current_screen', array( $this, 'check_niches_and_languages' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( Config::PLUGIN_PREFIX . 'settings', array( $this, 'add_update_button' ) );
	}

	/**
	 * Queries the API for niches & languages.
	 *
	 * @return void
	 */
	public function check_niches_and_languages() : void {
		$screen = \get_current_screen();

		// Check if the request is made within the plugin.
		if ( ! $this->is_plugin_page( $screen->id ) ) {
			return;
		}

		// Check for transient.
		$data = get_transient( Config::PLUGIN_PREFIX . 'temporary_openapi_data' );

		if ( ! $data ) {
			$data = $this->api_call();

			// API call returned false for some reason.
			// It will be retried on the next page load of the plugin.
			if ( ! $data ) {
				return;
			}
		}

		// We have data to be processed.
		$this->yaml_parse_and_check( $data );
		$this->yaml_parse_and_check( $data, 'languages', 'articleLanguage' );
	}

	/**
	 * Make API call to get updated niches.
	 */
	public function api_call() {
		// Make an API call.
		$api = new API();
		$api->set_apiurl( $this->_apiurl );
		$call = $api->call( '/openapi.yaml', 'GET' );

		if ( ! $call ) {
			return false;
		}

		// We have response from the API call.
		$data = $api->response;

		// Set transient for 24 hours.
		set_transient( Config::PLUGIN_PREFIX . 'temporary_openapi_data', $data, 60 * 60 * 24 );

		return $data;
	}

	/**
	 * Convert YAML to array and check for updates.
	 *
	 * @param string $data YAML string to be parsed.
	 * @return void
	 */
	public function yaml_parse_and_check( string $data, string $type = 'niches', string $schema = 'niche' ) : void {
		if ( get_transient( Config::PLUGIN_PREFIX . 'new_' . $type ) ) {
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

		if ( ! isset( $parsed_data['components']['schemas'][ $schema ]['enum'] ) ) {
			return;
		}

		// Convert into correct format.
		$fetched_data   = $parsed_data['components']['schemas'][ $schema ]['enum'];
		$formatted_data = array();

		foreach ( $fetched_data as $single ) {
			if ( isset( $formatted_data[ $single ] ) ) {
				continue;
			}

			// Value should be equal to key as well.
			$data_value = $single;

			if ( 'niches' === $type ) {
				// Split string by uppercase for display.
				$data_value = preg_replace( '/(?<!\ )[A-Z]/', ' $0', $single );

				// Special replacements for Home & Family, SEO niches
				$data_value = str_replace( 'And', 'and', $data_value );
				$data_value = str_replace( 'Seo', 'SEO', $data_value );
			}

			$formatted_data[ $single ] = sanitize_text_field( $data_value );
		}

		// Check for any new entries by finding difference of two arrays.
		$new_data = array_diff( $formatted_data, Config::${$type} );

		// Store the data as transient for the notification to update.
		// Set to 7 days.
		if ( ! empty( $new_data ) ) {
			set_transient( Config::PLUGIN_PREFIX . 'new_' . $type, $new_data, 60 * 60 * 24 * 7 );
		}
	}

	/**
	 * Shows notification in the admin panel when an update for
	 * niches and/or languages is available.
	 *
	 * @return void
	 */
	public function admin_notices() : void {
		global $current_screen;

		if ( ! $this->is_plugin_page( $current_screen->id ) ) {
			return;
		}

		$niche_transient    = get_transient( Config::PLUGIN_PREFIX . 'new_niches' );
		$language_transient = get_transient( Config::PLUGIN_PREFIX . 'new_languages' );

		if ( $niche_transient ) {
			$message = sprintf(
				esc_html__( 'New niches are available for the plugin. Please go to %1$sSettings page%2$s to update.', 'kafkai' ),
				'<a href="' . self_admin_url( 'admin.php?page=' . Config::PLUGIN_PREFIX . 'settings' ) . '">',
				'</a>'
			);

			$this->add_notification( $message );
		}

		if ( $language_transient ) {
			$message = sprintf(
				esc_html__( 'New languages are available for the plugin. Please go to %1$sSettings page%2$s to update.', 'kafkai' ),
				'<a href="' . self_admin_url( 'admin.php?page=' . Config::PLUGIN_PREFIX . 'settings' ) . '">',
				'</a>'
			);

			$this->add_notification( $message );
		}
	}

	/**
	 * Contains notification message structure.
	 *
	 * @return void
	 */
	public function add_notification( string $message, string $code = 'info' ) : void {

		echo sprintf( '<div class="notice notice-%s"><p>%s</p></div>', esc_html($code), esc_html($message ) );

	}

	/**
	 * Add update button to `Settings` page.
	 *
	 * @return void
	 */
	public function add_update_button() : void {
		// phpcs:disable
		echo '&nbsp;<input type="submit" name="' . esc_attr(Config::PLUGIN_PREFIX) . 'update_data" value="' . __( 'Update Niches & Languages', 'kafkai' ) . '" class="button button-secondary">';
	// phpcs:enable
	}

	/**
	 * Verifies whether the current page belongs to the plugin or not.
	 *
	 * @param string $page ID of the current page
	 * @return boolean
	 */
	protected function is_plugin_page( $page ) : bool {
		return (
			false !== strpos( $page, Config::PLUGIN_PREFIX . 'import' )
			|| false !== strpos( $page, Config::PLUGIN_PREFIX . 'generate' )
		);
	}

}
