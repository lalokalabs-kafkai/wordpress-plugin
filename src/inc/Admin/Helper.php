<?php
/**
 * Extending the admin workflow.
 */

namespace Niteo\Kafkai\Plugin\Admin;

use Niteo\Kafkai\Plugin\Config;
use Niteo\Kafkai\Plugin\Helper as MainHelper;
use Niteo\Kafkai\Plugin\Admin\API;

trait Helper {

	/**
	 * @var string
	 */
	private $notices = array();

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
			$this->notices[] = array(
				esc_html__( 'Request could not be validated.', 'kafkai' ),
				'error',
			);
			return;
		}

		// Request looks good, go ahead with processing credentials
		$user_email    = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'email' ] );
		$user_password = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'password' ] );

		// Empty fields
		if ( empty( $user_email ) || empty( $user_password ) ) {
			$this->notices[] = array(
				esc_html__( 'Please provide both email and password for authentication.', 'kafkai' ),
				'error',
			);
			return;
		}

		// Save credentials to DB
		$credentials = array(
			'email'    => $user_email,
			'password' => $user_password,
		);
		update_option( Config::PLUGIN_PREFIX . 'settings', $credentials );

		// API call for authentication
		$api      = new API();
		$response = $api->authenticate();

		// Request did not go through correctly
		if ( ! $response ) {
			$this->notices[] = array(
				$api->error,
				'error',
			);
			return;
		}

		// Decode the response
		$data = json_decode( $api->response, true );

		// Look for errors
		if ( isset( $data['errors'] ) ) {
			$this->notices[] = array(
				$data['errors'][0],
				'error',
			);
			return;
		}

		// Continue with processing the token
		if ( ! isset( $data['token'] ) ) {
			$this->notices[] = array(
				sprintf(
					esc_html__( 'Unable to fetch token from the API. Please try again or %1$scontact support%2$s.', 'kafkai' ),
					'<a href="https://help.kafkai.com/" target="_blank">',
					'</a>'
				),
				'error',
			);
			return;
		}

		// Sanitize user data received from API
		$user_data = array_map( 'sanitize_text_field', $data );

		// We have token from API
		// Add it to DB and other available details as it is
		update_option( Config::PLUGIN_PREFIX . 'token', $user_data['token'] );
		update_option( Config::PLUGIN_PREFIX . 'api_user', $user_data );

		// Add to notices.
		$this->notices[] = array(
			esc_html__( 'Authentication token has been generated successfully via API.', 'kafkai' ),
			'success',
		);
	}

	/**
	 * Fetch new data from the API.
	 * Used for getting updates for niches and languages.
	 *
	 * @return void
	 */
	public function update_data( string $type = 'niches' ) : void {
		if ( ! isset( $_POST[ Config::PLUGIN_PREFIX . 'update_data' ] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! MainHelper::verify_nonce() ) {
			$this->notices[] = array(
				esc_html__( 'Request could not be validated.', 'kafkai' ),
				'error',
			);
			return;
		}

		// Check for and merge new data.
		$data = get_transient( Config::PLUGIN_PREFIX . 'new_' . $type );

		if ( ! $data ) {
			$this->notices[] = array(
				sprintf(
					esc_html__( 'No new %1$s are available for the update.', 'kafkai' ),
					$type
				),
				'error',
			);
			return;
		}

		// Counter for new data.
		$new_data = 0;

		foreach ( $data as $key => $value ) {
			if ( isset( Config::${$type}[ $key ] ) ) {
				continue;
			}

			Config::${$type}[ $key ] = $value;

			if ( 'niches' === $type ) {
				// Download icon.
				if ( $this->download_icon( $key ) ) {
					++$new_data;
				}

				continue;
			}

			++$new_data;
		}

		// Confirm if new data was added.
		if ( ! $new_data ) {
			// Delete the transient as no update was required.
			delete_transient( Config::PLUGIN_PREFIX . 'new_' . $type );

			$this->notices[] = array(
				sprintf(
					esc_html__( 'No new %1$s were added. Either the list is updated or API request failed.', 'kafkai' ),
					$type
				),
				'info',
			);

			return;
		}

		// Sort data array.
		ksort( Config::${$type}, SORT_STRING );

		// Add option for new data.
		$update = update_option( Config::PLUGIN_PREFIX . $type, Config::${$type} );

		// Add to response and change code.
		if ( $update ) {
			// Delete the transient as the update was done.
			delete_transient( Config::PLUGIN_PREFIX . 'new_' . $type );

			$this->notices[] = array(
				sprintf(
					esc_html__( '%1$s new %2$s have been added successfully.', 'kafkai' ),
					$new_data,
					$type
				),
				'success',
			);

			return;
		}

		$this->notices[] = array(
			sprintf(
				esc_html__( 'There was an error updating %1$s. Please try agin later.', 'kafkai' ),
				$type
			),
			'error',
		);
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
		echo esc_html('<div class="notice notice-' . $code . ' is-dismissible"><p>' . $response . '</p></div>');
	}

}
