<?php
/**
 * Unit tests for `Admin` class.
 */

namespace Niteo\Kafkai\Plugin\Tests;

use Niteo\Kafkai\Plugin\Admin;
use PHPUnit\Framework\TestCase;

/**
 * Tests Admin class functions in isolation.
 *
 * @package Niteo\Kafkai\Plugin
 * @coversDefaultClass \Niteo\Kafkai\Plugin\Admin
 */
class AdminTest extends TestCase {

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
		$admin = new Admin();

		\WP_Mock::expectActionAdded( 'admin_menu', array( $admin, 'add_menu' ), PHP_INT_MAX );
		\WP_Mock::expectFilterAdded( 'plugin_row_meta', array( $admin, 'meta_links' ), 10, 2 );

		$admin->__construct();
		\WP_Mock::assertHooksAdded();
	}

	/**
	 * @covers ::__construct
	 * @covers ::add_menu
	 */
	public function testAddMenuNotAdmin() {
		$admin = new Admin();

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => false,
			)
		);

		$this->assertEmpty( $admin->add_menu() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::add_menu
	 */
	public function testAddMenuNoAccess() {
		$admin = new Admin();

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'return' => false,
			)
		);

		$this->assertEmpty( $admin->add_menu() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::add_menu
	 */
	public function testAddMenuSuccess() {
		$admin = new Admin();

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'add_menu_page',
			array(
				'return' => 'main_menu',
			)
		);

		\WP_Mock::userFunction(
			'add_submenu_page',
			array(
				'times'  => 3,
				'return' => 'sub_menu',
			)
		);

		$admin->add_menu();
	}

	/**
	 * @covers ::__construct
	 * @covers ::load_scripts
	 */
	public function testLoadScripts() {
		$admin = new Admin();

		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', array( $admin, 'admin_scripts' ) );

		$admin->load_scripts();
		\WP_Mock::assertHooksAdded();
	}

	/**
	 * @covers ::__construct
	 * @covers ::admin_scripts
	 */
	public function testAdminScripts() {
		$admin = new Admin();

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times'  => 2,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script',
			array(
				'times'  => 3,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'wp_localize_script',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'wp_create_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$this->assertEmpty( $admin->admin_scripts() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::meta_links
	 */
	public function testMetaLinksDiffPlugin() {
		$admin = new Admin();

		$this->assertEquals(
			array( 'first_link', 'second_link' ),
			$admin->meta_links( array( 'first_link', 'second_link' ), 'diff-plugin.php' )
		);
	}

	/**
	 * @covers ::__construct
	 * @covers ::meta_links
	 */
	public function testMetaLinksAddLink() {
		$admin = new Admin();

		$this->assertEquals(
			array( 'first_link', 'second_link', '<a href="https://kafkai.com" target="_blank">Website</a>' ),
			$admin->meta_links( array( 'first_link', 'second_link' ), 'kafkai.php' )
		);
	}

}
