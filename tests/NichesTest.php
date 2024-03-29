<?php
/**
 * Unit tests for `Niches` class.
 */

namespace Niteo\Kafkai\Plugin\Tests;

use Niteo\Kafkai\Plugin\Admin\Niches;
use PHPUnit\Framework\TestCase;

/**
 * Tests Niches class functions in isolation.
 *
 * @package Niteo\Kafkai\Plugin
 * @coversDefaultClass \Niteo\Kafkai\Plugin\Admin\Niches
 */
class NichesTest extends TestCase {

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
		$niches = new Niches();

		\WP_Mock::expectActionAdded( 'current_screen', array( $niches, 'check_niches_and_languages' ) );
		\WP_Mock::expectActionAdded( 'admin_notices', array( $niches, 'admin_notices' ) );
		\WP_Mock::expectActionAdded( 'kafkaiwp_settings', array( $niches, 'add_update_button' ) );

		$niches->__construct();
		\WP_Mock::assertHooksAdded();
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_niches_and_languages
	 * @covers ::is_plugin_page
	 */
	public function testCheckNichesWrongPage() {
		$niches        = new Niches();
		$current_screen = (object) array(
			'id' => 'wrong_page',
		);

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => $current_screen,
			)
		);

		$this->assertEmpty( $niches->check_niches_and_languages() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_niches_and_languages
	 * @covers ::is_plugin_page
	 */
	public function testCheckNichesNoTransientNoData() {
		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Niches' )->makePartial();
		$mock->shouldReceive( 'api_call' )->andReturn( false );

		$current_screen = (object) array(
			'id' => 'kafkaiwp_import',
		);

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => $current_screen,
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->assertEmpty( $mock->check_niches_and_languages() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_niches_and_languages
	 * @covers ::is_plugin_page
	 */
	public function testCheckNichesNoTransientWithData() {
		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Niches' )->makePartial();
		$mock->shouldReceive( 'api_call' )->andReturn( 'DUMMY_DATA' );
		$mock->shouldReceive( 'yaml_parse_and_check' )->andReturn( true );

		$current_screen = (object) array(
			'id' => 'kafkaiwp_import',
		);

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => $current_screen,
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->assertEmpty( $mock->check_niches_and_languages() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::check_niches_and_languages
	 * @covers ::is_plugin_page
	 */
	public function testCheckNichesWithTransient() {
		$mock = \Mockery::mock( '\Niteo\Kafkai\Plugin\Admin\Niches' )->makePartial();
		$mock->shouldReceive( 'yaml_parse_and_check' )->andReturn( true );

		$current_screen = (object) array(
			'id' => 'kafkaiwp_import',
		);

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => $current_screen,
			)
		);

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertEmpty( $mock->check_niches_and_languages() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::admin_notices
	 * @covers ::is_plugin_page
	 */
	public function testAddAdminNichesWrongPage() {
		global $current_screen;

		$current_screen = (object) array(
			'id' => 'not_plugin_page',
		);
		$niches        = new Niches();

		$this->assertEmpty( $niches->admin_notices() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::admin_notices
	 * @covers ::is_plugin_page
	 */
	public function testAdminNichesNoTransient() {
		global $current_screen;

		$current_screen = (object) array(
			'id' => 'kafkaiwp_import',
		);
		$niches        = new Niches();

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 2,
				'return' => false,
			)
		);

		$this->assertEmpty( $niches->admin_notices() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::admin_notices
	 * @covers ::add_notification
	 * @covers ::is_plugin_page
	 */
	public function testAdminNichesSuccess() {
		global $current_screen;

		$current_screen = (object) array(
			'id' => 'kafkaiwp_import',
		);
		$niches        = new Niches();

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'times'  => 2,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'self_admin_url',
			array(
				'times'  => 2,
				'return' => 'admin.php?page=kafkaiwp_settings',
			)
		);

		$this->expectOutputString( '<div class="notice notice-info"><p>New niches are available for the plugin. Please go to <a href="admin.php?page=kafkaiwp_settings">Settings page</a> to update.</p></div><div class="notice notice-info"><p>New languages are available for the plugin. Please go to <a href="admin.php?page=kafkaiwp_settings">Settings page</a> to update.</p></div>' );
		$niches->admin_notices();
	}

	/**
	 * @covers ::__construct
	 * @covers ::add_update_button
	 */
	public function testUpdateButton() {
		$niches = new Niches();

		$this->expectOutputString( '&nbsp;<input type="submit" name="kafkaiwp_update_data" value="Update Niches & Languages" class="button button-secondary">' );
		$niches->add_update_button();
	}

}
