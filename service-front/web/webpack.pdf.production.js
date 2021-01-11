const { merge } = require('webpack-merge');
const common = require('./webpack.pdf.common.js');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const path = require('path');

module.exports = merge(common, {
  mode: "production",
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'javascript/pdf.bundle.js',
    sourceMapFilename: 'pdf.[name].js.map',
  },
  optimization: {
    minimizer: [new OptimizeCSSAssetsPlugin({})],
  },
});
