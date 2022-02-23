<?php
/**
 * Unit tests for `Helper` trait.
 */

namespace Niteo\Kafkai\Plugin\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests Helper trait functions in isolation.
 *
 * @package Niteo\Kafkai\Plugin
 * @coversDefaultClass \Niteo\Kafkai\Plugin\Admin\Helper
 */
class AdminHelperTest extends TestCase {

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
	 * @covers ::get_settings
	 */
	public function testGetSettings() {
		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(
					'email'    => 'abc@xyz.com',
					'password' => 'secret',
				),
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 2,
				'return' => 'sanitized_value',
			)
		);

		$this->assertEquals(
			array(
				'email'    => 'sanitized_value',
				'password' => 'sanitized_value',
			),
			$mock->get_settings()
		);
	}

	/**
	 * @covers ::get_token
	 */
	public function testGetToken() {
		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => 'api_token',
			)
		);

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => 'api_token',
			)
		);

		$this->assertEquals(
			'api_token',
			$mock->get_token()
		);
	}

	/**
	 * @covers ::process_settings
	 */
	public function testProcessSettingsNoOption() {
		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		$this->assertEmpty( $mock->process_settings() );
	}

	/**
	 * @covers ::process_settings
	 */
	public function testProcessSettingsNonce() {
		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		$_POST['kafkaiwp_settings'] = 'value';
		$_POST['_kafkaiwp_nonce']   = 'wrong_nonce';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->assertEmpty( $mock->process_settings() );
	}

	/**
	 * @covers ::process_settings
	 */
	public function testProcessSettingsEmptyCredentials() {
		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		$_POST['kafkaiwp_settings'] = 'value';
		$_POST['_kafkaiwp_nonce']   = 'nonce';
		$_POST['kafkaiwp_email']    = 'email';
		$_POST['kafkaiwp_password'] = 'password';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 3,
				'return' => '',
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertEmpty( $mock->process_settings() );
	}

	/**
	 * @covers ::process_settings
	 */
	public function testProcessSettingsAPIFalseResponse() {
		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		$_POST['kafkaiwp_settings'] = 'value';
		$_POST['_kafkaiwp_nonce']   = 'nonce';
		$_POST['kafkaiwp_email']    = 'email';
		$_POST['kafkaiwp_password'] = 'password';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 3,
				'return' => 'some_value',
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
			'update_option',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertEmpty( $mock->process_settings() );
	}

	/**
	 * @covers ::process_settings
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testProcessSettingsAPIError() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\API' );
		$api_mock->shouldReceive( 'authenticate' )->andSet( 'response', '{"errors":["Error from the API"]}' )->andReturn( true );

		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		$_POST['kafkaiwp_settings'] = 'value';
		$_POST['_kafkaiwp_nonce']   = 'nonce';
		$_POST['kafkaiwp_email']    = 'email';
		$_POST['kafkaiwp_password'] = 'password';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 3,
				'return' => 'some_value',
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
			'update_option',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertEmpty( $mock->process_settings() );
	}

	/**
	 * @covers ::process_settings
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testProcessSettingsNoToken() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\API' );
		$api_mock->shouldReceive( 'authenticate' )->andSet( 'response', '{"response": "any response other than error"}' )->andReturn( true );

		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		$_POST['kafkaiwp_settings'] = 'value';
		$_POST['_kafkaiwp_nonce']   = 'nonce';
		$_POST['kafkaiwp_email']    = 'email';
		$_POST['kafkaiwp_password'] = 'password';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 3,
				'return' => 'some_value',
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
			'update_option',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertEmpty( $mock->process_settings() );
	}

	/**
	 * @covers ::process_settings
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testProcessSettingsTokenSet() {
		$api_mock = \Mockery::mock( 'overload:\Niteo\Kafkai\Plugin\Admin\API' );
		$api_mock->shouldReceive( 'authenticate' )->andSet( 'response', '{"token": "TOKEN"}' )->andReturn( true );

		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		$_POST['kafkaiwp_settings'] = 'value';
		$_POST['_kafkaiwp_nonce']   = 'nonce';
		$_POST['kafkaiwp_email']    = 'email';
		$_POST['kafkaiwp_password'] = 'password';

		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'times'  => 4,
				'return' => 'some_value',
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
			'update_option',
			array(
				'times'  => 3,
				'return' => true,
			)
		);

		$this->assertEmpty( $mock->process_settings() );
	}

	/**
	 * @covers ::add_notice
	 */
	public function testAddNotice() {
		$mock = $this->getMockBuilder( '\Niteo\Kafkai\Plugin\Admin\Helper' )->getMockForTrait();

		$this->expectOutputString( '<div class="notice notice-error is-dismissible"><p>there was an error</p></div>' );
		$mock->add_notice( 'error', 'there was an error' );
	}

}
