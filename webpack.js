const path = require('path');

module.exports = {
  mode: 'production',
	entry: {
		'personal': path.join(__dirname, 'src', 'personal.js'),
	},
	output: {
		path: path.resolve(__dirname, './js/'),
	}
}
