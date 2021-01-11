const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
  entry: './src/index.js',
  module: {
    rules: [
      {
        test: /\.scss$/i,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              sourceMap: true,
              url: false,
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true,
              implementation: require('node-sass'),
            },
          },
        ],
      },
      {
        test: /\.css$/i,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              sourceMap: true,
              url: false,
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
    new CopyWebpackPlugin({
        patterns: [
          { from: 'src/robots.txt', to: 'robots.txt' },
          { from: 'node_modules/govuk-frontend/govuk/assets', to: 'assets' },
          { from: 'src/images', to: 'assets/images' },
          {
            from: 'node_modules/@ministryofjustice/frontend/moj/assets',
            to: 'assets',
          },
        ]
      }
    ),
  ],
};
