const { merge } = require('webpack-merge');
const common = require('./webpack.pdf.common.js');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');

module.exports = merge(common, {
  mode: "development",
  output: {
    path: '/dist',
    filename: 'javascript/pdf.bundle.js',
    sourceMapFilename: 'pdf.[name].js.map',
  },
  resolve: {
    modules: [__dirname, 'node_modules'],
  },
  optimization: {
    minimizer: [new OptimizeCSSAssetsPlugin({})],
  },
});
