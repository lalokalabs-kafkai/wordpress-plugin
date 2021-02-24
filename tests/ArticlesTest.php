<?php
/**
 * Unit tests for `Articles` class.
 */

namespace Niteo\Kafkai\Plugin\Tests;

use Niteo\Kafkai\Plugin\Admin\Articles;
use PHPUnit\Framework\TestCase;

/**
 * Tests Articles class functions in isolation.
 *
 * @package Niteo\Kafkai\Plugin
 * @coversDefaultClass \Niteo\Kafkai\Plugin\Admin\Articles
 */
class ArticlesTest extends TestCase {

	function setUp() : void {
		\WP_Mock::setUsePatchwork( true );
		\WP_Mock::setUp();
	}

	function tearDown() : void {
		$this->addToAssertionCount(
			\Mockery::getContainer()->mockery_getExpectationCount()
		);

		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstructor() {
		$articles = new Articles();

		\WP_Mock::expectActionAdded( 'wp_ajax_kafkaiwp_fetch_article', array( $articles, 'fetch_single_article' ) );
		\WP_Mock::expectActionAdded( 'wp_ajax_kafkaiwp_import_article', array( $articles, 'import_single_article' ) );
		\WP_Mock::expectActionAdded( 'before_delete_post', array( $articles, 'delete_single_article' ), 10, 2 );

		$articles->__construct();
		\WP_Mock::assertHooksAdded();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_articles
	 */
	public function testFetchArticlesWithTransient() {
		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => 'transient_data',
			)
		);

		$this->assertEmpty( $articles->fetch_articles() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_articles
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFetchArticlesApiError() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'response', '{"errors":["Error from the API"]}' )->andReturn( true );

		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->assertEmpty( $articles->fetch_articles() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_articles
	 * @covers ::_set_articles_transient
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFetchArticlesWithData() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'response', '{"articles":[{"id":"abc0-def1-ghi3-jkl4","state":"Generated","niche":"Sports","title":"Article Title","createdAt":"1900-01-01 0000:00:00"}],"pageCount":10,"total":50}' )->andReturn( true );

		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 4,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'absint',
			array(
				'times'  => 2,
				'return' => true,
			)
		);

		$this->assertEmpty( $articles->fetch_articles() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_articles
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFetchArticlesCallError() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'error', 'API returned an error.' )->andReturn( false );

		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->assertEmpty( $articles->fetch_articles() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::delete_single_article
	 */
	public function testDeleteSingleArticleNoID() {
		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$wp_post_obj = \Mockery::mock( '\WP_Post' );
		$this->assertEmpty( $articles->delete_single_article( 10, $wp_post_obj ) );
	}

	/**
	 * @covers ::__construct
	 * @covers ::delete_single_article
	 * @covers ::_remove_from_imported_list
	 */
	public function testDeleteSingleArticleNoImportedArticles() {
		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'return' => 'ARTICLE_ID',
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$wp_post_obj = \Mockery::mock( '\WP_Post' );
		$this->assertEmpty( $articles->delete_single_article( 10, $wp_post_obj ) );
	}

	/**
	 * @covers ::__construct
	 * @covers ::delete_single_article
	 * @covers ::_remove_from_imported_list
	 */
	public function testDeleteSingleArticleFalseSearch() {
		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'return' => 'FIRST_ARTICLE',
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(
					'SECOND_ARTICLE',
					'THIRD_ARTICLE',
				),
			)
		);

		$wp_post_obj = \Mockery::mock( '\WP_Post' );
		$this->assertEmpty( $articles->delete_single_article( 10, $wp_post_obj ) );
	}

	/**
	 * @covers ::__construct
	 * @covers ::delete_single_article
	 * @covers ::_remove_from_imported_list
	 */
	public function testDeleteSingleArticleSuccess() {
		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'times'  => 1,
				'return' => 'FIRST_ARTICLE',
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(
					'FIRST_ARTICLE',
					'SECOND_ARTICLE',
					'THIRD_ARTICLE',
				),
			)
		);

		\WP_Mock::userFunction(
			'update_option',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$wp_post_obj = \Mockery::mock( '\WP_Post' );
		$this->assertEmpty( $articles->delete_single_article( 10, $wp_post_obj ) );
	}

	/**
	 * @covers ::__construct
	 * @covers ::refresh_list
	 */
	public function testRefreshListNoAction() {
		$articles = new Articles();

		$this->assertEmpty( $articles->refresh_list() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::refresh_list
	 */
	public function testRefreshListWrongAction() {
		$articles       = new Articles();
		$_GET['action'] = 'other_action';

		$this->assertEmpty( $articles->refresh_list() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::refresh_list
	 */
	public function testRefreshListSuccess() {
		global $wpdb;

		$_GET['action'] = 'refresh_list';

		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$wpdb         = \Mockery::mock( '\WPDB' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'SOME_QUERY' );
		$wpdb->shouldReceive( 'query' )->andReturn( true );

		\WP_Mock::userFunction(
			'wp_using_ext_object_cache',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'wp_cache_delete',
			array(
				'times'  => 10,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'self_admin_url',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'wp_safe_redirect',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertEmpty( $mock->refresh_list() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_page
	 */
	public function testCheckPageNoPaged() {
		$articles = new Articles();

		$this->assertEmpty( $articles->check_page() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_page
	 */
	public function testCheckPageEmptyPaged() {
		$articles = new Articles();

		$_GET['paged'] = 0;

		\WP_Mock::userFunction(
			'absint',
			array(
				'times'  => 1,
				'return' => '',
			)
		);

		$this->assertEmpty( $articles->check_page() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_page
	 */
	public function testCheckPageSuccess() {
		$articles = new Articles();

		$_GET['paged'] = 1;

		\WP_Mock::userFunction(
			'absint',
			array(
				'times'  => 1,
				'return' => 1,
			)
		);

		$this->assertEmpty( $articles->check_page() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_state
	 */
	public function testCheckStateNoState() {
		$articles = new Articles();

		$this->assertEmpty( $articles->check_state() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_state
	 */
	public function testCheckStateEmptyState() {
		$articles = new Articles();

		$_GET['state'] = '';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => '',
			)
		);

		$this->assertEmpty( $articles->check_state() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_state
	 */
	public function testCheckStatePendingState() {
		$articles = new Articles();

		$_GET['state'] = '';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => 'pending',
			)
		);

		$this->assertEmpty( $articles->check_state() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::niche_name
	 */
	public function testNicheNameFound() {
		$articles = new Articles();

		$this->assertEquals( 'Self Improvement', $articles->niche_name( 'SelfImprovement' ) );
	}

	/**
	 * @covers ::__construct
	 * @covers ::niche_name
	 */
	public function testNicheNameNotFound() {
		$articles = new Articles();

		$this->assertEquals( 'NotFoundNiche', $articles->niche_name( 'NotFoundNiche' ) );
	}

	/**
	 * @covers ::__construct
	 * @covers ::generate_article
	 */
	public function testGenerateArticleNoPostData() {
		$articles = new Articles();

		$this->assertEmpty( $articles->generate_article() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::generate_article
	 */
	public function testGenerateArticleWrongNonce() {
		$articles = new Articles();

		$_POST['kafkaiwp_generate'] = 'nonce_value';
		$_POST['_kafkaiwp_nonce']   = 'diff_value';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => 'nonce',
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->assertEmpty( $articles->generate_article() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::generate_article
	 */
	public function testGenerateArticleEmptyNicheAndSeed() {
		$articles = new Articles();

		$_POST['kafkaiwp_generate'] = 'nonce_value';
		$_POST['_kafkaiwp_nonce']   = 'nonce_value';
		$_POST['kafkaiwp_niche']    = 'niche';
		$_POST['kafkaiwp_seed']     = 'seed';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => 'nonce',
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => '',
			)
		);

		$this->assertEmpty( $articles->generate_article() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::generate_article
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testGenerateArticleApiError() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'response', '{"errors":["Error from the API"]}' )->andReturn( true );

		$articles = new Articles();

		$_POST['kafkaiwp_generate'] = 'nonce_value';
		$_POST['_kafkaiwp_nonce']   = 'nonce_value';
		$_POST['kafkaiwp_niche']    = 'niche';
		$_POST['kafkaiwp_seed']     = 'seed';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => 'nonce',
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc ornare convallis diam, ac malesuada libero aliquam id. Morbi auctor eget dui sed hendrerit. Maecenas nec sapien ac enim tincidunt sagittis et eget metus. Cras ut ullamcorper eros proin et euismod.',
			)
		);

		$this->assertEmpty( $articles->generate_article() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::generate_article
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testGenerateArticleApiSuccess() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'response', '{"id":"ARTICLE_ID"}' )->andReturn( true );

		$articles = new Articles();

		$_POST['kafkaiwp_generate'] = 'nonce_value';
		$_POST['_kafkaiwp_nonce']   = 'nonce_value';
		$_POST['kafkaiwp_niche']    = 'niche';
		$_POST['kafkaiwp_seed']     = 'seed';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => 'nonce',
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'SOME_VALUE',
			)
		);

		\WP_Mock::userFunction(
			'self_admin_url',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertEmpty( $articles->generate_article() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::generate_article
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testGenerateArticleOtherApiError() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'response', '{"unknown":"error"}' )->andReturn( true );

		$articles = new Articles();

		$_POST['kafkaiwp_generate'] = 'nonce_value';
		$_POST['_kafkaiwp_nonce']   = 'nonce_value';
		$_POST['kafkaiwp_niche']    = 'niche';
		$_POST['kafkaiwp_seed']     = 'seed';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => 'nonce',
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'SOME_VALUE',
			)
		);

		$this->assertEmpty( $articles->generate_article() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::generate_article
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testGenerateArticleFalseApiResponse() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'error', 'false response received from the API' )->andReturn( false );

		$articles = new Articles();

		$_POST['kafkaiwp_generate'] = 'nonce_value';
		$_POST['_kafkaiwp_nonce']   = 'nonce_value';
		$_POST['kafkaiwp_niche']    = 'niche';
		$_POST['kafkaiwp_seed']     = 'seed';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => 'nonce',
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'SOME_VALUE',
			)
		);

		$this->assertEmpty( $articles->generate_article() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 */
	public function testFetchSingleArticleNoNonce() {
		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$this->expectOutputString( '{"code":"error","error":"Request does not seem to be a valid one. Please try again."}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 */
	public function testFetchSingleArticleEmptyId() {
		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$_GET['_nonce']     = 'nonce';
		$_GET['article_id'] = '';

		$this->expectOutputString( '{"code":"error","error":"Request does not seem to be a valid one. Please try again."}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 */
	public function testFetchSingleArticleWrongNonce() {
		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$_GET['_nonce']     = 'nonce';
		$_GET['article_id'] = 'ARTICLE_ID';

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->expectOutputString( '{"code":"error","error":"Request does not seem to be a valid one. Please try again."}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 * @covers ::_fetch_article_call
	 */
	public function testFetchSingleArticleTrasientData() {
		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$_GET['_nonce']     = 'nonce';
		$_GET['article_id'] = 'ARTICLE_ID';

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'ARTICLE_ID',
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => array(
					'code'     => 'success',
					'response' => 'This is coming from the transient',
				),
			)
		);

		$this->expectOutputString( '{"code":"success","error":"Request does not seem to be a valid one. Please try again.","response":{"code":"success","response":"This is coming from the transient"}}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 * @covers ::_fetch_article_call
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFetchSingleArticleApiError() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'response', '{"errors":["Error from the API"]}' )->andReturn( true );

		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$_GET['_nonce']     = 'nonce';
		$_GET['article_id'] = 'ARTICLE_ID';

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'ARTICLE_ID',
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->expectOutputString( '{"code":"error","error":"Error from the API"}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 * @covers ::_fetch_article_call
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFetchSingleArticleApiException() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'response', '[{"exception":"This exception is coming from the API"}]' )->andReturn( true );

		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$_GET['_nonce']     = 'nonce';
		$_GET['article_id'] = 'ARTICLE_ID';

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'ARTICLE_ID',
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->expectOutputString( '{"code":"error","error":"This exception is coming from the API"}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 * @covers ::_fetch_article_call
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFetchSingleArticleApiMessage() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'response', '[{"message":"This message is coming from the API"}]' )->andReturn( true );

		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$_GET['_nonce']     = 'nonce';
		$_GET['article_id'] = 'ARTICLE_ID';

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'ARTICLE_ID',
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->expectOutputString( '{"code":"error","error":"This message is coming from the API"}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 * @covers ::_fetch_article_call
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFetchSingleArticleSuccess() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andSet( 'response', '{"id":"ARTICLE_ID"}' )->andReturn( true );

		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$_GET['_nonce']     = 'nonce';
		$_GET['article_id'] = 'ARTICLE_ID';

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'ARTICLE_ID',
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->expectOutputString( '{"code":"success","error":"Request does not seem to be a valid one. Please try again.","response":{"id":"ARTICLE_ID"}}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 * @covers ::_fetch_article_call
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFetchSingleArticleFalseApiCall() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andReturn( false );

		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$_GET['_nonce']     = 'nonce';
		$_GET['article_id'] = 'ARTICLE_ID';

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'ARTICLE_ID',
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->expectOutputString( '{"code":"error","error":"Request does not seem to be a valid one. Please try again."}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::fetch_single_article
	 * @covers ::_verify_ajax_call
	 * @covers ::_fetch_article_call
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFetchSingleArticleExceptionHandling() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\Api' );
		$api_mock->shouldReceive( 'call' )->andThrow( 'exception', 'API exception was handled gracefully by the plugin.' );

		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$_GET['_nonce']     = 'nonce';
		$_GET['article_id'] = 'ARTICLE_ID';

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'ARTICLE_ID',
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->expectOutputString( '{"code":"error","error":"API exception was handled gracefully by the plugin."}' );
		$mock->fetch_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::import_single_article
	 */
	public function testImportSingleArticleUnverifiedCall() {
		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Articles' )->makePartial()->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( '_set_header' )->andReturn( true );
		$mock->shouldReceive( '_terminate' )->andReturn( true );

		$this->expectOutputString( '{"code":"error","error":"Request does not seem to be a valid one. Please try again."}' );
		$mock->import_single_article();
	}

	/**
	 * @covers ::__construct
	 * @covers ::get_imported_article_ids
	 */
	public function testGetImportedArticleIdsNoOption() {
		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->assertEmpty( $articles->get_imported_article_ids() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::get_imported_article_ids
	 */
	public function testGetImportedArticleIdsWrongOption() {
		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => 'string instead of array',
			)
		);

		$this->assertEmpty( $articles->get_imported_article_ids() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::get_imported_article_ids
	 */
	public function testGetImportedArticleIdsSuccess() {
		$articles = new Articles();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(
					'ARTICLE_ID_ONE',
					'ARTICLE_ID_TWO',
					'ARTICLE_ID_THREE',
				),
			)
		);

		$this->assertEmpty( $articles->get_imported_article_ids() );
	}

}
