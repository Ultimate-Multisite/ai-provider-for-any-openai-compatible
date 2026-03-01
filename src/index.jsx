/**
 * OpenAI Compatible Connector — Connectors page integration.
 *
 * Registers a card on Settings > Connectors that lets users configure
 * the Endpoint URL, API Key, and Default Model from one place.
 *
 * @package OpenAiCompatibleConnector
 */

import {
	__experimentalRegisterConnector as registerConnector,
	__experimentalConnectorItem as ConnectorItem,
} from '@wordpress/connectors';

const { createElement, useState, useEffect, useCallback, useRef } = wp.element;
const {
	Button,
	TextControl,
	SelectControl,
	Spinner,
	__experimentalHStack: HStack,
	__experimentalVStack: VStack,
} = wp.components;
const { __ } = wp.i18n;
const apiFetch = wp.apiFetch;

/**
 * Server / network icon used as the connector logo.
 */
function Logo() {
	return (
		<svg
			width={ 40 }
			height={ 40 }
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<path
				d="M4 1h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2zm0 14h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2z"
				stroke="currentColor"
				strokeWidth="2"
				strokeLinecap="round"
				strokeLinejoin="round"
				fill="none"
			/>
			<circle cx={ 6 } cy={ 5 } r={ 1 } fill="currentColor" />
			<circle cx={ 6 } cy={ 19 } r={ 1 } fill="currentColor" />
			<line
				x1={ 12 }
				y1={ 9 }
				x2={ 12 }
				y2={ 15 }
				stroke="currentColor"
				strokeWidth="2"
				strokeLinecap="round"
			/>
		</svg>
	);
}

/**
 * Green "Connected" badge — matches the built-in connectors styling.
 */
function ConnectedBadge() {
	return (
		<span
			style={ {
				color: '#345b37',
				backgroundColor: '#eff8f0',
				padding: '4px 12px',
				borderRadius: '2px',
				fontSize: '13px',
				fontWeight: 500,
				whiteSpace: 'nowrap',
			} }
		>
			{ __( 'Connected' ) }
		</span>
	);
}

/**
 * Main connector card component rendered on the Connectors page.
 */
function OpenAiCompatConnectorCard( { slug, label, description } ) {
	const [ endpointUrl, setEndpointUrl ] = useState( '' );
	const [ apiKey, setApiKey ] = useState( '' );
	const [ defaultModel, setDefaultModel ] = useState( '' );
	const [ models, setModels ] = useState( [] );
	const [ isLoadingModels, setIsLoadingModels ] = useState( false );
	const [ isExpanded, setIsExpanded ] = useState( false );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ saveError, setSaveError ] = useState( null );
	const modelsFetchedForUrl = useRef( '' );

	const isConnected = endpointUrl !== '';

	const fetchSettings = useCallback( async () => {
		try {
			const settings = await apiFetch( {
				path: '/wp/v2/settings?_fields=openai_compat_endpoint_url,openai_compat_api_key,openai_compat_default_model',
			} );
			setEndpointUrl( settings.openai_compat_endpoint_url || '' );
			setApiKey( settings.openai_compat_api_key || '' );
			setDefaultModel( settings.openai_compat_default_model || '' );
		} catch {
			// Silently fail — fields will stay empty.
		} finally {
			setIsLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchSettings();
	}, [ fetchSettings ] );

	/**
	 * Fetch available models from the proxy REST endpoint.
	 */
	const fetchModels = useCallback( async () => {
		if ( ! endpointUrl ) {
			setModels( [] );
			return;
		}
		// Don't re-fetch if we already fetched for this URL.
		if ( modelsFetchedForUrl.current === endpointUrl ) {
			return;
		}
		setIsLoadingModels( true );
		try {
			const result = await apiFetch( {
				path: '/openai-compat/v1/models',
			} );
			setModels( Array.isArray( result ) ? result : [] );
			modelsFetchedForUrl.current = endpointUrl;
		} catch {
			setModels( [] );
		} finally {
			setIsLoadingModels( false );
		}
	}, [ endpointUrl ] );

	// Fetch models when the card is expanded and we have an endpoint URL.
	useEffect( () => {
		if ( isExpanded && endpointUrl ) {
			fetchModels();
		}
	}, [ isExpanded, endpointUrl, fetchModels ] );

	const handleSave = async () => {
		setSaveError( null );
		setIsSaving( true );
		try {
			const result = await apiFetch( {
				method: 'POST',
				path: '/wp/v2/settings',
				data: {
					openai_compat_endpoint_url: endpointUrl,
					openai_compat_api_key: apiKey,
					openai_compat_default_model: defaultModel,
				},
			} );
			setEndpointUrl( result.openai_compat_endpoint_url || '' );
			setApiKey( result.openai_compat_api_key || '' );
			setDefaultModel( result.openai_compat_default_model || '' );
			setIsExpanded( false );
		} catch ( error ) {
			setSaveError(
				error instanceof Error
					? error.message
					: __( 'Failed to save settings.' )
			);
		} finally {
			setIsSaving( false );
		}
	};

	const handleRemove = async () => {
		setSaveError( null );
		setIsSaving( true );
		try {
			await apiFetch( {
				method: 'POST',
				path: '/wp/v2/settings',
				data: {
					openai_compat_endpoint_url: '',
					openai_compat_api_key: '',
					openai_compat_default_model: '',
				},
			} );
			setEndpointUrl( '' );
			setApiKey( '' );
			setDefaultModel( '' );
			setModels( [] );
			modelsFetchedForUrl.current = '';
			setIsExpanded( false );
		} catch ( error ) {
			setSaveError(
				error instanceof Error
					? error.message
					: __( 'Failed to remove settings.' )
			);
		} finally {
			setIsSaving( false );
		}
	};

	const handleButtonClick = () => {
		setIsExpanded( ! isExpanded );
		setSaveError( null );
	};

	const getButtonLabel = () => {
		if ( isLoading ) {
			return __( 'Loading\u2026' );
		}
		if ( isExpanded ) {
			return __( 'Cancel' );
		}
		return isConnected ? __( 'Edit' ) : __( 'Set up' );
	};

	const modelOptions = [
		{ label: __( 'Auto-select (SDK chooses)' ), value: '' },
		...models.map( ( m ) => ( {
			label: m.name || m.id,
			value: m.id,
		} ) ),
	];

	// Action area: badge + toggle button.
	const actionArea = (
		<HStack spacing={ 3 } expanded={ false }>
			{ isConnected && ! isExpanded && <ConnectedBadge /> }
			<Button
				variant={
					isExpanded || isConnected ? 'tertiary' : 'secondary'
				}
				size={
					isExpanded || isConnected ? undefined : 'compact'
				}
				onClick={ handleButtonClick }
				disabled={ isLoading }
				aria-expanded={ isExpanded }
			>
				{ getButtonLabel() }
			</Button>
		</HStack>
	);

	// Expanded settings form.
	const settingsForm = isExpanded ? (
		<VStack
			spacing={ 4 }
			className="connector-settings"
			style={
				isConnected
					? { '--wp-components-color-background': '#f0f0f0' }
					: undefined
			}
		>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Endpoint URL' ) }
				value={ endpointUrl }
				onChange={ ( value ) => {
					setSaveError( null );
					setEndpointUrl( value );
					// Reset models when URL changes so we re-fetch.
					modelsFetchedForUrl.current = '';
					setModels( [] );
				} }
				placeholder="http://localhost:11434/v1"
				disabled={ isSaving }
				help={
					saveError
						? <span style={ { color: '#cc1818' } }>{ saveError }</span>
						: __( 'Base URL for the OpenAI-compatible API (e.g. Ollama, LM Studio, OpenRouter).' )
				}
			/>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'API Key' ) }
				type="password"
				value={ apiKey }
				onChange={ ( value ) => {
					setSaveError( null );
					setApiKey( value );
				} }
				placeholder="sk-..."
				disabled={ isSaving }
				help={ __(
					'Optional. Leave blank for servers that do not require authentication.'
				) }
			/>
			<div>
				{ isLoadingModels ? (
					<HStack spacing={ 2 } expanded={ false }>
						<Spinner />
						<span>{ __( 'Loading models\u2026' ) }</span>
					</HStack>
				) : (
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Default Model' ) }
						value={ defaultModel }
						options={ modelOptions }
						onChange={ setDefaultModel }
						disabled={ isSaving }
						help={ __(
							'Choose a specific model or let the SDK auto-select.'
						) }
					/>
				) }
			</div>
			{ isConnected && (
				<Button
					variant="link"
					isDestructive
					onClick={ handleRemove }
					disabled={ isSaving }
				>
					{ __( 'Remove connection' ) }
				</Button>
			) }
			<HStack justify="flex-start">
				<Button
					__next40pxDefaultSize
					variant="primary"
					disabled={ ! endpointUrl || isSaving }
					accessibleWhenDisabled
					isBusy={ isSaving }
					onClick={ handleSave }
				>
					{ __( 'Save' ) }
				</Button>
			</HStack>
		</VStack>
	) : null;

	return (
		<ConnectorItem
			className="connector-item--openai-compat"
			icon={ <Logo /> }
			name={ label }
			description={ description }
			actionArea={ actionArea }
		>
			{ settingsForm }
		</ConnectorItem>
	);
}

// Register the connector card.
registerConnector( 'openai-compat/connector', {
	label: __( 'OpenAI Compatible' ),
	description: __(
		'Connect to Ollama, LM Studio, OpenRouter, or any OpenAI-compatible endpoint.'
	),
	render: OpenAiCompatConnectorCard,
} );
