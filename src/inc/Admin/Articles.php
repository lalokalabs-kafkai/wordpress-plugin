<?php
/**
 * Articles management for the plugin.
 */

namespace Niteo\Kafkai\Plugin\Admin;

use Niteo\Kafkai\Plugin\Config;
use Niteo\Kafkai\Plugin\Helper;

/**
 * Class for managing user articles.
 *
 * @package Niteo\Kafkai\Plugin
 */
class Articles {

	/**
	 * @var string
	 */
	public $code = 'error';

	/**
	 * @var string
	 */
	public $error;

	/**
	 * @var array
	 */
	public $response = array();

	/**
	 * @var array
	 */
	public $imported_article_ids = array();

	/**
	 * @var string
	 */
	private $_state = 'All';

	/**
	 * @var int
	 */
	private $_pagesize = 20;

	/**
	 * @var int
	 */
	private $_page = 1;

	/**
	 * @var array
	 */
	private $_articles = array();

	/**
	 * @var array
	 */
	public $niches = array();

	/**
	 * On initialization, add default response and check for current page.
	 */
	public function __construct() {
		add_action( 'wp_ajax_' . Config::PLUGIN_PREFIX . 'fetch_article', array( $this, 'fetch_single_article' ) );
		add_action( 'wp_ajax_' . Config::PLUGIN_PREFIX . 'import_article', array( $this, 'import_single_article' ) );
		add_action( 'before_delete_post', array( $this, 'delete_single_article' ), 10, 2 );
	}

	/**
	 * Fetching articles for the user.
	 *
	 * @return void
	 */
	public function fetch_articles() : void {
		// Article state
		$state = $this->_state;

		if ( 'All' === $this->_state ) {
			$state = 'Generated,Read,Used';
		}

		// Check for transient
		$transient = get_transient( Config::PLUGIN_PREFIX . 'article_' . $this->_state . '_page' . $this->_page );

		if ( $transient ) {
			$this->code     = 'success';
			$this->response = $transient;

			return;
		}

		// Make connection to API
		$api      = new Api();
		$response = $api->call(
			sprintf(
				'/articles?states=%s&page=%d&pageSize=%d',
				urlencode( $state ),
				$this->_page,
				$this->_pagesize
			),
			'GET'
		);

		// If there was a valid response
		if ( $response ) {
			$data = json_decode( $api->response, true );

			// Check if an error is thrown by the API
			if ( isset( $data['errors'] ) ) {
				$this->error = $data['errors'][0];
				return;
			}

			// Create data with ID, state, niche, and title
			foreach ( $data['articles'] as $article ) {
				$this->_articles['articles'][ $article['id'] ] = array(
					'state' => sanitize_text_field( $article['state'] ),
					'niche' => sanitize_text_field( $article['niche'] ),
					'title' => sanitize_text_field( $article['title'] ),
					'date'  => sanitize_text_field( $article['createdAt'] ),
				);
			}

			$this->_articles['pageCount'] = absint( $data['pageCount'] );
			$this->_articles['total']     = absint( $data['total'] );
			$this->_articles['pageNum']   = $this->_page;

			// Set transient
			$this->_set_articles_transient();

			$this->code     = 'success';
			$this->response = $this->_articles;

			return;
		}

		// Capture the error thrown by the API call
		$this->error = $api->error;
	}

	/**
	 * Check postmeta for the attached article ID and updates the list.
	 *
	 * @param int     $post_id Post ID to check for the attached metadata
	 * @param WP_Post $post Post object
	 *
	 * @return void
	 */
	public function delete_single_article( int $post_id, \WP_Post $post ) : void {
		$article_id = get_post_meta( $post_id, 'kafkai_article_id', true );

		if ( ! $article_id ) {
			return;
		}

		// Remove it from the imported article list
		$this->_remove_from_imported_list( $article_id );
	}

	/**
	 * Refresh articles list by clearing the cache.
	 *
	 * @return void
	 */
	public function refresh_list() : void {
		global $wpdb;

		if ( ! isset( $_GET['action'] ) ) {
			return;
		}

		if ( 'refresh_list' !== $_GET['action'] ) {
			return;
		}

		// Clear transients for articles list.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}options WHERE `option_name` LIKE %s OR `option_name` LIKE %s",
				array( '%_transient_kafkaiwp_article_%', '%_transient_timeout_kafkaiwp_article_%' )
			)
		);

		/**
		 * Clear transients using the function if cache is enabled.
		 *
		 * @todo Add option to clear bulk transients when peristent cache is enabled.
		 */
		if ( (bool) wp_using_ext_object_cache() ) {
			for ( $i = 1; $i < 11; $i++ ) {
				wp_cache_delete( Config::PLUGIN_PREFIX . 'article_All_page' . $i, 'transient' );
			}
		}

		// Redirect to page without `refresh_list` action
		wp_safe_redirect( self_admin_url( 'admin.php?page=' . Config::PLUGIN_PREFIX . 'import' ) );
		$this->_terminate();
	}

	/**
	 * Check for current page.
	 *
	 * @return void
	 */
	public function check_page() : void {
		if ( ! isset( $_GET['paged'] ) ) {
			return;
		}

		$page = absint( $_GET['paged'] );

		if ( empty( $page ) ) {
			return;
		}

		// We do have a page number, set it
		$this->_page = $page;
	}

	/**
	 * Check for state selection.
	 *
	 * @return void
	 */
	public function check_state() : void {
		if ( ! isset( $_GET['state'] ) ) {
			return;
		}

		$state = sanitize_text_field( $_GET['state'] );

		if ( empty( $state ) ) {
			return;
		}

		// Change state only if it's "pending"
		if ( 'pending' === $state ) {
			$this->_state = 'Pending';
		}
	}

	/**
	 * Assign niche to pretty names.
	 *
	 * @return string
	 */
	public function niche_name( string $niche ) : string {
		$niches = Config::$niches;

		if ( isset( $niches[ $niche ] ) ) {
			return $niches[ $niche ];
		}

		return $niche;
	}

	/**
	 * Generate article by sending request to the API.
	 *
	 * @return void
	 */
	public function generate_article() : void {
		if ( ! isset( $_POST[ Config::PLUGIN_PREFIX . 'generate' ] ) ) {
			return;
		}

		// Verify nonce
		if ( ! Helper::verify_nonce() ) {
			$this->error = esc_html__( 'Request could not be validated.', 'kafkai' );
			return;
		}

		$niche = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'niche' ] );
		$seed  = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'seed' ] );

		// Ensure seed is 250 characters or less.
		if ( strlen( $seed ) > 250 ) {
			$seed = substr( $seed, 0, 250 );
		}

		// If seed is empty, set it to null.
		if ( empty( $seed ) ) {
			$seed = null;
		}

		// Empty fields
		if ( empty( $niche ) ) {
			$this->error = esc_html__( 'Niche is required for article generation.', 'kafkai' );
			return;
		}

		// Make connection to API
		$api      = new Api();
		$response = $api->call(
			'/articles/generate',
			'POST',
			array(
				'niche' => $niche,
				'seed'  => $seed,
			)
		);

		// If there was a valid response
		if ( $response ) {
			$data = json_decode( $api->response, true );

			// Check if an error is thrown by the API
			if ( isset( $data['errors'] ) ) {
				$this->error = $data['errors'][0];
				return;
			}

			// We should receive article ID
			if ( isset( $data['id'] ) ) {
				$this->code  = 'success';
				$this->error = sprintf(
					esc_html__( 'Article generation has been scheduled. Please wait a few minutes, then go to %1$sImport Article%2$s and click Refresh List.', 'kafkai' ),
					'<a href="' . self_admin_url( 'admin.php?page=' . Config::PLUGIN_PREFIX . 'import' ) . '">',
					'</a>'
				);
				return;
			}

			// Something went wrong, so showing a generic message
			$this->error = esc_html__( 'Something went wrong while receiving response from the API. Please try again.', 'kafkai' );
			return;
		}

		// Capture the error thrown by the API call
		$this->error = $api->error;
	}

	/**
	 * AJAX call handler for single article fetch.
	 *
	 * @return void
	 */
	public function fetch_single_article() : void {
		// Response and header
		$this->_set_header();

		$response = array(
			'code'  => 'error',
			'error' => esc_html__( 'Request does not seem to be a valid one. Please try again.', 'kafkai' ),
		);

		// Verify AJAX call for nonce and article ID
		if ( $this->_verify_ajax_call() ) {
			$article_id = sanitize_text_field( $_GET['article_id'] );

			// Make fetch call to the API
			$response = $this->_fetch_article_call( $article_id, $response );
		}

		// Send response back to the page
		echo json_encode( $response );
		$this->_terminate();
	}

	/**
	 * AJAX call handler for single article import.
	 *
	 * @todo Re-factor code into smaller functions for easier testing.
	 * @return void
	 */
	public function import_single_article() : void {
		// Response and header
		$this->_set_header();

		$response = array(
			'code'  => 'error',
			'error' => esc_html__( 'Request does not seem to be a valid one. Please try again.', 'kafkai' ),
		);

		// Verify AJAX call for nonce and article ID
		if ( $this->_verify_ajax_call() ) {
			// Make fetch call to the API
			$article_id = sanitize_text_field( $_GET['article_id'] );
			$response   = $this->_fetch_article_call( $article_id, $response );

			// On success, insert post into the database
			// Post that, we add Image & video via the API calls
			if ( 'success' === $response['code'] ) {
				// Check for author (if not, default to current_user_id)
				$post_author = $this->_update_post_author();

				// Post status
				$post_status = 'published';

				if ( isset( $_GET['article_status'] ) ) {
					if ( in_array( $_GET['article_status'], array_keys( get_post_statuses() ) ) ) {
						$post_status = sanitize_text_field( $_GET['article_status'] );
					}
				}

				// Insert post and look for post_id for a successfull insert
				$post_id = wp_insert_post(
					array(
						'post_author'  => $post_author,
						'post_content' => $response['response']['body'],
						'post_title'   => $response['response']['title'],
						'post_status'  => $post_status,
					)
				);

				// Check for errors
				if ( ! is_wp_error( $post_id ) ) {
					// Add article link to postmeta
					update_post_meta( $post_id, 'kafkai_article_id', $response['response']['id'] );

					// Update list of imported articles
					$this->_add_to_imported_list( $response['response']['id'] );

					$response['response'] = sprintf(
						esc_html__( 'Article has been imported successfully. %1$sOpen the Post%2$s', 'kafkai' ),
						'<a href="' . self_admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . '">',
						'</a>'
					);
				} else {
					$response['code']  = 'error';
					$response['error'] = esc_html__( 'There was a problem inserting article in the database. Please refresh the page and try again.', 'kafkai' );
				}
			}
		}

		// Send response back to the page
		echo json_encode( $response );
		$this->_terminate();
	}

	/**
	 * Get list of imported articles.
	 *
	 * @return void
	 */
	public function get_imported_article_ids() : void {
		$article_ids = get_option( Config::PLUGIN_PREFIX . 'imported_articles' );

		if ( ! $article_ids ) {
			return;
		}

		if ( ! is_array( $article_ids ) ) {
			return;
		}

		$this->imported_article_ids = $article_ids;
	}

	/**
	 * Set transient on successful fetch
	 *
	 * @return void
	 */
	private function _set_articles_transient() : void {
		// Set transient with expiry set to 24 hours
		set_transient( Config::PLUGIN_PREFIX . 'article_' . $this->_state . '_page' . $this->_page, $this->_articles, 86400 );
	}

	/**
	 * Check for _nonce and article ID. These two are must to proceed
	 * with the article request.
	 *
	 * @return bool
	 */
	private function _verify_ajax_call() : bool {
		// Check for _nonce and need article ID as well for making the API call
		if ( ! isset( $_GET['_nonce'] ) || ! isset( $_GET['article_id'] ) ) {
			return false;
		}

		if ( empty( $_GET['_nonce'] ) || empty( $_GET['article_id'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_nonce'] ), Config::PLUGIN_SLUG . '-nonce' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Making call to the API for fetching the article.
	 *
	 * @param string $article_id Article ID used to make a call to the API endpoint
	 * @param array  $response Array containing default response
	 *
	 * @return array
	 */
	private function _fetch_article_call( $article_id, $response ) : array {
		$transient = get_transient( Config::PLUGIN_PREFIX . 'single_' . $article_id );

		// Check for transient
		if ( $transient ) {
			$response['code']     = 'success';
			$response['response'] = $transient;

			return $response;
		}

		try {
			// Make connection to API
			$api  = new Api();
			$call = $api->call(
				'/articles/' . $article_id,
				'PATCH',
				array( 'state' => 'Read' )
			);

			// If there was a valid response
			if ( $call ) {
				$data = json_decode( $api->response, true );

				// Check if an error is thrown by the API
				if ( isset( $data['errors'] ) ) {
					$response['error'] = $data['errors'][0];
					return $response;
				}

				// Check for exceptions
				if ( isset( $data[0] ) ) {
					if ( isset( $data[0]['exception'] ) ) {
						$response['error'] = $data[0]['exception'];
					}

					if ( isset( $data[0]['message'] ) ) {
						$response['error'] = $data[0]['message'];
					}

					return $response;
				}

				// Looks good, add response to the array
				$response['code']     = 'success';
				$response['response'] = $data;

				// Set transient with expiry set to 24 hours
				set_transient( Config::PLUGIN_PREFIX . 'single_' . $data['id'], $data, 86400 );

				return $response;
			}
		} catch ( \Exception $e ) {
			$response['error'] = $e->getMessage();
			return $response;
		}

		return $response;
	}

	/**
	 * Check for post author and send back the appropriate user_id.
	 *
	 * @return boolean
	 */
	private function _update_post_author() {
		if ( ! isset( $_GET['article_author'] ) ) {
			return get_current_user_id();
		}

		$article_author = absint( $_GET['article_author'] );

		if ( empty( $article_author ) ) {
			return get_current_user_id();
		}

		return $article_author;
	}

	/**
	 * Update the list of imported articles for tracking them in view.
	 *
	 * @param string $article_id ID of the imported article
	 * @return void
	 */
	private function _add_to_imported_list( string $article_id ) : void {
		$imported_articles = get_option( Config::PLUGIN_PREFIX . 'imported_articles' );

		if ( ! $imported_articles ) {
			$imported_articles = array();
		}

		if ( in_array( $article_id, $imported_articles ) ) {
			return;
		}

		// Add article to the list
		array_push( $imported_articles, $article_id );

		// Update in the database
		update_option( Config::PLUGIN_PREFIX . 'imported_articles', $imported_articles );
	}

	/**
	 * Removes an article from the list of imported articles.
	 *
	 * @param string $article_id ID of the article to be removed from the list
	 * @return void
	 */
	private function _remove_from_imported_list( string $article_id ) : void {
		$imported_articles = get_option( Config::PLUGIN_PREFIX . 'imported_articles' );

		if ( ! $imported_articles ) {
			return;
		}

		// Find key for the given article ID
		$article_key = array_search( $article_id, $imported_articles );

		if ( false === $article_key ) {
			return;
		}

		// Remove article from the list
		unset( $imported_articles[ $article_key ] );

		// Update in the database
		update_option( Config::PLUGIN_PREFIX . 'imported_articles', $imported_articles );
	}

	/**
	 * Wrapper around the header() function.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	protected function _set_header() : void {
		header( 'Content-Type: application/json' );
	}

	/**
	 * Wrapper around the exit() function.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	protected function _terminate() : void {
		exit;
	}

}
