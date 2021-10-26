const config = {
  testEnvironment: "jsdom",
  reporters: ["default", "jest-junit"],
  resetMocks: true,
  testMatch: [
    "**/?(*.)+(test).js"
  ],
  collectCoverageFrom: [
    "src/**/*.js",
    "!**/node_modules/**",
    "!**/vendor/**"
  ]
};

module.exports = config;
