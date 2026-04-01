const { defineConfig } = require( 'cypress' );

module.exports = defineConfig( {
	e2e: {
		baseUrl: process.env.WP_BASE_URL || 'http://localhost:8888',
		supportFile: 'tests/e2e/support/e2e.js',
		specPattern: 'tests/e2e/**/*.cy.js',
		video: false,
		screenshotOnRunFailure: true,
		retries: {
			runMode: 1,
			openMode: 0,
		},
		defaultCommandTimeout: 10000,
		pageLoadTimeout: 30000,
	},
} );
