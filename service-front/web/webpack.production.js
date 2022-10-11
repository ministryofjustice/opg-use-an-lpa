const { merge } = require('webpack-merge');
const common = require('./webpack.common.js');
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const path = require('path');

module.exports = merge(common, {
  mode: "production", //minify?
  target: ["web", "es5"],//list browsers
  output: {
    path: path.resolve(__dirname, 'dist'),//outdir
    filename: 'javascript/bundle.js',//outfile
    sourceMapFilename: '[name].js.map', //sourcemap and sourcefile
  },
  optimization: {
    minimizer: [new CssMinimizerPlugin()],
  },
});
