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
    environment: {
      // The environment supports arrow functions ('() => { ... }').
      arrowFunction: false,
      // The environment supports BigInt as literal (123n).
      bigIntLiteral: false,
      // The environment supports const and let for variable declarations.
      const: true,
      // The environment supports destructuring ('{ a, b } = obj').
      destructuring: true,
      // The environment supports an async import() function to import EcmaScript modules.
      dynamicImport: false,
      // The environment supports 'for of' iteration ('for (const x of array) { ... }').
      forOf: true,
      // The environment supports ECMAScript Module syntax to import ECMAScript modules (import ... from '...').
      module: false,
    },
  },
  optimization: {
    minimizer: [new CssMinimizerPlugin()],
  },
});
