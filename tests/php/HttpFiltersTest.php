<?php
/**
 * Test HTTP filter functions.
 *
 * @package UltimateAiConnectorCompatibleEndpoints
 * @license GPL-2.0-or-later
 */

namespace UltimateAiConnectorCompatibleEndpoints\Tests;

use WP_UnitTestCase;

/**
 * HTTP filters tests.
 */
class HttpFiltersTest extends WP_UnitTestCase {

	/**
	 * Clean up after each test.
	 */
	public function tear_down(): void {
		parent::tear_down();
		delete_option( 'ultimate_ai_connector_endpoint_url' );
		delete_option( 'ultimate_ai_connector_timeout' );
	}

	/**
	 * Test timeout is increased for matching endpoint host.
	 */
	public function test_increase_timeout_for_endpoint_host() {
		update_option( 'ultimate_ai_connector_endpoint_url', 'http://localhost:11434/v1' );
		update_option( 'ultimate_ai_connector_timeout', 300 );

		$args   = [ 'timeout' => 30 ];
		$result = \UltimateAiConnectorCompatibleEndpoints\increase_timeout(
			$args,
			'http://localhost:11434/v1/chat/completions'
		);

		$this->assertSame( 300.0, $result['timeout'] );
	}

	/**
	 * Test timeout is not changed for non-matching host.
	 */
	public function test_timeout_unchanged_for_other_hosts() {
		update_option( 'ultimate_ai_connector_endpoint_url', 'http://localhost:11434/v1' );

		$args   = [ 'timeout' => 30 ];
		$result = \UltimateAiConnectorCompatibleEndpoints\increase_timeout(
			$args,
			'https://api.openai.com/v1/chat/completions'
		);

		$this->assertSame( 30, $result['timeout'] );
	}

	/**
	 * Test timeout is not changed when no endpoint configured.
	 */
	public function test_timeout_unchanged_when_no_endpoint() {
		$args   = [ 'timeout' => 30 ];
		$result = \UltimateAiConnectorCompatibleEndpoints\increase_timeout(
			$args,
			'http://localhost:11434/v1/chat/completions'
		);

		$this->assertSame( 30, $result['timeout'] );
	}

	/**
	 * Test non-standard port is added to allowed ports.
	 */
	public function test_allow_endpoint_port() {
		update_option( 'ultimate_ai_connector_endpoint_url', 'http://localhost:11434/v1' );

		$ports  = [ 80, 443, 8080 ];
		$result = \UltimateAiConnectorCompatibleEndpoints\allow_endpoint_port( $ports );

		$this->assertContains( 11434, $result );
	}

	/**
	 * Test ports unchanged when no endpoint configured.
	 */
	public function test_ports_unchanged_when_no_endpoint() {
		$ports  = [ 80, 443, 8080 ];
		$result = \UltimateAiConnectorCompatibleEndpoints\allow_endpoint_port( $ports );

		$this->assertSame( $ports, $result );
	}

	/**
	 * Test localhost is allowed when endpoint is localhost.
	 */
	public function test_allow_localhost_endpoint() {
		update_option( 'ultimate_ai_connector_endpoint_url', 'http://localhost:11434/v1' );

		$result = \UltimateAiConnectorCompatibleEndpoints\allow_endpoint_host( false, 'localhost' );

		$this->assertTrue( $result );
	}

	/**
	 * Test non-matching host is not allowed.
	 */
	public function test_non_matching_host_not_allowed() {
		update_option( 'ultimate_ai_connector_endpoint_url', 'http://localhost:11434/v1' );

		$result = \UltimateAiConnectorCompatibleEndpoints\allow_endpoint_host( false, 'evil.com' );

		$this->assertFalse( $result );
	}

	/**
	 * Test already-external host stays external.
	 */
	public function test_already_external_stays_external() {
		$result = \UltimateAiConnectorCompatibleEndpoints\allow_endpoint_host( true, 'example.com' );

		$this->assertTrue( $result );
	}
}
