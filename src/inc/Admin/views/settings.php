<?php
/**
 * Settings page for the admin.
 */

use Niteo\Kafkai\Plugin\Config;

?>

<div class="wrap">
	<h1>
		<?php esc_html_e( 'Settings', 'kafkai' ); ?>
	</h1>

  <?php

	// Show notification if $response is set
	if ( ! empty( $this->response ) ) {
		$this->add_notice( $this->code, $this->response );
	}

	?>

	<form method="post">
	<input type="hidden" name="_<?php echo Config::PLUGIN_PREFIX; ?>nonce" id="_<?php echo Config::PLUGIN_PREFIX; ?>nonce" value="<?php echo esc_attr( wp_create_nonce( Config::PLUGIN_SLUG . '-nonce' ) ); ?>">

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="<?php echo Config::PLUGIN_PREFIX; ?>email"><?php esc_html_e( 'Email', 'kafkai' ); ?></label>
					</th>
					<td>
			<input type="text" name="<?php echo Config::PLUGIN_PREFIX; ?>email" id="<?php echo Config::PLUGIN_PREFIX; ?>email" class="regular-text" value="<?php echo $settings['email']; ?>">
			<p class="description"><?php esc_html_e( 'Email address you use for login on Kafkai.', 'kafkai' ); ?></p>
					</td>
			  </tr>

		<tr>
					<th scope="row">
						<label for="<?php echo Config::PLUGIN_PREFIX; ?>password"><?php esc_html_e( 'Password', 'kafkai' ); ?></label>
					</th>
					<td>
			<input type="password" name="<?php echo Config::PLUGIN_PREFIX; ?>password" id="<?php echo Config::PLUGIN_PREFIX; ?>password" class="regular-text" value="<?php echo $settings['password']; ?>">
			<p class="description"><?php esc_html_e( 'Account password for authentication.', 'kafkai' ); ?></p>
					</td>
			  </tr>

		<tr>
					<th scope="row">
						<label for="<?php echo Config::PLUGIN_PREFIX; ?>token"><?php esc_html_e( 'Token', 'kafkai' ); ?></label>
					</th>
					<td>
			<input type="text" name="<?php echo Config::PLUGIN_PREFIX; ?>token" id="<?php echo Config::PLUGIN_PREFIX; ?>token" class="regular-text code" value="<?php echo $token; ?>" disabled>
			<p class="description"><?php esc_html_e( 'Bearer token for authenticating Kafkai API calls.', 'kafkai' ); ?></p>
			<p class="description"><?php esc_html_e( 'This is generated once valid credentials are processed.', 'kafkai' ); ?></p>
					</td>
			  </tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="<?php echo Config::PLUGIN_PREFIX; ?>settings" value="<?php esc_attr_e( 'Save Settings', 'kafkai' ); ?>" class="button button-primary">
		</p>
	</form>
</div>
