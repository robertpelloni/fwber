describe('Friends Page', () => {
  beforeEach(() => {
    cy.visit('/friends');
  });

  it('should display the main heading', () => {
    cy.get('h1').should('contain', 'Friends');
  });

  it('should switch between tabs', () => {
    cy.get('button').contains('Friend Requests').click();
    cy.get('p').should('contain', 'You don\'t have any pending friend requests.');

    cy.get('button').contains('Find Friends').click();
    cy.get('input[placeholder="Search by name or email"]').should('be.visible');
  });
});
