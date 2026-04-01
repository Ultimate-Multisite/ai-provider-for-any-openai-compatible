/**
 * Cypress E2E support file.
 *
 * Loaded before every spec file. Registers custom commands
 * for WordPress admin login.
 */

const WP_ADMIN_USER = Cypress.env( 'WP_ADMIN_USER' ) || 'admin';
const WP_ADMIN_PASSWORD = Cypress.env( 'WP_ADMIN_PASSWORD' ) || 'password';

/**
 * Log in to the WordPress admin dashboard.
 */
Cypress.Commands.add( 'wpLogin', ( username = WP_ADMIN_USER, password = WP_ADMIN_PASSWORD ) => {
	cy.visit( '/wp-login.php' );
	cy.get( '#user_login' ).clear().type( username );
	cy.get( '#user_pass' ).clear().type( password );
	cy.get( '#wp-submit' ).click();
	cy.url().should( 'contain', 'wp-admin' );
} );
