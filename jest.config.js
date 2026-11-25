module.exports = {
	preset: '@wordpress/jest-preset-default',
	testMatch: [ '**/__tests__/**/*.[jt]s?(x)' ],
	testPathIgnorePatterns: [ '/build/', '/node_modules/', '/tests/', '/vendor/' ],
	setupFilesAfterEnv: [ '<rootDir>/jest.setup.js' ],
	// `@wordpress/components` pulls in `uuid`, `@wordpress/ui` and `@wordpress/theme`, which ship
	// as untranspiled ESM (the last one as .mjs). Run them through Babel like our own sources.
	transform: {
		'\\.[jt]sx?$': [ 'babel-jest', { presets: [ '@wordpress/babel-preset-default' ] } ],
		'\\.mjs$': [ 'babel-jest', { presets: [ '@wordpress/babel-preset-default' ] } ],
	},
	transformIgnorePatterns: [ '/node_modules/(?!(?:.*/)?(?:uuid|@wordpress/(?:theme|ui))/)' ],
};
