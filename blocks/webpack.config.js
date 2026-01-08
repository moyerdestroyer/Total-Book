const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyPlugin = require('copy-webpack-plugin');

module.exports = {
	...defaultConfig,
	entry: {
		'book-display/index': path.resolve(__dirname, 'book-display/src/index.js'),
		'book-shelf/index': path.resolve(__dirname, 'book-shelf/src/index.js'),
	},
	output: {
		path: path.resolve(__dirname, '../dist'),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) => !(plugin instanceof MiniCssExtractPlugin)
		),
		new MiniCssExtractPlugin({
			filename: '[name].css',
		}),
		new CopyPlugin({
			patterns: [
				{
					from: path.resolve(__dirname, 'book-display/block.json'),
					to: path.resolve(__dirname, '../dist/book-display/block.json'),
				},
				{
					from: path.resolve(__dirname, 'book-shelf/block.json'),
					to: path.resolve(__dirname, '../dist/book-shelf/block.json'),
				},
			],
		}),
	],
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules.map((rule) => {
				// Replace the default CSS rule with one that handles SCSS
				if (rule.test && rule.test.toString().includes('css')) {
					return {
						test: /\.(scss|css)$/i,
						use: [
							MiniCssExtractPlugin.loader,
							'css-loader',
							{
								loader: 'sass-loader',
								options: {
									sassOptions: {
										outputStyle: 'compressed',
									},
								},
							},
						],
					};
				}
				return rule;
			}),
		],
	},
};
