<?php
/**
 * Plugin Name: AI Provider for Any OpenAI-Compatible Endpoint
 * Plugin URI: https://github.com/Ultimate-Multisite/ai-provider-for-any-openai-compatible
 * Description: Registers an AI Client provider for Ollama, LM Studio, or any endpoint using the OpenAI-compatible API format. Not affiliated with OpenAI.
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: Ultimate Multisite Community
 * Author URI: https://developer.developer.developer
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: ai-provider-for-any-openai-compatible
 *
 * @package AiProviderOpenaiCompatible
 */

declare(strict_types=1);

namespace OpenAiCompatibleConnector;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Absolute path to this plugin's main file.
 *
 * @var string
 */
define( 'AI_PROVIDER_OPENAI_COMPAT_FILE', __FILE__ );

// ---------------------------------------------------------------------------
// Load classes and function files.
// ---------------------------------------------------------------------------

require_once __DIR__ . '/inc/class-provider.php';
require_once __DIR__ . '/inc/class-model.php';
require_once __DIR__ . '/inc/class-model-directory.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/rest-api.php';
require_once __DIR__ . '/inc/http-filters.php';
require_once __DIR__ . '/inc/provider-registration.php';

// ---------------------------------------------------------------------------
// Hook registrations.
// ---------------------------------------------------------------------------

// Settings.
add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_settings' );

// Connectors page.
add_action( 'connectors-wp-admin_init', __NAMESPACE__ . '\\enqueue_connector_module' );

// REST API.
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_models_route' );

// HTTP filters.
add_filter( 'http_request_args', __NAMESPACE__ . '\\increase_timeout', 10, 2 );
add_filter( 'http_allowed_safe_ports', __NAMESPACE__ . '\\allow_endpoint_port' );
add_filter( 'http_request_host_is_external', __NAMESPACE__ . '\\allow_endpoint_host', 10, 2 );

// Provider registration.
add_action( 'init', __NAMESPACE__ . '\\register_provider', 5 );
