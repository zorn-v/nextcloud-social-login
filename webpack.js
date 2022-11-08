const path = require('path')
const { VueLoaderPlugin } = require('vue-loader')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts')

module.exports = {
  mode: 'production',
  context: path.resolve(__dirname, 'src'),
  entry: {
    personal: './personal.js',
    settings: './settings.js',
    styles: './styles.scss'
  },
  output: {
    path: path.resolve(__dirname, 'js'),
  },
  performance: {
    maxAssetSize: 256000,
    maxEntrypointSize: 256000,
  },
  stats: { modulesSpace: 0 },
  module: {
    rules: [
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader']
      },
      {
        test: /\.scss$/,
        use: ['css-loader', 'sass-loader']
      },
      {
        test: /src[\\\/].+\.scss$/,
        exclude: /node_modules/,
        use: [MiniCssExtractPlugin.loader, 'css-loader?url=false', 'sass-loader']
      },
      {
        test: /\.vue$/,
        loader: 'vue-loader',
        exclude: /node_modules/
      },
    ]
  },
  plugins: [
    new VueLoaderPlugin(),
    new RemoveEmptyScriptsPlugin(),
    new MiniCssExtractPlugin({filename: '../css/[name].css'}),
  ],
}
