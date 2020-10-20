<?php
/**
 * Import page for the admin.
 */

use Niteo\Kafkai\Plugin\Config;

?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Import Articles', 'kafkai-wp' ); ?>
	</h1>

	<a href="<?php echo self_admin_url( 'admin.php?page=kafkaiwp_generate' ); ?>" class="page-title-action">
		<?php esc_html_e( 'Generate New', 'kafkai-wp' ); ?>
	</a>

	<hr class="wp-header-end">

	<?php

		// Show errors as notification
	if ( 'error' === $articles->code ) {
		$this->add_notice( $articles->code, $articles->error );
	}

	?>

	<h2 class="screen-reader-text"><?php esc_html_e( 'Filter pages list', 'kafkai-wp' ); ?></h2>

	<form id="articles-filter" method="get">
		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-top" class="screen-reader-text">
					<?php esc_html_e( 'Select bulk action', 'kafkai-wp' ); ?>
				</label>

				<select name="action" id="bulk-action-selector-top">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'kafkai-wp' ); ?></option>
					<option value="import"><?php esc_html_e( 'Import', 'kafkai-wp' ); ?></option>
				</select>

				<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'kafkai-wp' ); ?>">
			</div>

			<h2 class="screen-reader-text">
				<?php esc_html_e( 'Pages list navigation', 'kafkai-wp' ); ?>
			</h2>

			<?php

			if ( 'success' === $articles->code ) :
				if ( $articles->response['pageNum'] > 0 ) :

					?>
				<div class="tablenav-pages">
					<?php if ( (int) $articles->response['pageCount'] > 1 && (int) $articles->response['pageNum'] !== 1 ) : ?>
						<a class="prev-page button" href="<?php echo add_query_arg( 'paged', $articles->response['pageNum'] - 1 ); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>
					<?php endif; ?>&nbsp;

					<span class="displaying-num">Page <?php echo $articles->response['pageNum']; ?> of <?php echo $articles->response['pageCount']; ?></span>

					<?php if ( (int) $articles->response['pageCount'] > $articles->response['pageNum'] ) : ?>
						<a class="next-page button" href="<?php echo add_query_arg( 'paged', $articles->response['pageNum'] + 1 ); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
					<?php endif; ?>
				</div>
					<?php

					endif;
				endif;

			?>
		</div>

		<h2 class="screen-reader-text">
			<?php esc_html_e( 'Pages list', 'kafkai-wp' ); ?>
		</h2>

		<table class="wp-list-table widefat fixed striped table-view-list articles">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="<?php echo Config::PLUGIN_PREFIX; ?>select-all">
							<?php esc_html_e( 'Select all', 'kafkai-wp' ); ?>
						</label>

						<input id="<?php echo Config::PLUGIN_PREFIX; ?>select-all" type="checkbox">
					</td>

					<th class="image column-image"></th>

					<th id="title" class="manage-column column-title column-primary">
						<span><?php esc_html_e( 'Title', 'kafkai-wp' ); ?></span>
					</th>

					<th id="niche" class="manage-column column-niche">
						<span><?php esc_html_e( 'Niche', 'kafkai-wp' ); ?></span>
					</th>

					<th scope="col" id="date" class="manage-column column-date">
						<span><?php esc_html_e( 'Date', 'kafkai-wp' ); ?></span>
					</th>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php

				if ( 'success' === $articles->code ) :
					if ( isset( $articles->response['articles'] ) ) :
						foreach ( $articles->response['articles'] as $key => $data ) :
							$key         = esc_attr( $key );
							$title       = trim( esc_html( $data['title'] ) );
							$niche       = esc_html( $data['niche'] );
							$state       = esc_attr( $data['state'] );
							$niche_image = strtolower( str_replace( ' ', '_', $articles->niche_name( $niche ) ) );

							// Date
							$date           = new DateTime( esc_html( $data['date'] ) );
							$formatted_date = $date->format( 'Y/m/d' ) . ' at ' . $date->format( 'h:m a' );

							?>
								<tr id="article-<?php echo $key; ?> niche-<?php echo $niche; ?> state-<?php echo $state; ?>">
									<th scope="row" class="check-column">
										<label class="screen-reader-text" for="cb-select-<?php echo $key; ?>">
									<?php esc_html_e( 'Select', 'kafkai-wp' ); ?>
										</label>

										<input id="cb-select-<?php echo $key; ?>" type="checkbox" name="post[]" value="<?php echo $key; ?>">
									</th>

									<td class="image column-image">
										<img src="<?php echo Config::$plugin_url . 'assets/admin/images/' . $niche_image . '.svg'; ?>" alt="<?php echo $niche; ?>">
									</td>

									<td class="title column-title column-primary has-row-actions" data-colname="<?php esc_html_e( 'Title', 'kafkai-wp' ); ?>">
								<?php

								if ( strlen( $title ) > 120 ) {
									echo substr( $title, 0, 120 ) . '...';
								} else {
									echo $title;
								}

								?>
									</td>

									<td class="niche column-niche" data-colname="<?php esc_html_e( 'Niche', 'kafkai-wp' ); ?>">
									<?php echo $articles->niche_name( $niche ); ?>
									</td>

									<td class="date column-date" data-colname="<?php esc_html_e( 'Date', 'kafkai-wp' ); ?>">
									<?php echo $formatted_date; ?>
									</td>
								</tr>
							<?php

							endforeach;
						endif;
					endif;

				?>

				<?php if ( 'error' === $articles->code ) : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="3">
							<?php esc_html_e( 'No articles found.', 'kafkai-wp' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>

			<tfoot>
				<tr>
					<td class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="<?php echo Config::PLUGIN_PREFIX; ?>select-all">
							<?php esc_html_e( 'Select all', 'kafkai-wp' ); ?>
						</label>

						<input id="<?php echo Config::PLUGIN_PREFIX; ?>select-all" type="checkbox">
					</td>

					<th class="image column-image"></th>

					<th id="title" class="manage-column column-title column-primary">
						<span><?php esc_html_e( 'Title', 'kafkai-wp' ); ?></span>
					</th>

					<th id="niche" class="manage-column column-niche">
						<span><?php esc_html_e( 'Niche', 'kafkai-wp' ); ?></span>
					</th>

					<th scope="col" id="date" class="manage-column column-date">
						<span><?php esc_html_e( 'Date', 'kafkai-wp' ); ?></span>
					</th>
				</tr>
			</tfoot>
		</table>

		<div class="tablenav bottom">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-top" class="screen-reader-text">
					<?php esc_html_e( 'Select bulk action', 'kafkai-wp' ); ?>
				</label>

				<select name="action" id="bulk-action-selector-top">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'kafkai-wp' ); ?></option>
					<option value="import"><?php esc_html_e( 'Import', 'kafkai-wp' ); ?></option>
				</select>

				<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'kafkai-wp' ); ?>">
			</div>

			<h2 class="screen-reader-text">
				<?php esc_html_e( 'Pages list navigation', 'kafkai-wp' ); ?>
			</h2>

			<?php

			if ( 'success' === $articles->code ) :
				if ( $articles->response['pageNum'] > 0 ) :

					?>
				<div class="tablenav-pages">
					<?php if ( (int) $articles->response['pageCount'] > 1 && (int) $articles->response['pageNum'] !== 1 ) : ?>
						<a class="prev-page button" href="<?php echo add_query_arg( 'paged', $articles->response['pageNum'] - 1 ); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>
					<?php endif; ?>&nbsp;

					<span class="displaying-num">Page <?php echo $articles->response['pageNum']; ?> of <?php echo $articles->response['pageCount']; ?></span>

					<?php if ( (int) $articles->response['pageCount'] > $articles->response['pageNum'] ) : ?>
						<a class="next-page button" href="<?php echo add_query_arg( 'paged', $articles->response['pageNum'] + 1 ); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
					<?php endif; ?>
				</div>
					<?php

					endif;
				endif;

			?>
		</div>
	</form>
</div>
