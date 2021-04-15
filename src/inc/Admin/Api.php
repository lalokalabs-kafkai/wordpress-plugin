<?php
/**
 * API wrapper for the plugin.
 */

namespace Niteo\Kafkai\Plugin\Admin;

use Niteo\Kafkai\Plugin\Config;

/**
 * Base class for making API calls to Kafkai.
 *
 * @package Niteo\Kafkai\Plugin
 */
class Api {

	/**
	 * @var array
	 */
	public $response = array();

	/**
	 * @var string
	 */
	public $error;

	/**
	 * @var string
	 */
	private $_apiurl = 'https://app.kafkai.com/api/v1';

	/**
	 * @var array
	 */
	private $args = array(
		'timeout'     => 60,
		'redirection' => 5,
		'blocking'    => true,
		'headers'     => array(
			'Content-type'      => 'application/json',
			'X-Mixpanel-Client' => 'Kafkai WP Plugin v' . Config::PLUGIN_VERSION,
		),
	);

	/**
	 * Authentication to get token from the API.
	 *
	 * @return bool
	 */
	public function authenticate() {
		$credentials = $this->get_credentials();

		if ( ! $credentials ) {
			return false;
		}

		// We have the DB entry, check for email and password
		if ( ! is_array( $credentials ) ) {
			return false;
		}

		if ( ! isset( $credentials['email'] ) || ! isset( $credentials['password'] ) ) {
			return false;
		}

		// Make authentication call and return the result
		return $this->call(
			'/user/login',
			'POST',
			$credentials,
			true
		);
	}

	/**
	 * Make API requests.
	 *
	 * @return boolean
	 */
	public function call( string $endpoint, string $method, array $body = array(), bool $skip_token = false ) {
		// Authorization token for making API request
		if ( ! $skip_token ) {
			if ( ! $this->verify_token() ) {
				return false;
			}
		}

		// Move ahead with the request
		if ( in_array( $method, array( 'GET', 'POST', 'PATCH' ) ) ) {
			$this->args['method'] = $method;

			if ( 'GET' !== $method ) {
				if ( ! empty( $body ) ) {
					$this->args['body'] = json_encode( $body );
				}
			}

			$request = wp_remote_request(
				$this->_apiurl . $endpoint,
				$this->args
			);
		}

		// Check for response or errors
		if ( is_wp_error( $request ) ) {
			$this->error = $request->get_error_message();
			return false;
		}

		// Add to response
		$this->response = $request['body'];
		return true;
	}

	/**
	 * Wrapper to modify API url.
	 *
	 * @param string $url API url to be set
	 * @return void
	 */
	public function set_apiurl( string $url ) : void {
		$this->_apiurl = $url;
	}

	/**
	 * Wrapper to modify API call args.
	 *
	 * @param array $args Arguments for the API call
	 * @return void
	 */
	public function set_args( array $args ) : void {
		$this->args = $args;
	}

	/**
	 * Wrapper to modify API call headers.
	 *
	 * @param array $headers Headers for the API call
	 * @return void
	 */
	public function set_headers( array $headers ) : void {
		$this->args['headers'] = $headers;
	}

	/**
	 * Check for authentication token in the database.
	 *
	 * @return string|bool
	 */
	private function get_token() {
		return get_option( Config::PLUGIN_PREFIX . 'token' );
	}

	/**
	 * Get credentials from the database.
	 *
	 * @return array|bool
	 */
	private function get_credentials() {
		return get_option( Config::PLUGIN_PREFIX . 'settings' );
	}

	/**
	 * Verification of Kafkai API token.
	 *
	 * @return bool
	 */
	private function verify_token() : bool {
		$token = $this->get_token();

		if ( ! $token ) {
			$this->error = sprintf(
				esc_html__( 'Authorization token is not available. Please go to %1$splugin settings%2$s and provide credentials or re-generate token.', 'kafkai' ),
				'<a href="' . admin_url( 'admin.php?page=' . Config::PLUGIN_PREFIX . 'settings' ) . '">',
				'</a>'
			);

			return false;
		}

		// Add token
		$this->args['headers']['Authorization'] = 'Bearer ' . $token;

		return true;
	}

}
