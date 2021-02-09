<?php
/**
 * Generate page for the admin.
 */

use Niteo\Kafkai\Plugin\Config;

?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Generate Article', 'kafkai' ); ?>
	</h1>

	<hr class="wp-header-end">

	<?php

		// Show errors as notification
	if ( ! empty( $this->articles->error ) ) {
		$this->add_notice( $this->articles->code, $this->articles->error );
	}

	?>

	<form method="post">
		<input type="hidden" name="_<?php echo Config::PLUGIN_PREFIX; ?>nonce" id="_<?php echo Config::PLUGIN_PREFIX; ?>nonce" value="<?php echo esc_attr( wp_create_nonce( Config::PLUGIN_SLUG . '-nonce' ) ); ?>">

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="<?php echo Config::PLUGIN_PREFIX; ?>niche">
							<?php esc_html_e( 'Niche', 'kafkai' ); ?>
						</label>
					</th>
					<td>
						<select name="<?php echo Config::PLUGIN_PREFIX; ?>niche" id="<?php echo Config::PLUGIN_PREFIX; ?>niche" class="regular-text">
							<?php

								// Article niches
							foreach ( $this->articles->niches as $key => $niche ) {
								echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $niche ) . '</option>\n';
							}

							?>
						</select>
					</td>
				</tr>
			</tbody>
		</table><br>

		<h2 class="title">
			<?php esc_html_e( 'Advanced', 'kafkai' ); ?>
		</h2>

		<hr>

		<p>
			<?php esc_html_e( 'Give me a paragraph to start with (upto 250 characters). It could be taken from any page on the Internet and it won’t be used as-is in the new article.', 'kafkai' ); ?>
		</p>

		<table class="form-table">
			<tbody>
				<tr>
					<td class="no-margin-left">
						<textarea name="<?php echo Config::PLUGIN_PREFIX; ?>seed" id="<?php echo Config::PLUGIN_PREFIX; ?>seed" class="large-text" rows="4" placeholder="<?php esc_attr_e( 'Optional', 'kafkai' ); ?>"></textarea>
						<p class="help-text"><?php esc_html_e( 'Please note that advanced articles take longer to finish.', 'kafkai' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="<?php echo Config::PLUGIN_PREFIX; ?>generate" value="<?php esc_attr_e( 'Generate Article', 'kafkai' ); ?>" class="button button-primary">
		</p>
	</form>
</div>
