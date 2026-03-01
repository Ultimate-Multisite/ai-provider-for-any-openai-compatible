<?php
/**
 * Plugin Name: OpenAI-Compatible Connector
 * Plugin URI: https://github.com/Ultimate-Multisite/openai-compatible-connector
 * Description: Registers an AI Client provider for any OpenAI-compatible endpoint (Ollama, LM Studio, OpenRouter, etc.).
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: Ultimate Multisite Community
 * Author URI: https://ultimatemultisite.com
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: openai-compatible-connector
 *
 * @package OpenAiCompatibleConnector
 */

declare(strict_types=1);

namespace OpenAiCompatibleConnector;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\Enums\ModalityEnum;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

/**
 * Provider class for an OpenAI-compatible endpoint.
 *
 * The base URL is read from plugin settings and stored in a static property
 * so that it is available to the SDK's static `baseUrl()` method.
 */
class OpenAiCompatProvider extends AbstractApiProvider {

	/**
	 * Configured endpoint URL. Set from options before registration.
	 *
	 * @var string
	 */
	public static string $endpointUrl = '';

	/**
	 * {@inheritDoc}
	 */
	protected static function baseUrl(): string {
		return rtrim( self::$endpointUrl, '/' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function createModel(
		ModelMetadata $modelMetadata,
		ProviderMetadata $providerMetadata
	): ModelInterface {
		$capabilities = $modelMetadata->getSupportedCapabilities();
		foreach ( $capabilities as $capability ) {
			if ( $capability->isTextGeneration() ) {
				return new OpenAiCompatModel( $modelMetadata, $providerMetadata );
			}
		}

		throw new RuntimeException(
			'Unsupported model capabilities: ' . esc_html( implode( ', ', $capabilities ) )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		return new ProviderMetadata(
			'openai-compat',
			'OpenAI Compatible',
			ProviderTypeEnum::server(),
			null,
			RequestAuthenticationMethod::apiKey()
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		return new ListModelsApiBasedProviderAvailability(
			static::modelMetadataDirectory()
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new OpenAiCompatModelDirectory();
	}
}

// ---------------------------------------------------------------------------
// Text Generation Model
// ---------------------------------------------------------------------------

/**
 * Text generation model that forwards requests to the configured endpoint
 * using the standard OpenAI chat/completions format.
 */
class OpenAiCompatModel extends AbstractOpenAiCompatibleTextGenerationModel {

	/**
	 * {@inheritDoc}
	 */
	protected function createRequest(
		HttpMethodEnum $method,
		string $path,
		array $headers = [],
		$data = null
	): Request {
		return new Request(
			$method,
			OpenAiCompatProvider::url( $path ),
			$headers,
			$data,
			$this->getRequestOptions()
		);
	}
}

// ---------------------------------------------------------------------------
// Model Metadata Directory
// ---------------------------------------------------------------------------

/**
 * Lists available models from the configured endpoint's /models resource.
 */
class OpenAiCompatModelDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory {

	/**
	 * {@inheritDoc}
	 */
	protected function createRequest(
		HttpMethodEnum $method,
		string $path,
		array $headers = [],
		$data = null
	): Request {
		return new Request(
			$method,
			OpenAiCompatProvider::url( $path ),
			$headers,
			$data
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-type ModelsResponseData array{data?: list<array{id: string, name?: string}>}
	 */
	protected function parseResponseToModelMetadataList( Response $response ): array {
		/** @var ModelsResponseData $responseData */
		$responseData = $response->getData();

		$modelsData = [];
		if ( isset( $responseData['data'] ) && is_array( $responseData['data'] ) ) {
			$modelsData = $responseData['data'];
		}

		// Fallback: some servers (e.g. Ollama < 0.5) return {models: [...]} instead of {data: [...]}.
		if ( empty( $modelsData ) && isset( $responseData['models'] ) && is_array( $responseData['models'] ) ) {
			$modelsData = $responseData['models'];
		}

		if ( empty( $modelsData ) ) {
			return [];
		}

		$capabilities = [
			CapabilityEnum::textGeneration(),
			CapabilityEnum::chatHistory(),
		];

		$options = [
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption( OptionEnum::frequencyPenalty() ),
			new SupportedOption( OptionEnum::presencePenalty() ),
			new SupportedOption( OptionEnum::functionDeclarations() ),
			new SupportedOption( OptionEnum::customOptions() ),
			// Don't restrict inputModalities/outputModalities to specific enum values.
			// The SDK caches ModelMetadata via PSR-16, which deserializes enum objects
			// into new instances that fail strict (===) identity checks against the
			// singletons used by the PromptBuilder's ModelRequirements. Passing null
			// (accept any value) avoids this SDK cache-deserialization bug.
			new SupportedOption( OptionEnum::inputModalities() ),
			new SupportedOption( OptionEnum::outputModalities() ),
			new SupportedOption( OptionEnum::outputMimeType(), [ 'text/plain', 'application/json' ] ),
			new SupportedOption( OptionEnum::outputSchema() ),
		];

		return array_values(
			array_map(
				static function ( array $modelData ) use ( $capabilities, $options ): ModelMetadata {
					$id   = $modelData['id'] ?? $modelData['name'] ?? 'unknown';
					$name = $modelData['name'] ?? $modelData['id'] ?? $id;

					return new ModelMetadata( $id, $name, $capabilities, $options );
				},
				$modelsData
			)
		);
	}
}

// ---------------------------------------------------------------------------
// Settings registration
// ---------------------------------------------------------------------------

/**
 * Registers the plugin settings for the REST API and admin.
 */
function register_settings(): void {
	register_setting(
		'openai_compat_connector',
		'openai_compat_endpoint_url',
		[
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
			'show_in_rest'      => true,
		]
	);

	register_setting(
		'openai_compat_connector',
		'openai_compat_api_key',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
			'show_in_rest'      => true,
		]
	);

	register_setting(
		'openai_compat_connector',
		'openai_compat_default_model',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
			'show_in_rest'      => true,
		]
	);
}
add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_settings' );

// ---------------------------------------------------------------------------
// Connectors page integration
// ---------------------------------------------------------------------------

/**
 * Enqueues the connector script module on the Connectors admin page.
 *
 * The `connectors-wp-admin_init` action fires only on the Settings > Connectors
 * page, so the module is loaded only where it is needed.
 */
function enqueue_connector_module(): void {
	wp_register_script_module(
		'openai-compat-connector',
		plugins_url( 'build/connector.js', __FILE__ ),
		[
			[
				'id'     => '@wordpress/connectors',
				'import' => 'static',
			],
		],
		'1.0.0'
	);
	wp_enqueue_script_module( 'openai-compat-connector' );
}
add_action( 'connectors-wp-admin_init', __NAMESPACE__ . '\\enqueue_connector_module' );

// ---------------------------------------------------------------------------
// REST endpoint: list models from the configured endpoint
// ---------------------------------------------------------------------------

/**
 * Registers a REST route that proxies /models from the configured endpoint.
 *
 * This avoids browser CORS issues by fetching server-side.
 */
function register_models_route(): void {
	register_rest_route(
		'openai-compat/v1',
		'/models',
		[
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\rest_list_models',
			'permission_callback' => static function () {
				return current_user_can( 'manage_options' );
			},
		]
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_models_route' );

/**
 * Fetches models from the configured endpoint and returns them.
 *
 * @return \WP_REST_Response|\WP_Error
 */
function rest_list_models() {
	$endpoint_url = get_option( 'openai_compat_endpoint_url', '' );

	if ( empty( $endpoint_url ) ) {
		return new \WP_Error(
			'no_endpoint',
			__( 'No endpoint URL configured.', 'openai-compatible-connector' ),
			[ 'status' => 400 ]
		);
	}

	$models_url = rtrim( $endpoint_url, '/' ) . '/models';
	$api_key    = get_option( 'openai_compat_api_key', '' );

	$headers = [
		'Accept' => 'application/json',
	];

	if ( ! empty( $api_key ) ) {
		$headers['Authorization'] = 'Bearer ' . $api_key;
	}

	$response = wp_remote_get(
		$models_url,
		[
			'headers' => $headers,
			'timeout' => 15,
		]
	);

	if ( is_wp_error( $response ) ) {
		return new \WP_Error(
			'request_failed',
			$response->get_error_message(),
			[ 'status' => 502 ]
		);
	}

	$code = wp_remote_retrieve_response_code( $response );

	if ( $code < 200 || $code >= 300 ) {
		return new \WP_Error(
			'upstream_error',
			sprintf(
				/* translators: %d: HTTP status code */
				__( 'Upstream returned HTTP %d.', 'openai-compatible-connector' ),
				$code
			),
			[ 'status' => 502 ]
		);
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) ) {
		return new \WP_Error(
			'invalid_response',
			__( 'Could not parse models response.', 'openai-compatible-connector' ),
			[ 'status' => 502 ]
		);
	}

	// OpenAI format: { data: [...] }  Ollama format: { models: [...] }
	$models_data = [];
	if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
		$models_data = $body['data'];
	} elseif ( isset( $body['models'] ) && is_array( $body['models'] ) ) {
		$models_data = $body['models'];
	}

	$models = array_map(
		static function ( array $model ): array {
			$id   = $model['id'] ?? $model['name'] ?? 'unknown';
			$name = $model['name'] ?? $model['id'] ?? $id;
			return [
				'id'   => $id,
				'name' => $name,
			];
		},
		$models_data
	);

	// Sort by name.
	usort(
		$models,
		static function ( array $a, array $b ): int {
			return strcasecmp( $a['name'], $b['name'] );
		}
	);

	return rest_ensure_response( $models );
}

// ---------------------------------------------------------------------------
// Increase HTTP timeout for inference requests
// ---------------------------------------------------------------------------

/**
 * Increases the HTTP timeout for requests to the configured endpoint.
 *
 * Local/self-hosted LLMs can take over 30 seconds to respond, especially
 * on CPU-only hardware. The default WordPress timeout of 30s is too short.
 *
 * @param array  $parsed_args HTTP request arguments.
 * @param string $url         Request URL.
 * @return array Modified arguments.
 */
function increase_timeout( array $parsed_args, string $url ): array {
	$endpoint_url = get_option( 'openai_compat_endpoint_url', '' );
	if ( empty( $endpoint_url ) ) {
		return $parsed_args;
	}

	$endpoint_host = wp_parse_url( $endpoint_url, PHP_URL_HOST );
	$request_host  = wp_parse_url( $url, PHP_URL_HOST );

	if ( $endpoint_host && $request_host && $endpoint_host === $request_host ) {
		$parsed_args['timeout'] = max( (float) ( $parsed_args['timeout'] ?? 30 ), 120.0 );
	}

	return $parsed_args;
}
add_filter( 'http_request_args', __NAMESPACE__ . '\\increase_timeout', 10, 2 );

// ---------------------------------------------------------------------------
// Allow non-standard ports through wp_safe_remote_request
// ---------------------------------------------------------------------------

/**
 * Adds the configured endpoint port to the list of allowed HTTP ports.
 *
 * WordPress's wp_safe_remote_request() only allows ports 80, 443, and 8080
 * by default. Self-hosted inference servers typically run on other ports.
 *
 * @param int[] $ports Allowed ports.
 * @return int[] Modified allowed ports.
 */
function allow_endpoint_port( array $ports ): array {
	$endpoint_url = get_option( 'openai_compat_endpoint_url', '' );
	if ( empty( $endpoint_url ) ) {
		return $ports;
	}

	$parsed = wp_parse_url( $endpoint_url );
	if ( ! empty( $parsed['port'] ) ) {
		$ports[] = (int) $parsed['port'];
	}

	return array_unique( $ports );
}
add_filter( 'http_allowed_safe_ports', __NAMESPACE__ . '\\allow_endpoint_port' );

// ---------------------------------------------------------------------------
// Provider registration
// ---------------------------------------------------------------------------

/**
 * Registers the provider with the AI Client on init.
 *
 * Runs at priority 5 so the provider is available before most plugins act on
 * `init` (default priority 10).
 */
function register_provider(): void {
	if ( ! class_exists( AiClient::class ) ) {
		return;
	}

	$endpoint_url = get_option( 'openai_compat_endpoint_url', '' );
	if ( empty( $endpoint_url ) ) {
		return;
	}

	// Set the base URL before any SDK method can call baseUrl().
	OpenAiCompatProvider::$endpointUrl = $endpoint_url;

	$registry = AiClient::defaultRegistry();

	if ( $registry->hasProvider( OpenAiCompatProvider::class ) ) {
		return;
	}

	$registry->registerProvider( OpenAiCompatProvider::class );

	// Inject the API key (or a placeholder for servers that don't need one).
	$api_key = get_option( 'openai_compat_api_key', '' );
	if ( empty( $api_key ) ) {
		$api_key = 'no-key';
	}

	$registry->setProviderRequestAuthentication(
		OpenAiCompatProvider::class,
		new ApiKeyRequestAuthentication( $api_key )
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_provider', 5 );

/**
 * Returns the configured default model ID, or empty string if none set.
 *
 * @return string
 */
function get_default_model(): string {
	return (string) get_option( 'openai_compat_default_model', '' );
}
