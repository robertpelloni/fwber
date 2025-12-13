const { defineConfig } = require('cypress')
const { authenticator } = require('otplib')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://127.0.0.1:3005',
    supportFile: false,
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    viewportWidth: 1280,
    viewportHeight: 720,
    video: true,
    screenshotOnRunFailure: true,
    defaultCommandTimeout: 10000,
    requestTimeout: 30000,
    responseTimeout: 60000,
    pageLoadTimeout: 120000,
    chromeWebSecurity: false,
    setupNodeEvents(on, config) {
      on('task', {
        log(message) {
          console.log(message)
          return null
        },
        generateOTP(secret) {
          return authenticator.generate(secret)
        },
      })
      // Mock geolocation
      on('before:browser:launch', (browser, launchOptions) => {
        if (browser.name === 'chrome') {
          launchOptions.args.push('--disable-web-security')
          launchOptions.args.push('--disable-features=VizDisplayCompositor')
        }
        return launchOptions
      })
    },
  },
  env: {
    API_URL: 'http://localhost:8000/api',
    MERCURE_URL: 'http://localhost:3001',
  },
})


