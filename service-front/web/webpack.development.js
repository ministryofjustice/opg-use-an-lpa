const { merge } = require('webpack-merge');
const common = require('./webpack.common.js');
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

module.exports = merge(common, {
  mode: "development",
  target: ["web", "es5"],
  output: {
    path: '/dist',
    filename: 'javascript/bundle.js',
    sourceMapFilename: '[name].js.map',
  },
  watchOptions: {
    aggregateTimeout: 300,
    poll: 1000,
  },
  optimization: {
    minimizer: [new CssMinimizerPlugin()],
  },
});
