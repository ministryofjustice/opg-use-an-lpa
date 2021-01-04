const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: './src/pdf.js',
  resolve: {
    modules: [__dirname, 'node_modules'],
  },
  output: {
    path: '/dist',
    filename: 'build.js',
  },
  module: {
    rules: [
      {
        test: /\.s[ac]ss$/i,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              url: true,
              url: false,
            },
          },
          {
            loader: 'sass-loader',
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
