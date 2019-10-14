// Webpack uses this to work with directories
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

// This is main configuration object.
// Here you write different options and tell Webpack what to do
module.exports = {
  // Path to your entry point. From this file Webpack will begin his work
  entry: './src/index.js',

  module: {
    rules: [
      {
        test: /\.s[ac]ss$/i,
        use: [
          // Creates `style` nodes from JS strings
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              sourceMap: true,
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true,
            },
          },
        ],
      },
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
            plugins: [
              '@babel/plugin-transform-reserved-words',
              '@babel/plugin-transform-member-expression-literals',
              '@babel/plugin-transform-property-literals',
            ],
          },
        },
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      // Options similar to the same options in webpackOptions.output
      // both options are optional
      filename: 'stylesheets/[name].css',
      chunkFilename: 'stylesheets/[id].css',
    }),
    new CopyWebpackPlugin([
      { from: 'src/robots.txt', to: 'robots.txt' },
      { from: 'src/stylesheets', to: 'stylesheets' },
      { from: 'node_modules/govuk-frontend/govuk/assets', to: 'assets' },
    ]),
  ],
  // Path and filename of your result bundle.
  // Webpack will bundle all JavaScript into this file
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'javascript/bundle.js',
    sourceMapFilename: '[name].js.map',
  },
};
