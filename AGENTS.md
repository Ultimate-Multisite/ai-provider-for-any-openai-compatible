# AGENTS.md — Ultimate AI Connector for Compatible Endpoints

WordPress plugin that registers an AI Client provider for Ollama, LM Studio, or any AI endpoint using the standard chat completions API format.

## Build Commands

```bash
# Install dependencies
npm install

# Development build with watch
npm run start

# Production build
npm run build
```

Output: `build/connector.js` (ES module for WordPress Script Modules API).

## Testing

### PHPUnit (unit tests)

```bash
# Via wp-env (recommended):
npm run test:php
npm run test:php:testdox

# Via standalone install (CI):
composer install
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 6.9 true
vendor/bin/phpunit
```

Test files: `tests/php/*Test.php`. Bootstrap: `tests/bootstrap.php`.

### Cypress (E2E tests)

```bash
# Start wp-env first:
npm run wp-env:start

# Run headless:
npm run test:e2e

# Open interactive:
npm run test:e2e:open
```

Test files: `tests/e2e/*.cy.js`. Config: `cypress.config.js`.

### wp-env

```bash
npm run wp-env:start   # Start WordPress 6.9 + plugin
npm run wp-env:stop    # Stop
npm run wp-env:clean   # Reset
```

Config: `.wp-env.json`. Override ports: `.wp-env.override.json` (gitignored).

### Manual testing

1. Ensure WordPress 6.9+ with AI Client SDK is active
2. Activate the plugin
3. Navigate to Settings → Connectors
4. Configure an endpoint (e.g., `http://localhost:11434/v1` for Ollama)

## Linting

No linter is configured. Follow WordPress Coding Standards manually.

```bash
# If adding PHP linting later:
composer require --dev wp-coding-standards/wpcs
./vendor/bin/phpcs --standard=WordPress inc/ *.php

# If adding JS linting later:
npm install --save-dev @wordpress/eslint-plugin
npx eslint src/
```

## Code Style

### PHP

- **Strict types**: Every PHP file must declare `declare(strict_types=1);`
- **Namespace**: `UltimateAiConnectorCompatibleEndpoints` for all classes and functions
- **File headers**: Include `@package UltimateAiConnectorCompatibleEndpoints` in docblocks
- **WordPress standards**: Use WordPress coding style (tabs, Yoda conditions, etc.)
- **Type hints**: Use PHP 7.4+ type declarations for parameters and return types
- **Escaping**: Always escape output (`esc_html()`, `esc_url()`, `esc_attr()`)
- **Sanitization**: Sanitize all input (`sanitize_text_field()`, `absint()`, etc.)
- **Nonce verification**: Required for form submissions and AJAX handlers
- **Capability checks**: Use `current_user_can()` before privileged operations

```php
<?php
declare(strict_types=1);

namespace UltimateAiConnectorCompatibleEndpoints;

/**
 * Function description.
 *
 * @param string $param Description.
 * @return string
 */
function example_function( string $param ): string {
    if ( ! current_user_can( 'manage_options' ) ) {
        return '';
    }
    return sanitize_text_field( $param );
}
```

### JavaScript/JSX

- **React**: Use `wp.element` (createElement, useState, useEffect, etc.)
- **Components**: Use `wp.components` (Button, TextControl, SelectControl, etc.)
- **i18n**: Use `wp.i18n` for translations (`__()`, `_x()`)
- **API**: Use `wp.apiFetch` for REST API calls
- **No JSX runtime**: Custom pragma `createElement` (see webpack.config.js)
- **Imports**: Import from `@wordpress/connectors` for connector registration

```jsx
const { createElement, useState } = wp.element;
const { Button, TextControl } = wp.components;
const { __ } = wp.i18n;

function MyComponent() {
    const [value, setValue] = useState('');
    return (
        <TextControl
            label={__('Label')}
            value={value}
            onChange={setValue}
        />
    );
}
```

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| PHP functions | `snake_case` | `register_settings()` |
| PHP classes | `PascalCase` | `CompatibleEndpointProvider` |
| PHP constants | `UPPER_SNAKE_CASE` | `ULTIMATE_AI_CONNECTOR_COMPATIBLE_ENDPOINTS_FILE` |
| JS functions | `camelCase` | `fetchModels()` |
| JS components | `PascalCase` | `CompatibleEndpointConnectorCard` |
| CSS classes | `kebab-case` | `connector-item--ultimate-ai-connector-compatible-endpoints` |
| Options | `snake_case` with prefix | `ultimate_ai_connector_endpoint_url` |
| REST routes | `kebab-case` | `/ultimate-ai-connector-compatible-endpoints/v1/models` |

### File Organization

```
├── ultimate-ai-connector-compatible-endpoints.php  # Main plugin file, hooks
├── inc/
│   ├── class-provider.php          # AbstractApiProvider implementation
│   ├── class-model.php             # Text generation model
│   ├── class-model-directory.php   # Model listing from /models endpoint
│   ├── settings.php                # register_setting() calls
│   ├── admin.php                   # Script module enqueue
│   ├── rest-api.php                # REST endpoint for model proxy
│   ├── http-filters.php            # Timeout, port, host filters
│   └── provider-registration.php   # AiClient registry integration
├── src/
│   └── index.jsx                   # Connectors page UI component
├── build/
│   └── connector.js                # Compiled ES module (gitignored: no)
├── tests/
│   ├── bootstrap.php               # PHPUnit bootstrap
│   ├── php/                        # PHPUnit test cases
│   │   ├── PluginActivationTest.php
│   │   ├── SettingsTest.php
│   │   └── HttpFiltersTest.php
│   └── e2e/                        # Cypress E2E tests
│       ├── plugin-activation.cy.js
│       └── support/
│           └── e2e.js
├── bin/
│   └── install-wp-tests.sh         # WP test suite installer (CI)
└── .github/
    └── workflows/
        ├── tests.yml               # PHPUnit + PHP syntax CI
        └── e2e.yml                 # Cypress E2E CI
```

### Error Handling

- **PHP**: Return `WP_Error` from REST callbacks; throw `RuntimeException` for SDK errors
- **JS**: Use try/catch with `apiFetch`; show errors via component state, not alerts

```php
if ( is_wp_error( $response ) ) {
    return new \WP_Error(
        'request_failed',
        $response->get_error_message(),
        [ 'status' => 502 ]
    );
}
```

```jsx
try {
    const result = await apiFetch({ path: '/wp/v2/settings', method: 'POST', data });
} catch (error) {
    setSaveError(error instanceof Error ? error.message : __('Failed to save.'));
}
```

### WordPress AI Client SDK

This plugin extends the WordPress AI Client SDK. Key classes:

- `AbstractApiProvider` — Base class for API-based providers
- `AbstractOpenAiCompatibleTextGenerationModel` — Standard chat completions format
- `AbstractOpenAiCompatibleModelMetadataDirectory` — Model listing from /models
- `ModelMetadata`, `ProviderMetadata` — DTOs for provider/model info
- `CapabilityEnum`, `OptionEnum` — Supported capabilities and options

Provider registration pattern:
```php
$registry = AiClient::defaultRegistry();
$registry->registerProvider( CompatibleEndpointProvider::class );
$registry->setProviderRequestAuthentication(
    CompatibleEndpointProvider::class,
    new ApiKeyRequestAuthentication( $api_key )
);
```

### HTTP Considerations

The plugin adds filters to support self-hosted inference servers:

1. **Timeout**: Extended to 360s (configurable) for slow hardware
2. **Ports**: Non-standard ports (11434, etc.) added to allowed list
3. **Localhost**: Private IPs/localhost marked as "external" for wp_safe_remote_request

### Settings

All settings use the `ultimate_ai_connector_` prefix:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `ultimate_ai_connector_endpoint_url` | string | `''` | Base URL for API |
| `ultimate_ai_connector_api_key` | string | `''` | Bearer token (optional) |
| `ultimate_ai_connector_default_model` | string | `''` | Model ID to use |
| `ultimate_ai_connector_timeout` | integer | `360` | Request timeout in seconds |

### Commit Messages

Use conventional commits:

- `feat:` — New feature
- `fix:` — Bug fix
- `docs:` — Documentation only
- `refactor:` — Code change that neither fixes a bug nor adds a feature
- `chore:` — Maintenance tasks

Example: `feat: add temperature slider to connector settings`
