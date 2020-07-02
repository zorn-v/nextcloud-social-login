const path = require('path');

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
				test: /\.scss$/,
				use: ['css-loader', 'sass-loader']
			},
		]
	}
}
