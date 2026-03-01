const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		connector: path.resolve( __dirname, 'src', 'index.jsx' ),
	},
	output: {
		path: path.resolve( __dirname, 'build' ),
		filename: '[name].js',
		module: true,
		chunkFormat: 'module',
	},
	experiments: {
		...( defaultConfig.experiments || {} ),
		outputModule: true,
	},
	externalsType: 'module',
	externals: {
		'@wordpress/connectors': '@wordpress/connectors',
	},
	plugins: defaultConfig.plugins.filter(
		( plugin ) =>
			plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
	),
	module: {
		...defaultConfig.module,
		rules: [
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				use: {
					loader: require.resolve( 'babel-loader' ),
					options: {
						presets: [
							require.resolve(
								'@babel/preset-env'
							),
						],
						plugins: [
							[
								require.resolve(
									'@babel/plugin-transform-react-jsx'
								),
								{
									runtime: 'classic',
									pragma: 'createElement',
								},
							],
						],
					},
				},
			},
		],
	},
};
