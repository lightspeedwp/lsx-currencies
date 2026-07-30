const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'blocks/currency-switcher/index': path.resolve( __dirname, 'src/blocks/currency-switcher/index.js' ),
		'blocks/currency-switcher/view': path.resolve( __dirname, 'src/blocks/currency-switcher/view.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
};
