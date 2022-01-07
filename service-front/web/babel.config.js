module.exports = {
  presets: [
    [
      '@babel/preset-env',
      {
        targets: {
          node: 'current',
        },
      },
    ],
  ],
  plugins: [
    [
      '@babel/plugin-transform-runtime',
      {
        regenerator: true,
      },
    ],
    '@babel/plugin-transform-reserved-words',
    '@babel/plugin-transform-member-expression-literals',
    '@babel/plugin-transform-property-literals',
  ],
};
