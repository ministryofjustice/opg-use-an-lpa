const config = {
  testEnvironment: 'jsdom',
  reporters: ['default', 'jest-junit'],
  resetMocks: true,
  transform: {
    '\\.[jt]sx?$': 'babel-jest',
  },
  testMatch: ['**/?(*.)+(test).js'],
  collectCoverageFrom: ['src/**/*.js', '!**/node_modules/**', '!**/vendor/**'],
};

module.exports = config;
