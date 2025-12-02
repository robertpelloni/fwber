describe('Navigation', () => {
  it('should navigate to the friends page', () => {
    cy.visit('/matches');
    cy.get('a[href*="friends"]').click();
    cy.url().should('include', '/friends');
    cy.get('h1').should('contain', 'Friends');
  });
});
