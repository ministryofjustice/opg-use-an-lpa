module.exports = {
  presets: [
    [
      '@babel/preset-env',
      {
        corejs: {
          version: "3",
          proposals: true
        },
        useBuiltIns: 'usage',
        targets: {
          browsers: [
            "edge >= 16",
            "safari >= 9",
            "firefox >= 57",
            "ie >= 11",
            "ios >= 9",
            "chrome >= 49"
          ]
        },
      },
    ],
  ],
  plugins: [
    [
      '@babel/plugin-transform-runtime',
      {
        regenerator: true,
        corejs: 3,
      }],
    '@babel/plugin-transform-reserved-words',
    '@babel/plugin-transform-member-expression-literals',
    '@babel/plugin-transform-property-literals',
  ],
};
