<?php
/**
 * Unit tests for `API` class.
 */

namespace Niteo\Kafkai\Plugin\Tests;

use Niteo\Kafkai\Plugin\Admin\API;
use PHPUnit\Framework\TestCase;

/**
 * Tests API class functions in isolation.
 *
 * @package Niteo\Kafkai\Plugin
 * @coversDefaultClass \Niteo\Kafkai\Plugin\Admin\API
 */
class APITest extends TestCase {

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
	 * @covers ::authenticate
	 * @covers ::get_credentials
	 */
	public function testAuthenticateNoCredentials() {
		$api = new API();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->assertFalse( $api->authenticate() );
	}

	/**
	 * @covers ::authenticate
	 * @covers ::get_credentials
	 */
	public function testAuthenticateNotArray() {
		$api = new API();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => 'string_value',
			)
		);

		$this->assertFalse( $api->authenticate() );
	}

	/**
	 * @covers ::authenticate
	 * @covers ::get_credentials
	 */
	public function testAuthenticateWrongKeys() {
		$api = new API();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(
					'not_email'    => '',
					'not_password' => '',
				),
			)
		);

		$this->assertFalse( $api->authenticate() );
	}

	/**
	 * @covers ::authenticate
	 * @covers ::get_credentials
	 */
	public function testAuthenticateReturnTrue() {
		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\API' )->makePartial();
		$mock->shouldReceive( 'call' )->andReturn( true );

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(
					'email'    => '',
					'password' => '',
				),
			)
		);

		$this->assertTrue( $mock->authenticate() );
	}

	/**
	 * @covers ::authenticate
	 * @covers ::call
	 * @covers ::get_token
	 * @covers ::verify_token
	 */
	public function testCallUnverifiedToken() {
		$api = new API();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertFalse( $api->call( '/endpoint', 'POST' ) );
	}

	/**
	 * @covers ::authenticate
	 * @covers ::call
	 * @covers ::get_token
	 * @covers ::verify_token
	 */
	public function testCallVerifiedToken() {
		$api = new API();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$mocked_request = new class() {
			function get_error_message() {
				return 'error message is returned';
			}
		};

		\WP_Mock::userFunction(
			'wp_remote_request',
			array(
				'times'  => 1,
				'return' => $mocked_request,
			)
		);

		\WP_Mock::userFunction(
			'is_wp_error',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertFalse( $api->call( '/endpoint', 'GET' ) );
	}

	/**
	 * @covers ::authenticate
	 * @covers ::call
	 * @covers ::get_token
	 * @covers ::verify_token
	 */
	public function testCallPostSuccess() {
		$api = new API();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'wp_remote_request',
			array(
				'times'  => 1,
				'return' => array(
					'body' => 'Response from the API',
				),
			)
		);

		\WP_Mock::userFunction(
			'is_wp_error',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->assertTrue( $api->call( '/endpoint', 'POST', array( 'message' => 'body_content' ) ) );
	}

	/**
	 * @covers ::set_apiurl
	 */
	public function testSetAPIUrl() {
		$api = new API();

		$this->assertEmpty( $api->set_apiurl( 'URL' ) );
	}

	/**
	 * @covers ::set_args
	 */
	public function testSetArgs() {
		$api = new API();

		$this->assertEmpty( $api->set_args( array() ) );
	}

	/**
	 * @covers ::set_headers
	 */
	public function testSetHeader() {
		$api = new API();

		$this->assertEmpty( $api->set_headers( array() ) );
	}

}
