<?php
/**
 * Unit tests for `Helper` class.
 */

namespace Niteo\Kafkai\Plugin\Tests;

use Niteo\Kafkai\Plugin\Helper;
use PHPUnit\Framework\TestCase;

/**
 * Tests Helper class functions in isolation.
 * @package Niteo\Kafkai\Plugin
 * @coversDefaultClass \Niteo\Kafkai\Plugin\Helper
 */
class HelperTest extends TestCase {

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
   * @covers ::verify_nonce
	 */
	public function testVerifyNonceFalse() {
    $_POST['_kafkaiwp_nonce'] = 'nonce_unfiltered';

    \WP_Mock::userFunction(
			'sanitize_text_field',
			array(
        'times' => 1,
				'return' => 'nonce_filtered',
			)
		);

    \WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
        'times' => 1,
				'return' => false,
			)
		);

    $this->assertFalse( Helper::verify_nonce() );
  }

  /**
   * @covers ::verify_nonce
	 */
	public function testVerifyNonceTrue() {
    $_POST['_kafkaiwp_nonce'] = 'nonce_unfiltered';

    \WP_Mock::userFunction(
			'sanitize_text_field',
			array(
        'times' => 1,
				'return' => 'nonce_filtered',
			)
		);

    \WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
        'times' => 1,
				'return' => true,
			)
		);

    $this->assertTrue( Helper::verify_nonce() );
  }

}
