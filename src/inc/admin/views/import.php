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
	if ( 'error' === $this->articles->code ) {
		$this->add_notice( $this->articles->code, $this->articles->error );
	}

	?>

	<h2 class="screen-reader-text"><?php esc_html_e( 'Filter pages list', 'kafkai-wp' ); ?></h2>

	<form id="articles-filter" method="get">
		<div class="tablenav top">
			<h2 class="screen-reader-text">
				<?php esc_html_e( 'Pages list navigation', 'kafkai-wp' ); ?>
			</h2>

			<?php

			if ( 'success' === $this->articles->code ) :
				if ( ! empty( $this->articles->response['pageNum'] ) ) :

					?>
				<div class="tablenav-pages">
					<?php if ( (int) $this->articles->response['pageCount'] > 1 && (int) $this->articles->response['pageNum'] !== 1 ) : ?>
						<?php if ( (int) $this->articles->response['pageNum'] > 2 ) : ?>
						<a class="prev-page button" href="<?php echo add_query_arg( 'paged', 1 ); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">«</span></a>
					<?php endif; ?>
						<a class="prev-page button" href="<?php echo add_query_arg( 'paged', $this->articles->response['pageNum'] - 1 ); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>&nbsp;
					<?php endif; ?>&nbsp;

					<span class="displaying-num">Page <?php echo $this->articles->response['pageNum']; ?> of <?php echo $this->articles->response['pageCount']; ?></span>

					<?php if ( (int) $this->articles->response['pageCount'] > $this->articles->response['pageNum'] ) : ?>
						<a class="next-page button" href="<?php echo add_query_arg( 'paged', $this->articles->response['pageNum'] + 1 ); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>&nbsp;
						<?php if ( (int) $this->articles->response['pageCount'] > 2 && ( $this->articles->response['pageCount'] - $this->articles->response['pageNum'] ) > 1 ) : ?>
						<a class="next-page button" href="<?php echo add_query_arg( 'paged', $this->articles->response['pageCount'] ); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">»</span></a>
					<?php endif; ?>
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

			<tbody id="article-list">
				<?php

				if ( 'success' === $this->articles->code ) :
					if ( isset( $this->articles->response['articles'] ) ) :
						foreach ( $this->articles->response['articles'] as $key => $data ) :
							$key         = esc_attr( $key );
							$title       = trim( esc_html( $data['title'] ) );
							$niche       = esc_html( $data['niche'] );
							$state       = esc_attr( $data['state'] );
							$niche_image = strtolower( str_replace( ' ', '_', $this->articles->niche_name( $niche ) ) );

							// Date
							$date           = new DateTime( esc_html( $data['date'] ) );
							$formatted_date = $date->format( 'Y/m/d' ) . ' at ' . $date->format( 'h:m a' );

							?>
								<tr id="article-<?php echo $key; ?>" class="niche-<?php echo $niche; ?> state-<?php echo $state; ?>">
									<td class="image column-image">
										<img src="<?php echo Config::$plugin_url . 'assets/admin/images/' . $niche_image . '.svg'; ?>" alt="<?php echo $niche; ?>">
									</td>

									<td class="title column-title column-primary has-row-actions" data-colname="<?php esc_html_e( 'Title', 'kafkai-wp' ); ?>">
								<?php

								if ( strlen( $title ) > 120 ) {
									echo '<a href="javascript:;" class="import-article" data-id="' . $key . '">' . substr( $title, 0, 120 ) . '</a>...';
								} else {
									echo '<a href="javascript:;" class="import-article" data-id="' . $key . '">' . $title . '</a>';
								}

								?>
									</td>

									<td class="niche column-niche" data-colname="<?php esc_html_e( 'Niche', 'kafkai-wp' ); ?>">
									<?php echo $this->articles->niche_name( $niche ); ?>
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

				<?php if ( 'error' === $this->articles->code ) : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="3">
							<?php esc_html_e( 'No articles found.', 'kafkai-wp' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>

			<tfoot>
				<tr>
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
			<h2 class="screen-reader-text">
				<?php esc_html_e( 'Pages list navigation', 'kafkai-wp' ); ?>
			</h2>

			<?php

			if ( 'success' === $this->articles->code ) :
				if ( ! empty( $this->articles->response['pageNum'] ) ) :

					?>
					<div class="tablenav-pages">
					<?php if ( (int) $this->articles->response['pageCount'] > 1 && (int) $this->articles->response['pageNum'] !== 1 ) : ?>
						<?php if ( (int) $this->articles->response['pageNum'] > 2 ) : ?>
							<a class="prev-page button" href="<?php echo add_query_arg( 'paged', 1 ); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">«</span></a>
						<?php endif; ?>
							<a class="prev-page button" href="<?php echo add_query_arg( 'paged', $this->articles->response['pageNum'] - 1 ); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>&nbsp;
						<?php endif; ?>&nbsp;

						<span class="displaying-num">Page <?php echo $this->articles->response['pageNum']; ?> of <?php echo $this->articles->response['pageCount']; ?></span>

						<?php if ( (int) $this->articles->response['pageCount'] > $this->articles->response['pageNum'] ) : ?>
							<a class="next-page button" href="<?php echo add_query_arg( 'paged', $this->articles->response['pageNum'] + 1 ); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>&nbsp;
							<?php if ( (int) $this->articles->response['pageCount'] > 2 && ( $this->articles->response['pageCount'] - $this->articles->response['pageNum'] ) > 1 ) : ?>
							<a class="next-page button" href="<?php echo add_query_arg( 'paged', $this->articles->response['pageCount'] ); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">»</span></a>
						<?php endif; ?>
						<?php endif; ?>
					</div>
						<?php

						endif;
					endif;

			?>
		</div>
	</form>

	<div id="inline-article-container" class="single-article-container">
		<div class="article-actions top">
			<p>
				<label for="article-import-keywords">
					<strong><?php esc_html_e( 'Keywords', 'kafkai-wp' ); ?></strong>
				</label>&nbsp;

				<input name="article-import-keywords" id="article-import-keywords" type="text">
			</p>

			<p>
				<label for="article-import-author">
					<strong><?php esc_html_e( 'Author', 'kafkai-wp' ); ?></strong>
				</label>&nbsp;
	
				<?php

					// Fetch users information for publishing the post as another user
					// Only from Editor, Contributor, Author, and Administrator
					$users = get_users(
						array(
							'role__in' => array( 'editor', 'author', 'contributor', 'administrator' ),
						)
					);

					// Make sure the array is not empty
					if ( ! empty( $users ) ) {
						echo '<select name="article-import-author" id="article-import-author">';

						// Loop over array
						foreach ( $users as $user ) {
							echo sprintf(
								'<option value="%s">%s</option>',
								$user->data->ID,
								$user->data->user_login
							);
						}

						echo '</select>';
					} else {
						echo esc_html__( 'No users found', 'kafkai-wp' );
					}

					?>
			</p>

			<p class="align-right">
				<label for="article-import-media">
					<strong><?php esc_html_e( 'Media', 'kafkai-wp' ); ?></strong>
				</label>&nbsp;

				<input type="checkbox" name="article-import-image" id="import-image" class="switch" checked="checked">
				<label for="import-image"><?php esc_html_e( 'Image', 'kafkai-wp' ); ?></label>&nbsp;

				<input type="checkbox" name="article-import-video" id="import-video" class="switch" checked="checked">
				<label for="import-video"><?php esc_html_e( 'Video', 'kafkai-wp' ); ?></label>
			</p>
		</div>

		<div class="error-response"></div>

		<div class="article-content">
			<div class="article-meta"></div>
			<div class="article-body"></div>
		</div>

		<div class="article-actions bottom">
			<p>
				<label for="article-import-status">
					<strong><?php esc_html_e( 'Status', 'kafkai-wp' ); ?></strong>
				</label>&nbsp;

				<?php

					// Fetch post status options for the user
					$statuses = get_post_statuses();

					// Make sure the array is not empty
				if ( ! empty( $statuses ) ) {
					echo '<select name="article-import-status" id="article-import-status">';

					// Loop over array
					foreach ( $statuses as $status ) {
						echo sprintf(
							'<option value="%s">%s</option>',
							str_replace( ' ', '_', strtolower( $status ) ),
							$status
						);
					}

					echo '</select>';
				} else {
					echo esc_html__( 'Unable to fetch status', 'kafkai-wp' );
				}

				?>
			</p>

			<p class="align-right">
				<input type="submit" name="<?php echo Config::PLUGIN_PREFIX; ?>article_import" value="<?php esc_attr_e( 'Import Article', 'kafkai-wp' ); ?>" class="button button-primary">
			</p>
		</div>
	</div><!-- .single-article-container -->
</div>
