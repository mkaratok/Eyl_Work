// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
  css: ['~/assets/css/main.css'],
  postcss: {
    plugins: {
      tailwindcss: {},
      autoprefixer: {},
    },
  },
  plugins: [
    '~/plugins/i18n.js',
    '~/plugins/auth.js'
  ],
  alias: {
    '~/services': './services',
    '~/composables': './composables'
  },
  routeRules: {
    // Apply auth middleware to user routes
    '/user/**': { middleware: ['auth'] },
    '/admin/**': { middleware: ['auth'] },
    '/seller/**': { middleware: ['auth'] },
    // Apply guest middleware to auth routes
    '/login': { middleware: ['guest'] },
    '/register': { middleware: ['guest'] }
  },
  // Set the development server port to 3001 (since 3000 is in use)
  devServer: {
    port: 3001,
    host: '0.0.0.0' // Allow external connections
  },
  runtimeConfig: {
    public: {
      API_BASE_URL: process.env.API_BASE_URL || 'http://localhost:8000/api',
      APP_URL: process.env.APP_URL || 'http://localhost:3001'
    }
  }
})