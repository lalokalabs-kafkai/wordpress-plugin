<?php
/**
 * Unit tests for `Articles` class.
 */

namespace Niteo\Kafkai\Plugin\Tests;

use Niteo\Kafkai\Plugin\Admin\Articles;
use PHPUnit\Framework\TestCase;

/**
 * Tests Articles class functions in isolation.
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
        'times' => 1,
				'return' => 'transient_data',
			)
		);

    $this->assertEmpty( $articles->fetch_articles() );
  }

}
