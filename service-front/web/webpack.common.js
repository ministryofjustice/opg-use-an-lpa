const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
  entry: './src/index.js',
  module: {
    rules: [
      {
        test: /\.s[ac]ss$/i,
        use: [
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
      filename: 'stylesheets/[name].css',
      chunkFilename: 'stylesheets/[id].css',
    }),
    new CopyWebpackPlugin([
      { from: 'src/robots.txt', to: 'robots.txt' },
      { from: 'src/stylesheets', to: 'stylesheets' },
      { from: 'node_modules/govuk-frontend/govuk/assets', to: 'assets' },
    ]),
  ],
};
