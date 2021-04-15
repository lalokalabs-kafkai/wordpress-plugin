<?php
/**
 * Extending the admin workflow.
 */

namespace Niteo\Kafkai\Plugin\Admin;

use Niteo\Kafkai\Plugin\Config;
use Niteo\Kafkai\Plugin\Helper as MainHelper;
use Niteo\Kafkai\Plugin\Admin\Api;

trait Helper {

	/**
	 * @var string
	 */
	private $response;

	/**
	 * @var string
	 */
	private $code = 'error';

	/**
	 * @var string
	 */
	private $_imageurl = 'https://app.kafkai.com/images/niches/';

	/**
	 * Get settings from the DB.
	 * This includes credentials, token, and user details, if available.
	 *
	 * @return array
	 */
	public function get_settings() : array {
		$credentials = array(
			'email'    => '',
			'password' => '',
		);

		$settings = get_option( Config::PLUGIN_PREFIX . 'settings', $credentials );

		// Sanitize
		$settings = array_map( 'sanitize_text_field', $settings );

		return $settings;
	}

	/**
	 * Get user token from DB.
	 *
	 * @return false|string
	 */
	public function get_token() {
		return sanitize_text_field( get_option( Config::PLUGIN_PREFIX . 'token' ) );
	}

	/**
	 * Process form data on submission.
	 *
	 * @return void
	 */
	public function process_settings() : void {
		if ( ! isset( $_POST[ Config::PLUGIN_PREFIX . 'settings' ] ) ) {
			return;
		}

		// Verify nonce
		if ( ! MainHelper::verify_nonce() ) {
			$this->response = esc_html__( 'Request could not be validated.', 'kafkai' );
			return;
		}

		// Request looks good, go ahead with processing credentials
		$user_email    = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'email' ] );
		$user_password = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'password' ] );

		// Empty fields
		if ( empty( $user_email ) || empty( $user_password ) ) {
			$this->response = esc_html__( 'Please provide both email and password for authentication.', 'kafkai' );
			return;
		}

		// Save credentials to DB
		$credentials = array(
			'email'    => $user_email,
			'password' => $user_password,
		);
		update_option( Config::PLUGIN_PREFIX . 'settings', $credentials );

		// API call for authentication
		$api      = new Api();
		$response = $api->authenticate();

		// Request did not go through correctly
		if ( ! $response ) {
			$this->response = $api->error;
			return;
		}

		// Decode the response
		$data = json_decode( $api->response, true );

		// Look for errors
		if ( isset( $data['errors'] ) ) {
			$this->response = $data['errors'][0];
			return;
		}

		// Continue with processing the token
		if ( ! isset( $data['token'] ) ) {
			$this->response = sprintf(
				esc_html__( 'Unable to fetch token from the API. Please try again or %1$scontact support%2$s.', 'kafkai' ),
				'<a href="https://help.kafkai.com/" target="_blank">',
				'</a>'
			);
			return;
		}

		// Sanitize user data received from API
		$user_data = array_map( 'sanitize_text_field', $data );

		// We have token from API
		// Add it to DB and other available details as it is
		update_option( Config::PLUGIN_PREFIX . 'token', $user_data['token'] );
		update_option( Config::PLUGIN_PREFIX . 'api_user', $user_data );

		// Add to response and change code
		$this->code     = 'success';
		$this->response = esc_html__( 'Authentication token has been generated successfully via API.', 'kafkai' );
	}

	/**
	 * Fetch new niches along with icons from the API.
	 *
	 * @return void
	 */
	public function update_niches() : void {
		if ( ! isset( $_POST[ Config::PLUGIN_PREFIX . 'update_niches' ] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! MainHelper::verify_nonce() ) {
			$this->response = esc_html__( 'Request could not be validated.', 'kafkai' );
			return;
		}

		// Check for and merge new niches.
		$niches = get_transient( Config::PLUGIN_PREFIX . 'new_niches' );

		if ( ! $niches ) {
			$this->response = esc_html__( 'No new niches are available for the update.', 'kafkai' );
			return;
		}

		// Counter for new niches.
		$new_niches = 0;

		foreach ( $niches as $key => $value ) {
			if ( isset( Config::$niches[ $key ] ) ) {
				continue;
			}

			Config::$niches[ $key ] = $value;

			// Download icon.
			if ( $this->download_icon( $key ) ) {
				++$new_niches;
			}
		}

		// Confirm if new niches were added.
		if ( ! $new_niches ) {
			// Delete the new_niches transient as no update was required.
			delete_transient( Config::PLUGIN_PREFIX . 'new_niches' );

			$this->code     = 'info';
			$this->response = esc_html__( 'No new niches were added. Either the list is updated or download of niche icons failed.', 'kafkai' );

			return;
		}

		// Sort niches array.
		ksort( Config::$niches, SORT_STRING );

		// Add option for new niches.
		$update = update_option( Config::PLUGIN_PREFIX . 'niches', Config::$niches );

		// Add to response and change code.
		if ( $update ) {
			// Delete the new_niches transient as the update was done.
			delete_transient( Config::PLUGIN_PREFIX . 'new_niches' );

			$this->code     = 'success';
			$this->response = esc_html__( $new_niches . ' new niche(s) have been added successfully.', 'kafkai' );

			return;
		}

		$this->response = esc_html__( 'There was an error updating niches. Please try agin later.', 'kafkai' );
	}

	/**
	 * Download niche icon over HTTP.
	 *
	 * @param string $slug Niche slug to check for the icon.
	 * @return bool
	 */
	public function download_icon( string $slug ) : bool {
		$folder = Config::$plugin_path . 'assets/admin/images/';

		if ( ! is_writable( $folder ) ) {
			return false;
		}

		$slug = preg_replace( '/(?<!\ )[A-Z]/', ' $0', $slug );
		$slug = sanitize_text_field( strtolower( $slug ) );
		$slug = str_replace( ' ', '_', $slug );

		// Fetch niche icon using the simplest method if the server allows it.
		if ( ! ini_get( 'allow_url_fopen' ) ) {
			return false;
		}

		$icon = file_get_contents( $this->_imageurl . $slug . '.svg' );

		if ( ! $icon ) {
			return false;
		}

		$save = file_put_contents( $folder . $slug . '.svg', $icon );

		if ( ! $save ) {
			return false;
		}

		return true;
	}

	/**
	 * Adds notice for admin screen.
	 *
	 * @return void
	 */
	public function add_notice( $code, $response ) : void {
		echo '<div class="notice notice-' . $code . ' is-dismissible"><p>' . $response . '</p></div>';
	}

}
