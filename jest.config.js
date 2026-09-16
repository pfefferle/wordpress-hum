const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config.js' );

module.exports = {
	...defaultConfig,
	testMatch: [ '**/__tests__/**/*.[jt]s?(x)' ],
	testPathIgnorePatterns: [ '/build/', '/node_modules/', '/tests/', '/vendor/' ],
	setupFilesAfterEnv: [ '<rootDir>/jest.setup.js' ],
	// `@wordpress/components` pulls in `uuid`, `@wordpress/ui` and `@wordpress/theme`, which ship
	// as untranspiled ESM (the last one as .mjs). Run them through Babel like our own sources.
	transform: {
		...defaultConfig.transform,
		'\\.mjs$': require.resolve( '@wordpress/scripts/config/babel-transform' ),
	},
	transformIgnorePatterns: [ '/node_modules/(?!(?:.*/)?(?:uuid|@wordpress/(?:theme|ui))/)' ],
};
