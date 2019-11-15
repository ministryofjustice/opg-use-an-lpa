const merge = require('webpack-merge');
const common = require('./webpack.pdf.common.js');

module.exports = merge(common, {
  output: {
    path: '/dist',
    filename: 'javascript/pdf.bundle.js',
    sourceMapFilename: 'pdf.[name].js.map',
  },
  resolve: {
    modules: [__dirname, 'node_modules'],
  },
});
