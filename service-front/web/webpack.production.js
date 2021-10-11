const { merge } = require('webpack-merge');
const common = require('./webpack.common.js');
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const path = require('path');

module.exports = merge(common, {
  mode: "production",
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'javascript/bundle.js',
    sourceMapFilename: '[name].js.map',
  },
  optimization: {
    minimizer: [new CssMinimizerPlugin()],
  },
});
