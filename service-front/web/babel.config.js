import corejsPackage from 'core-js/package.json' with { type: 'json' };

const config = function (api) {
  // build default env
  const presets = [
    [
      "@babel/preset-env",
      {
        "corejs": corejsPackage.version,
        "targets": ">0.1%, last 4 versions, safari > 11, Firefox ESR, not dead",
        "debug": true,
        "modules": false
      }
    ]
  ];
  const plugins = [
    "@babel/plugin-transform-runtime",
    "@babel/plugin-transform-reserved-words",
    "@babel/plugin-transform-member-expression-literals",
    "@babel/plugin-transform-property-literals",
    [
      "babel-plugin-polyfill-corejs3",
      {
        "method": "usage-global",
        "version": corejsPackage.version
      }
    ]
  ];

  if (api.env("test")) {
    presets.length = 0;
    plugins.length = 0;
    plugins.push("@babel/plugin-transform-modules-commonjs");
  }

  return {
    presets,
    plugins
  };
}

export default config;
