const merge = require('webpack-merge');
const common = require('./webpack.pdf.common.js');
const path = require('path');

module.exports = merge(common, {
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'javascript/pdf.bundle.js',
    sourceMapFilename: 'pdf.[name].js.map',
  },
});
