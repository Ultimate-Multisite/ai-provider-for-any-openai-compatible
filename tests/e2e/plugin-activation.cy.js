/**
 * E2E test: Plugin activation without fatal errors.
 *
 * This is the critical regression test for the namespace declaration bug.
 * If the plugin has a fatal parse error, the plugins page will show an
 * error notice and the plugin row will not have an "active" state.
 *
 * Run: npx cypress run --spec tests/e2e/plugin-activation.cy.js
 */

describe( 'Plugin Activation', () => {
	beforeEach( () => {
		cy.wpLogin();
	} );

	it( 'plugin is active and no fatal errors on plugins page', () => {
		cy.visit( '/wp-admin/plugins.php' );

		// The plugin row should exist and be marked active.
		// wp-env activates plugins listed in .wp-env.json automatically.
		cy.get( '[data-slug="ultimate-ai-connector-compatible-endpoints"]' )
			.should( 'exist' )
			.and( 'have.class', 'active' );
	} );

	it( 'no PHP errors visible on plugins page', () => {
		cy.visit( '/wp-admin/plugins.php' );

		// WordPress displays fatal errors in a div.error or div.notice-error.
		// There should be no error notices related to our plugin.
		cy.get( 'body' ).then( ( $body ) => {
			const bodyText = $body.text();
			expect( bodyText ).not.to.contain( 'Fatal error' );
			expect( bodyText ).not.to.contain( 'Namespace declaration' );
			expect( bodyText ).not.to.contain( 'Parse error' );
		} );
	} );

	it( 'admin dashboard loads without errors', () => {
		cy.visit( '/wp-admin/' );

		// Dashboard should load successfully.
		cy.get( '#wpbody' ).should( 'exist' );

		// No PHP fatal errors in the page.
		cy.get( 'body' ).then( ( $body ) => {
			const bodyText = $body.text();
			expect( bodyText ).not.to.contain( 'Fatal error' );
			expect( bodyText ).not.to.contain( 'Parse error' );
		} );
	} );

	it( 'plugin can be deactivated and reactivated', () => {
		cy.visit( '/wp-admin/plugins.php' );

		// Deactivate the plugin.
		cy.get( '[data-slug="ultimate-ai-connector-compatible-endpoints"]' )
			.find( '.deactivate a' )
			.click();

		// Verify it is now inactive.
		cy.get( '[data-slug="ultimate-ai-connector-compatible-endpoints"]' )
			.should( 'have.class', 'inactive' );

		// Reactivate the plugin.
		cy.get( '[data-slug="ultimate-ai-connector-compatible-endpoints"]' )
			.find( '.activate a' )
			.click();

		// Verify it is active again with no errors.
		cy.get( '[data-slug="ultimate-ai-connector-compatible-endpoints"]' )
			.should( 'have.class', 'active' );

		// No fatal errors after reactivation.
		cy.get( 'body' ).then( ( $body ) => {
			const bodyText = $body.text();
			expect( bodyText ).not.to.contain( 'Fatal error' );
			expect( bodyText ).not.to.contain( 'Parse error' );
		} );
	} );
} );
