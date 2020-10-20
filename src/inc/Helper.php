<?php
/**
 * Helper class for the plugin.
 */

namespace Niteo\Kafkai\Plugin;

/**
 * Class with helper functions.
 *
 * @package Niteo\Kafkai\Plugin
 */
class Helper {

	/**
	 * Nonce verification for the request.
	 *
	 * @return bool
	 */
	public static function verify_nonce( $name = 'nonce' ) : bool {
		// Nonce verification
		$nonce = sanitize_text_field( $_POST[ '_' . Config::PLUGIN_PREFIX . $name ] );

		if ( wp_verify_nonce( $nonce, Config::PLUGIN_SLUG . '-' . $name ) ) {
			return true;
		}

		return false;
	}

}
