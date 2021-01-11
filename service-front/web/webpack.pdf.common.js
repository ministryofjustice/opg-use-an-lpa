const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: './src/pdf.js',
  module: {
    rules: [
      {
        test: /\.scss$/i,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              url: false,
            },
          },
          {
            loader: 'sass-loader',
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
        test: /\.(woff|woff2|ttf|eot|svg)/,
        use: 'base64-inline-loader',
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'stylesheets/pdf.css',
    }),
  ],
};
