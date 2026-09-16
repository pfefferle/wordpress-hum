<?php
/**
 * Test file for hum.php.
 *
 * @package Hum
 */

namespace Hum\Tests;

/**
 * Test class for the functions in the main plugin file.
 */
class Test_Hum extends \WP_UnitTestCase {

	/**
	 * Post ID whose base-32 representation ("fba") survives the hex filter.
	 *
	 * @var int
	 */
	const KNOWN_POST_ID = 15722;

	/**
	 * Path of the plugin directory.
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * Set up the test.
	 */
	public function set_up() {
		parent::set_up();

		$this->plugin_dir = \dirname( __DIR__, 3 );
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down() {
		remove_all_filters( 'hum_enable_legacy_ftl' );

		parent::tear_down();
	}

	/**
	 * Create a post with a predictable ID.
	 *
	 * @return int Post ID.
	 */
	protected function create_known_post() {
		return self::factory()->post->create( array( 'import_id' => self::KNOWN_POST_ID ) );
	}

	/**
	 * The activation and deactivation hooks hang off the main plugin file.
	 */
	public function test_activation_hooks_hang_off_the_main_plugin_file() {
		$plugin = \plugin_basename( $this->plugin_dir . '/hum.php' );

		$this->assertNotFalse( \has_action( 'activate_' . $plugin, 'Hum\activate' ) );
		$this->assertNotFalse( \has_action( 'deactivate_' . $plugin, 'Hum\deactivate' ) );
	}

	/**
	 * The plugin hooks plain functions on `init`, not an object.
	 *
	 * Plain function callbacks are what makes the hooks removable for other
	 * plugins, so this guards against sliding back to `array( $obj, 'init' )`.
	 */
	public function test_init_hooks_are_registered_as_functions() {
		$this->assertSame( 10, \has_action( 'init', 'Hum\init' ) );
		$this->assertSame( 15, \has_action( 'init', 'Hum\rewrite_rules' ) );
	}

	/**
	 * The textdomain is registered against the plugin directory.
	 *
	 * Since WP 6.7 `load_plugin_textdomain()` does not load anything itself, it
	 * only records a custom path on the textdomain registry, so that path is the
	 * thing worth asserting on.
	 */
	public function test_textdomain_path_is_the_plugin_directory() {
		global $wp_textdomain_registry;

		\Hum\init();

		$custom_paths = new \ReflectionProperty( $wp_textdomain_registry, 'custom_paths' );
		$custom_paths->setAccessible( true );
		$paths = $custom_paths->getValue( $wp_textdomain_registry );

		$this->assertArrayHasKey( 'hum', $paths );
		$this->assertSame( \basename( $this->plugin_dir ), \basename( $paths['hum'] ) );
	}

	/**
	 * The editor script is enqueued from the plugin root.
	 *
	 * The asset file is `include`d by path, so a wrong root does not just build a
	 * broken URL, it fails to read `build/index.asset.php` at all.
	 */
	public function test_editor_script_is_enqueued_from_the_plugin_root() {
		\Hum\enqueue_block_editor_script();

		$this->assertTrue( \wp_script_is( 'hum-editor-script', 'enqueued' ) );
		$this->assertStringEndsWith( '/' . \basename( $this->plugin_dir ) . '/build/index.js', \wp_scripts()->registered['hum-editor-script']->src );
	}

	/**
	 * A numeric path resolves to the post with that ID.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_numeric_path_resolves_to_post() {
		$post_id = $this->create_known_post();

		$this->assertSame( $post_id, \Hum\legacy_ftl_id( 0, (string) $post_id ) );
	}

	/**
	 * A base-32 path resolves to the post with the decoded ID.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_base32_path_resolves_to_post() {
		$post_id = $this->create_known_post();

		// base_convert( 'fba', 32, 10 ) === 15722.
		$this->assertSame( $post_id, \Hum\legacy_ftl_id( 0, 'fba' ) );
	}

	/**
	 * A path that decodes to a non-existent post leaves the ID untouched.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_unknown_post_leaves_id_untouched() {
		$this->assertSame( 0, \Hum\legacy_ftl_id( 0, 'ffffff' ) );
	}

	/**
	 * A very long hex path does not overflow base_convert().
	 *
	 * On PHP 8 the overflow to INF throws a ValueError, on PHP 7 it silently
	 * returned a float. Either way the decoder must bail early.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_long_path_does_not_overflow() {
		$this->assertSame( 0, \Hum\legacy_ftl_id( 0, str_repeat( 'f', 400 ) ) );
	}

	/**
	 * A 14 character hex string is one wider than PHP_INT_MAX in base 32.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_path_wider_than_int_max_is_rejected() {
		$this->assertSame( 0, \Hum\legacy_ftl_id( 0, str_repeat( 'f', 14 ) ) );
	}

	/**
	 * A 13 character hex string is still decoded, it fits into an integer.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_path_at_int_max_width_is_decoded() {
		$this->assertSame( 0, \Hum\legacy_ftl_id( 0, str_repeat( 'f', 13 ) ) );
	}

	/**
	 * A path without any hex characters is ignored.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_path_without_hex_characters_is_ignored() {
		$this->assertSame( 0, \Hum\legacy_ftl_id( 0, 'wxyz' ) );
	}

	/**
	 * The decoder is enabled by default.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_decoder_is_enabled_by_default() {
		$post_id = $this->create_known_post();

		$this->assertSame( $post_id, \Hum\legacy_ftl_id( 0, 'fba' ) );
	}

	/**
	 * The `hum_enable_legacy_ftl` filter switches the decoder off.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_filter_disables_decoder() {
		$this->create_known_post();
		add_filter( 'hum_enable_legacy_ftl', '__return_false' );

		$this->assertSame( 0, \Hum\legacy_ftl_id( 0, 'fba' ) );
		$this->assertSame( 0, \Hum\legacy_ftl_id( 0, (string) self::KNOWN_POST_ID ) );
	}

	/**
	 * An ordinary URL path is decoded as long as the decoder is enabled.
	 *
	 * `legacy_ftl_id()` strips every non-hex character before decoding, so
	 * `foo/bar` becomes `fba` and resolves to a post. That is the behaviour the
	 * filter exists for.
	 *
	 * @covers \Hum\legacy_ftl_id
	 */
	public function test_ordinary_path_is_decoded_when_enabled() {
		$post_id = $this->create_known_post();

		$this->assertSame( $post_id, \Hum\legacy_ftl_id( 0, 'foo/bar' ) );

		add_filter( 'hum_enable_legacy_ftl', '__return_false' );
		$this->assertSame( 0, \Hum\legacy_ftl_id( 0, 'foo/bar' ) );
	}
}
