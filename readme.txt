=== AI Provider for Any OpenAI-Compatible Endpoint ===
Contributors: developer-starter
Tags: ai, ollama, llm, connectors, local-ai
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connects the WordPress AI Client to Ollama, LM Studio, or any endpoint using the OpenAI-compatible API format.

== Description ==

This plugin extends the WordPress AI Client to support **any service or server that speaks the OpenAI-compatible API format**. It is not affiliated with or endorsed by OpenAI.

**Supported services include:**

* **Ollama** - Run open-source models (Llama, Mistral, Gemma, etc.) locally on your own hardware.
* **LM Studio** - Desktop application for local LLM inference with a one-click server.
* **OpenRouter** - Unified API providing access to 100+ models from multiple providers.
* **Claude Max proxy** - Use Claude with token-based billing through an OpenAI-compatible proxy.
* **Any OpenAI-compatible server** - vLLM, text-generation-webui, LocalAI, and more.

**Requirements by WordPress version:**

* **WordPress 7.0+** - The AI Client SDK is included in core. This plugin works on its own without any additional dependencies.
* **WordPress 6.9** - The AI Client SDK is not in core. You must also install the [AI Experiments](https://wordpress.org/plugins/ai/) plugin, which bundles the SDK.

**Why it matters:**

Other AI-powered plugins that use the WordPress AI Client (such as AI Experiments and StifLi Flex MCP) can automatically discover and use any model you connect through this plugin. Configure your endpoint once and every AI feature on your site can use it.

**How it works:**

1. Install and activate the plugin.
2. Go to **Settings > Connectors** and configure the "AI Provider for OpenAI-Compatible" connector with your endpoint URL (e.g. `http://localhost:11434/v1` for Ollama).
3. Optionally provide an API key for services that require authentication.
4. The plugin registers a provider with the WordPress AI Client and dynamically discovers all available models from your endpoint.

The plugin also handles practical concerns like extended HTTP timeouts for slow local inference and non-standard port support.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-any-openai-compatible/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. **WordPress 6.9 only:** Make sure the [AI Experiments](https://wordpress.org/plugins/ai/) plugin is installed and active. It provides the AI Client SDK that this plugin requires.
4. Go to **Settings > Connectors** and configure the "AI Provider for OpenAI-Compatible" connector.
5. Optionally enter an API key if your endpoint requires one.

== Frequently Asked Questions ==

= What is an OpenAI-compatible endpoint? =

Many AI inference servers and services implement the same API format that OpenAI uses (the `/v1/chat/completions` and `/v1/models` endpoints). This plugin works with any server that follows that format.

= Do I need an API key? =

It depends on your endpoint. Local servers like Ollama and LM Studio typically do not require a key. Cloud services like OpenRouter require one. Leave the API Key field blank for servers that do not need authentication.

= What models will be available? =

The plugin automatically queries your endpoint's `/models` resource and registers every model it finds. Whatever models your server offers will appear in the WordPress AI Client.

= Does this work with WordPress 6.9? =

Yes, but you must also install the [AI Experiments](https://wordpress.org/plugins/ai/) plugin, which bundles the AI Client SDK. On WordPress 7.0 and later, the SDK is built into core and no additional plugin is needed.

= Does this work on WordPress 7.0 without the AI Experiments plugin? =

Yes. WordPress 7.0 ships the AI Client SDK in core, so this connector plugin works on its own. You only need the AI Experiments plugin if you want the experimental AI features it provides (excerpt generation, summarization, etc.).

== Changelog ==

= 1.0.0 =

* Initial release.
* Provider registration with the WordPress AI Client.
* Settings page for endpoint URL and optional API key.
* Dynamic model discovery from any OpenAI-compatible endpoint.
* Extended HTTP timeout support for local inference servers.
* Non-standard port support (e.g. Ollama on 11434, LM Studio on 1234).
