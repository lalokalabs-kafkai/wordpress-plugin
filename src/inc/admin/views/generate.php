<?php
/**
 * Generate page for the admin.
 */

?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Generate Article', 'kafkai-wp' ); ?>
	</h1>

	<a href="<?php echo self_admin_url( 'admin.php?page=kafkaiwp_admin' ); ?>" class="page-title-action">
		<?php esc_html_e( 'Import Articles', 'kafkai-wp' ); ?>
	</a>

	<hr class="wp-header-end">
</div>
