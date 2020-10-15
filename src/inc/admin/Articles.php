<?php
/**
 * Articles management for the plugin.
 */

namespace Niteo\Kafkai\Plugin\Admin;

use Niteo\Kafkai\Plugin\Config;

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
	 * On initialization, add default response and check for current page.
	 */
	public function __construct() {
		$this->error = esc_html__( 'There was an error processing your request. Please try again.', 'kafkai-wp' );

		// Set proper page number
		$this->_check_page();

		// Check state
		$this->_check_state();

		// Get articles from API
		$this->import_articles();
	}

	/**
	 * Importing articles for the user.
	 *
	 * @return void
	 */
	public function import_articles() : void {
		// Article state
		$state = $this->_state;

		if ( 'All' === $this->_state ) {
			$state = 'Generated,Read,Accepted';
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
				$this->_articles[ $article['id'] ] = array(
					'state' => sanitize_text_field( $article['state'] ),
					'niche' => sanitize_text_field( $article['niche'] ),
					'title' => sanitize_text_field( $article['title'] ),
				);
			}

			// Set transient
			$this->_set_transient();

			$this->code     = 'success';
			$this->response = $this->_articles;

			return;
		}

		// Capture the error thrown by the API call
		$this->error = $api->error;
	}

	/**
	 * Check for current page.
	 *
	 * @return void
	 */
	private function _check_page() : void {
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
	private function _check_state() : void {
		if ( ! isset( $_GET['status'] ) ) {
			return;
		}

		$state = sanitize_text_field( $_GET['status'] );

		if ( empty( $state ) ) {
			return;
		}

		// Change state only if it's "pending"
		if ( 'pending' === $state ) {
			$this->_state = 'Pending';
		}
	}

	/**
	 * Set transient on successful fetch
	 *
	 * @return void
	 */
	private function _set_transient() : void {
		if ( ! array( $this->_articles ) ) {
			return;
		}

		// Set transient with expiry set to 24 hours
		set_transient( Config::PLUGIN_PREFIX . 'article_' . $this->_state . '_page' . $this->_page, $this->_articles, 86400 );
	}

}
