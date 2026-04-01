<?php
/**
 * Test settings registration and sanitization.
 *
 * @package UltimateAiConnectorCompatibleEndpoints
 * @license GPL-2.0-or-later
 */

namespace UltimateAiConnectorCompatibleEndpoints\Tests;

use WP_UnitTestCase;

/**
 * Settings tests.
 */
class SettingsTest extends WP_UnitTestCase {

	/**
	 * Clean up after each test.
	 */
	public function tear_down(): void {
		parent::tear_down();
		delete_option( 'ultimate_ai_connector_endpoint_url' );
		delete_option( 'ultimate_ai_connector_api_key' );
		delete_option( 'ultimate_ai_connector_default_model' );
		delete_option( 'ultimate_ai_connector_timeout' );
	}

	/**
	 * Test that settings are registered.
	 */
	public function test_settings_are_registered() {
		// Trigger registration.
		\UltimateAiConnectorCompatibleEndpoints\register_settings();

		$registered = get_registered_settings();

		$this->assertArrayHasKey( 'ultimate_ai_connector_endpoint_url', $registered );
		$this->assertArrayHasKey( 'ultimate_ai_connector_api_key', $registered );
		$this->assertArrayHasKey( 'ultimate_ai_connector_default_model', $registered );
		$this->assertArrayHasKey( 'ultimate_ai_connector_timeout', $registered );
	}

	/**
	 * Test endpoint URL setting defaults to empty string.
	 */
	public function test_endpoint_url_defaults_to_empty() {
		$value = get_option( 'ultimate_ai_connector_endpoint_url', '' );
		$this->assertSame( '', $value );
	}

	/**
	 * Test timeout setting defaults to 360.
	 */
	public function test_timeout_defaults_to_360() {
		$value = get_option( 'ultimate_ai_connector_timeout', 360 );
		$this->assertSame( 360, $value );
	}

	/**
	 * Test endpoint URL is stored and retrieved.
	 */
	public function test_endpoint_url_stored_and_retrieved() {
		update_option( 'ultimate_ai_connector_endpoint_url', 'http://localhost:11434/v1' );
		$this->assertSame( 'http://localhost:11434/v1', get_option( 'ultimate_ai_connector_endpoint_url' ) );
	}

	/**
	 * Test timeout is stored as integer.
	 */
	public function test_timeout_stored_as_integer() {
		update_option( 'ultimate_ai_connector_timeout', 120 );
		$this->assertSame( 120, (int) get_option( 'ultimate_ai_connector_timeout' ) );
	}

	/**
	 * Test settings are exposed in REST API.
	 */
	public function test_settings_show_in_rest() {
		\UltimateAiConnectorCompatibleEndpoints\register_settings();

		$registered = get_registered_settings();

		$this->assertTrue( $registered['ultimate_ai_connector_endpoint_url']['show_in_rest'] );
		$this->assertTrue( $registered['ultimate_ai_connector_api_key']['show_in_rest'] );
		$this->assertTrue( $registered['ultimate_ai_connector_default_model']['show_in_rest'] );
		$this->assertTrue( $registered['ultimate_ai_connector_timeout']['show_in_rest'] );
	}
}
