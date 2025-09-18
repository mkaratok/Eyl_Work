// jest.config.js
module.exports = {
  preset: '@nuxt/test-utils',
  transform: {
    '^.+\\.js$': 'babel-jest',
    '^.+\\.vue$': '@vue/vue3-jest'
  },
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/$1',
    '^~/(.*)$': '<rootDir>/$1',
    '^vue$': 'vue/dist/vue.common.js'
  },
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['<rootDir>/tests/setup.js']
}