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
		'user-agent'  => 'Kafkai WP Plugin v@##VERSION##@',
		'headers'     => array(
			'Content-type' => 'application/json',
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
	 * @return array
	 */
	public function call( string $endpoint, string $method, array $body = array(), bool $skip_token = false ) {
		// Authorization token for making API request
		if ( ! $skip_token ) {
			if ( ! $this->verify_token() ) {
				return false;
			}
		}

		// Move ahead with the request
		if ( 'GET' === $method ) {
			$request = wp_remote_get(
				$this->_apiurl . $endpoint,
				$this->args
			);
		}

		// For a post request, include body if it's provided
		if ( 'POST' === $method ) {
			if ( ! empty( $body ) ) {
				$this->args['body'] = json_encode( $body );
			}

			$request = wp_remote_post(
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
