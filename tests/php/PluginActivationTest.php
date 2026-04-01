<?php
/**
 * Test that the plugin activates without fatal errors.
 *
 * This is the most critical test — it catches namespace declaration errors,
 * missing files, and syntax issues that cause fatal errors on activation.
 *
 * @package UltimateAiConnectorCompatibleEndpoints
 * @license GPL-2.0-or-later
 */

namespace UltimateAiConnectorCompatibleEndpoints\Tests;

use WP_UnitTestCase;

/**
 * Plugin activation tests.
 */
class PluginActivationTest extends WP_UnitTestCase {

	/**
	 * Test that the plugin file was loaded without fatal errors.
	 *
	 * If the namespace declaration is misplaced (the bug this test guards
	 * against), PHP will fatal before reaching this test. The test passing
	 * at all proves the plugin loaded successfully.
	 */
	public function test_plugin_loaded_without_fatal_errors() {
		$this->assertTrue(
			function_exists( 'UltimateAiConnectorCompatibleEndpoints\\register_settings' ),
			'Plugin function register_settings() should be defined after loading.'
		);
	}

	/**
	 * Test that all inc/ files have correct namespace declarations.
	 *
	 * Verifies that the namespace declaration appears before any executable
	 * code (other than the opening PHP tag and docblock) in each file.
	 */
	public function test_inc_files_have_namespace_before_code() {
		$inc_dir = dirname( __DIR__, 2 ) . '/inc/';
		$files   = glob( $inc_dir . '*.php' );

		$this->assertNotEmpty( $files, 'Should find PHP files in inc/ directory.' );

		foreach ( $files as $file ) {
			$contents = file_get_contents( $file );
			$basename = basename( $file );

			// Find the position of the namespace declaration.
			$namespace_pos = strpos( $contents, 'namespace ' );
			$this->assertNotFalse(
				$namespace_pos,
				"File {$basename} should contain a namespace declaration."
			);

			// Find the position of the ABSPATH check.
			$abspath_pos = strpos( $contents, "defined( 'ABSPATH' )" );
			if ( false === $abspath_pos ) {
				$abspath_pos = strpos( $contents, "defined('ABSPATH')" );
			}

			if ( false !== $abspath_pos ) {
				$this->assertLessThan(
					$abspath_pos,
					$namespace_pos,
					"File {$basename}: namespace declaration must appear before ABSPATH check."
				);
			}
		}
	}

	/**
	 * Test that all expected functions are defined.
	 */
	public function test_expected_functions_exist() {
		$functions = [
			'UltimateAiConnectorCompatibleEndpoints\\register_settings',
			'UltimateAiConnectorCompatibleEndpoints\\enqueue_connector_module',
			'UltimateAiConnectorCompatibleEndpoints\\register_models_route',
			'UltimateAiConnectorCompatibleEndpoints\\increase_timeout',
			'UltimateAiConnectorCompatibleEndpoints\\allow_endpoint_port',
			'UltimateAiConnectorCompatibleEndpoints\\allow_endpoint_host',
			'UltimateAiConnectorCompatibleEndpoints\\register_provider',
		];

		foreach ( $functions as $function ) {
			$this->assertTrue(
				function_exists( $function ),
				"Function {$function} should be defined."
			);
		}
	}

	/**
	 * Test that all expected classes are defined.
	 */
	public function test_expected_classes_exist() {
		$classes = [
			'UltimateAiConnectorCompatibleEndpoints\\CompatibleEndpointProvider',
			'UltimateAiConnectorCompatibleEndpoints\\CompatibleEndpointModel',
			'UltimateAiConnectorCompatibleEndpoints\\CompatibleEndpointModelDirectory',
		];

		foreach ( $classes as $class ) {
			$this->assertTrue(
				class_exists( $class ),
				"Class {$class} should be defined."
			);
		}
	}
}
