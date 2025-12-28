Cypress.on('uncaught:exception', (err, runnable) => {
    // returning false here prevents Cypress from failing the test
    if (err.message.includes('Module [project]/node_modules/@solana/wallet-adapter-react-ui/styles.css')) {
      return false;
    }
    return true;
  });
