const merge = require('webpack-merge');
const common = require('./webpack.common.js');

module.exports = merge(common, {
  mode: "development",
  output: {
    path: '/dist',
    filename: 'javascript/bundle.js',
    sourceMapFilename: '[name].js.map',
  },
  watchOptions: {
    aggregateTimeout: 300,
    poll: 1000,
  },
});
