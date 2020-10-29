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
		// Add niches
		$this->niches = array(
			'Affiliate'       => esc_html__( 'Affiliate', 'kafkai-wp' ),
			'Automotive'      => esc_html__( 'Automotive', 'kafkai-wp' ),
			'Business'        => esc_html__( 'Business', 'kafkai-wp' ),
			'CyberSecurity'   => esc_html__( 'Cyber Security', 'kafkai-wp' ),
			'Dating'          => esc_html__( 'Dating', 'kafkai-wp' ),
			'Dogs'            => esc_html__( 'Dogs', 'kafkai-wp' ),
			'Fashion'         => esc_html__( 'Fashion', 'kafkai-wp' ),
			'Finance'         => esc_html__( 'Finance', 'kafkai-wp' ),
			'Health'          => esc_html__( 'Health', 'kafkai-wp' ),
			'HomeAndFamily'   => esc_html__( 'Home and Family', 'kafkai-wp' ),
			'HomeImprovement' => esc_html__( 'Home Improvement', 'kafkai-wp' ),
			'Nutrition'       => esc_html__( 'Nutrition', 'kafkai-wp' ),
			'OnlineMarketing' => esc_html__( 'Online Marketing', 'kafkai-wp' ),
			'SelfImprovement' => esc_html__( 'Self Improvement', 'kafkai-wp' ),
			'Seo'             => esc_html__( 'SEO', 'kafkai-wp' ),
			'Software'        => esc_html__( 'Software', 'kafkai-wp' ),
			'Spirituality'    => esc_html__( 'Spirituality', 'kafkai-wp' ),
			'Travel'          => esc_html__( 'Travel', 'kafkai-wp' ),
			'WeightLoss'      => esc_html__( 'Weight Loss', 'kafkai-wp' ),
			'Experimental'    => esc_html__( 'Experimental', 'kafkai-wp' ),
		);

		add_action( 'wp_ajax_' . Config::PLUGIN_PREFIX . 'fetch_article', array( $this, 'fetch_single_article' ) );
		add_action( 'wp_ajax_' . Config::PLUGIN_PREFIX . 'import_article', array( $this, 'import_single_article' ) );
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
	 * Set transient on successful fetch
	 *
	 * @return void
	 */
	private function _set_articles_transient() : void {
		if ( ! array( $this->_articles ) ) {
			return;
		}

		// Set transient with expiry set to 24 hours
		set_transient( Config::PLUGIN_PREFIX . 'article_' . $this->_state . '_page' . $this->_page, $this->_articles, 86400 );
	}

	/**
	 * Assign niche to pretty names.
	 *
	 * @return string
	 */
	public function niche_name( string $niche ) : string {
		if ( isset( $this->niches[ $niche ] ) ) {
			return $this->niches[ $niche ];
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
			$this->error = esc_html__( 'Request could not be validated.', 'kafkai-wp' );
			return;
		}

		$niche = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'niche' ] );
		$title = sanitize_text_field( $_POST[ Config::PLUGIN_PREFIX . 'title' ] );

		// Empty fields
		if ( empty( $niche ) || empty( $title ) ) {
			$this->error = esc_html__( 'Both fields are required for article generation.', 'kafkai-wp' );
			return;
		}

		// Make connection to API
		$api      = new Api();
		$response = $api->call(
			'/articles/generate',
			'POST',
			array(
				'niche' => $niche,
				'title' => $title,
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
				$this->error = esc_html__( 'Article generation has been scheduled. It will be generated shortly.', 'kafkai-wp' );
				return;
			}

			// Something went wrong, so showing a generic message
			$this->error = esc_html__( 'Something went wrong while receiving response from the API. Please try again.', 'kafkai-wp' );
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
		header( 'Content-Type: application/json' );
		$response = array(
			'code'  => 'error',
			'error' => esc_html__( 'Request does not seem to be a valid one. Please try again.', 'kafkai-wp' ),
		);

		// Verify AJAX call for nonce and article ID
		if ( $this->_verify_ajax_call() ) {
			$article_id = sanitize_text_field( $_GET['article_id'] );

			// Make fetch call to the API
			$response = $this->_fetch_article_call( $article_id, $response );
		}

		// Send response back to the page
		echo json_encode( $response );
		exit;
	}

	/**
	 * AJAX call handler for single article import.
	 *
	 * @return void
	 */
	public function import_single_article() : void {
		// Response and header
		header( 'Content-Type: application/json' );
		$response = array(
			'code'  => 'error',
			'error' => esc_html__( 'Request does not seem to be a valid one. Please try again.', 'kafkai-wp' ),
		);

		// Verify AJAX call for nonce and article ID
		if ( ! $this->_verify_ajax_call() ) {
			echo json_encode( $response );
			exit;
		}

		if ( ! $this->_verify_import_call() ) {
			$response['error'] = esc_html__( 'Keyword is required if the Image or Video option is checked. Please provide a keyword or uncheck both of the media options.', 'kafkai-wp' );

			echo json_encode( $response );
			exit;
		}

		// Make fetch call to the API
		$article_id = sanitize_text_field( $_GET['article_id'] );
		$response   = $this->_fetch_article_call( $article_id, $response );

		// On success, insert post into the database
		// Post that, we add Image & video via the API calls

		// Send response back to the page
		echo json_encode( $response );
		exit;
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
	 * Verify import call to check for presence of keyword for adding image and video
	 * to the post.
	 *
	 * @return boolean
	 */
	private function _verify_import_call() {
		if ( ! isset( $_GET['article_keyword'] ) || ! isset( $_GET['article_image'] ) || ! isset( $_GET['article_video'] ) ) {
			return false;
		}

		if ( empty( $_GET['article_keyword'] ) || empty( $_GET['article_image'] ) || empty( $_GET['article_video'] ) ) {
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
				'GET'
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
					if ( isset( $data[0]['exception'] ) || isset( $data[0]['message'] ) ) {
						$response['error'] = $data[0]['message'];
						return $response;
					}
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

}
