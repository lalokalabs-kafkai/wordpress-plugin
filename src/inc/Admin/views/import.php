<?php
/**
 * Import page for the admin.
 */

use Niteo\Kafkai\Plugin\Config;

?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Import Articles', 'kafkai' ); ?>
	</h1>

	<a href="<?php echo add_query_arg( array( 'action' => 'refresh_list' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Refresh List', 'kafkai' ); ?>
	</a>

	<hr class="wp-header-end">

	<?php

		// Show errors as notification
	if ( 'error' === $this->articles->code ) {
		$this->add_notice( $this->articles->code, $this->articles->error );
	}

	?>

	<h2 class="screen-reader-text"><?php esc_html_e( 'Filter pages list', 'kafkai' ); ?></h2>

	<form id="articles-filter" method="get">
		<div class="tablenav top">
			<h2 class="screen-reader-text">
				<?php esc_html_e( 'Pages list navigation', 'kafkai' ); ?>
			</h2>

			<div class="alignleft color-identifier">
				<p><span class="import-color"></span> Imported Article</p>
			</div>

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
			<?php esc_html_e( 'Pages list', 'kafkai' ); ?>
		</h2>

		<table class="wp-list-table widefat fixed striped table-view-list articles">
			<thead>
				<tr>
					<th class="image column-image"></th>

					<th id="title" class="manage-column column-title column-primary">
						<span><?php esc_html_e( 'Title', 'kafkai' ); ?></span>
					</th>

					<th id="niche" class="manage-column column-niche">
						<span><?php esc_html_e( 'Niche', 'kafkai' ); ?></span>
					</th>

					<th scope="col" id="date" class="manage-column column-date">
						<span><?php esc_html_e( 'Date', 'kafkai' ); ?></span>
					</th>
				</tr>
			</thead>

			<tbody id="article-list">
				<?php

				if ( 'success' === $this->articles->code ) :
					if ( isset( $this->articles->response['articles'] ) && ! empty( $this->articles->response['articles'] ) ) :
						foreach ( $this->articles->response['articles'] as $key => $data ) :
							$key         = esc_attr( $key );
							$title       = trim( esc_html( $data['title'] ) );
							$niche       = esc_html( $data['niche'] );
							$state       = esc_attr( $data['state'] );
							$status      = esc_attr__( 'raw', 'kafkai' );
							$niche_image = strtolower( str_replace( ' ', '_', $this->articles->niche_name( $niche ) ) );

							// Date
							$date           = new DateTime( esc_html( $data['date'] ) );
							$formatted_date = $date->format( 'Y/m/d' ) . ' at ' . $date->format( 'h:m a' );

							// Check for import status
							if ( in_array( $key, $this->articles->imported_article_ids ) ) {
								$status = esc_attr__( 'imported', 'kafkai' );
							}

							?>
								<tr id="article-<?php echo $key; ?>" class="niche-<?php echo $niche; ?> state-<?php echo $state; ?> status-<?php echo $status; ?>">
									<td class="image column-image">
										<img src="<?php echo Config::$plugin_url . 'assets/admin/images/' . $niche_image . '.svg'; ?>" alt="<?php echo $niche; ?>" onerror="javascript:this.style.display='none'">
									</td>

									<td class="title column-title column-primary has-row-actions" data-colname="<?php esc_html_e( 'Title', 'kafkai' ); ?>">
								<?php

								if ( strlen( $title ) > 120 ) {
									echo '<a href="javascript:;" class="fetch-article" data-id="' . $key . '">' . substr( $title, 0, 120 ) . '</a>...';
								} else {
									echo '<a href="javascript:;" class="fetch-article" data-id="' . $key . '">' . $title . '</a>';
								}

								?>
									</td>

									<td class="niche column-niche" data-colname="<?php esc_html_e( 'Niche', 'kafkai' ); ?>">
									<?php echo $this->articles->niche_name( $niche ); ?>
									</td>

									<td class="date column-date" data-colname="<?php esc_html_e( 'Date', 'kafkai' ); ?>">
									<?php echo $formatted_date; ?>
									</td>
								</tr>
							<?php

							endforeach;
						else :
							?>
								<tr>
									<td colspan="4">
										<?php

											echo sprintf(
												esc_html__( 'There are no articles under your account. You can %1$sclick here%2$s to generate one.', 'kafkai' ),
												'<a href="' . self_admin_url( 'admin.php?page=' . Config::PLUGIN_PREFIX . 'generate' ) . '">',
												'</a>'
											);

										?>
										</td>
								</tr>
							<?php
						endif;
					endif;

				?>

				<?php if ( 'error' === $this->articles->code ) : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="3">
							<?php esc_html_e( 'No articles found.', 'kafkai' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>

			<tfoot>
				<tr>
					<th class="image column-image"></th>

					<th id="title" class="manage-column column-title column-primary">
						<span><?php esc_html_e( 'Title', 'kafkai' ); ?></span>
					</th>

					<th id="niche" class="manage-column column-niche">
						<span><?php esc_html_e( 'Niche', 'kafkai' ); ?></span>
					</th>

					<th scope="col" id="date" class="manage-column column-date">
						<span><?php esc_html_e( 'Date', 'kafkai' ); ?></span>
					</th>
				</tr>
			</tfoot>
		</table>

		<div class="tablenav bottom">
			<h2 class="screen-reader-text">
				<?php esc_html_e( 'Pages list navigation', 'kafkai' ); ?>
			</h2>

			<div class="alignleft color-identifier">
				<p><span class="import-color"></span> Imported Article</p>
			</div>

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

	<div id="<?php echo Config::PLUGIN_PREFIX; ?>inline-article-container" class="single-article-container">
		<form method="post" id="<?php echo Config::PLUGIN_PREFIX; ?>import_form">
			<div class="single-article-scrollable">
				<div class="article-actions top">
					<button type="button" class="modal-close">
						<span class="modal-icon">
							<span class="screen-reader-text"><?php esc_html_e( 'Close dialogue', 'kafkai' ); ?></span>
						</span>
					</button>
				</div>

				<div class="article-content">
					<div class="article-meta">
						<div><span class="article-meta-chars">N/A</span> <?php esc_html_e( 'Chars', 'kafkai' ); ?></div>
						<div><span class="article-meta-words">N/A</span> <?php esc_html_e( 'Words', 'kafkai' ); ?></div>
					</div>

					<h1 class="article-title"></h1>
					<div class="article-body"></div>
				</div>
			</div><!-- .single-article-scrollable -->

			<div class="article-actions bottom">
				<p>
					<label for="<?php echo Config::PLUGIN_PREFIX; ?>article-import-status">
						<strong><?php esc_html_e( 'Status', 'kafkai' ); ?></strong>
					</label>&nbsp;

					<?php

						// Fetch post status options for the user
						$statuses = get_post_statuses();

						// Make sure the array is not empty
					if ( ! empty( $statuses ) ) {
						echo '<select name="' . Config::PLUGIN_PREFIX . 'article-import-status" id="' . Config::PLUGIN_PREFIX . 'article-import-status">';

						// Loop over array
						foreach ( $statuses as $status_key => $status_name ) {
							echo sprintf(
								'<option value="%s">%s</option>',
								$status_key,
								$status_name
							);
						}

						echo '</select>';
					} else {
						echo esc_html__( 'Unable to fetch status', 'kafkai' );
					}

					?>
				</p>

				<p>
					<label for="<?php echo Config::PLUGIN_PREFIX; ?>article-import-author">
						<strong><?php esc_html_e( 'Author', 'kafkai' ); ?></strong>
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
							echo '<select name="' . Config::PLUGIN_PREFIX . 'article-import-author" id="' . Config::PLUGIN_PREFIX . 'article-import-author">';

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
							echo esc_html__( 'No users found', 'kafkai' );
						}

						?>
				</p>

				<p class="align-right">
					<input type="hidden" name="<?php echo Config::PLUGIN_PREFIX; ?>article_id" id="<?php echo Config::PLUGIN_PREFIX; ?>article_id">
					<input type="submit" name="<?php echo Config::PLUGIN_PREFIX; ?>article_import" id="<?php echo Config::PLUGIN_PREFIX; ?>article_import" value="<?php esc_attr_e( 'Import Article', 'kafkai' ); ?>" class="button button-primary">
				</p>
			</div>
	</form><!-- form -->
	</div><!-- .single-article-container -->
</div>
