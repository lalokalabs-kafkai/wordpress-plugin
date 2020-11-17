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
	 * Adds notice for admin screen.
	 *
	 * @return void
	 */
	public function add_notice( $code, $response ) : void {
		?>
	<div class="notice notice-<?php echo $code; ?> is-dismissible">
	  <p><?php echo $response; ?></p>
	</div>
		<?php
	}

}
