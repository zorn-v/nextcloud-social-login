const path = require('path')
const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
  mode: 'production',
	entry: {
    'personal': path.join(__dirname, 'src', 'personal.js'),
    'settings': path.join(__dirname, 'src', 'settings.js'),
	},
	output: {
		path: path.resolve(__dirname, './js/'),
	},
	module: {
		rules: [
			{
				test: /\.css$/,
				use: ['vue-style-loader', 'css-loader']
			},
			{
				test: /\.scss$/,
				use: ['vue-style-loader', 'css-loader', 'sass-loader']
			},
			{
				test: /\.vue$/,
				loader: 'vue-loader',
				exclude: /node_modules/
			},
		]
	},
	plugins: [new VueLoaderPlugin()],
}
