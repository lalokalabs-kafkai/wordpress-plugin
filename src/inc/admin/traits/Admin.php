<?php
/**
 * Extending the admin workflow.
 */

namespace Niteo\Kafkai\Plugin\Extend;

use Niteo\Kafkai\Plugin\Config;

trait Admin {

	/**
	 * @var string
	 */
	private $response;

	/**
	 * @var string
	 */
	private $code = 'error';

	/**
	 * Get plugin option from the database.
	 *
	 * @return void
	 */
	public function get_options() : void {

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
		if ( ! $this->verify_nonce() ) {
			$this->response = esc_html__( 'Request could not be validated.', 'kafkai-wp' );
			return;
		}

		// Request looks good, go ahead with processing credentials
		$user_email    = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'email' ] );
		$user_password = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'password' ] );

		if ( empty( $user_email ) || empty( $user_password ) ) {
			$this->response = esc_html__( 'Please provide both email and password for authentication.', 'kafkai-wp' );
		}
	}

	/**
	 * Nonce verification for the request.
	 *
	 * @return bool
	 */
	private function verify_nonce() : bool {
		// Nonce verification
		$nonce = sanitize_text_field( $_POST[ '_' . Config::PLUGIN_PREFIX . 'nonce' ] );

		if ( wp_verify_nonce( $nonce, Config::PLUGIN_SLUG . '-nonce' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Adds notice for admin screen.
	 *
	 * @return void
	 */
	public function add_notice() {
		?>
	<div class="notice notice-<?php echo $this->code; ?> is-dismissible">
	  <p><?php echo $this->response; ?></p>
	</div>
		<?php
	}

}
