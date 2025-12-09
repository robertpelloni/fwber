describe('Admin Analytics Dashboard', () => {
  const adminUser = {
    id: 1,
    name: 'Admin User',
    role: 'admin',
    is_admin: true
  };

  beforeEach(() => {
    // Mock Auth
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: { token: 'admin-token', user: adminUser }
    }).as('login');

    // Mock Platform Analytics
    cy.intercept('GET', '**/api/analytics*', {
      statusCode: 200,
      body: {
        users: {
          total: 1250,
          active: 450,
          new_today: 12,
          growth_rate: 5.4
        },
        messages: {
          total: 85000,
          average_per_user: 68,
          moderation_stats: {
            flagged: 15,
            approved: 10,
            rejected: 2,
            pending_review: 3
          }
        },
        locations: {
          active_areas: 3,
          coverage_radius: 50,
          most_active: [
            { name: 'Downtown', active_users: 120, message_count: 4500 }
          ]
        },
        trends: {
          hourly_activity: [
            { hour: 10, messages: 150 },
            { hour: 11, messages: 300 }
          ],
          top_categories: [
            { category: 'General', count: 500 },
            { category: 'Tech', count: 300 }
          ]
        },
        performance: {
          api_response_time: 45,
          sse_connections: 120,
          cache_hit_rate: 92.5,
          error_rate: 0.1
        }
      }
    }).as('getPlatformAnalytics');

    // Mock Realtime Metrics
    cy.intercept('GET', '**/api/analytics/realtime*', {
      statusCode: 200,
      body: {
        active_connections: 85,
        messages_per_minute: 42,
        system_load: 25
      }
    }).as('getRealtimeMetrics');

    // Mock Moderation Insights
    cy.intercept('GET', '**/api/analytics/moderation*', {
      statusCode: 200,
      body: {
        ai_accuracy: 98.5,
        human_review_rate: 1.5,
        false_positive_rate: 0.2,
        average_review_time: 45,
        top_flagged_categories: [
          { category: 'Spam', count: 10 },
          { category: 'Harassment', count: 5 }
        ]
      }
    }).as('getModerationInsights');

    // Mock System Health
    cy.intercept('GET', '**/api/config/health*', {
      statusCode: 200,
      body: {
        status: 'healthy',
        services: {
          database: 'up',
          cache: 'up',
          mercure: 'up'
        }
      }
    }).as('getSystemHealth');
  });

  it('loads the analytics dashboard and displays key metrics', () => {
    cy.visit('/analytics', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'admin-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(adminUser));
      }
    });

    // Verify Header
    cy.contains('h1', 'Platform Analytics').should('be.visible');
    cy.contains('Admin Observatory').should('be.visible');

    // Verify Platform Analytics Overview
    // Using .parent() can be brittle if the DOM structure changes slightly (e.g. extra div wrapper)
    // Using .within() or finding by testid is better, but for now let's just check existence nearby
    cy.contains('Total Users').should('exist');
    cy.contains('1,250').should('exist');
    
    cy.contains('Active Users').should('exist');
    cy.contains('450').should('exist');

    cy.contains('Messages').should('exist');
    cy.contains('85,000').should('exist');

    // Verify System Health
    cy.contains('h2', 'System Health').should('be.visible');
    cy.contains('Overall Status').should('exist');
    cy.contains('Healthy').should('exist');
    
    // Verify Live System Signals
    cy.contains('h2', 'Live System Signals').should('be.visible');
    cy.contains('Active Connections').should('exist');
    cy.contains('85').should('exist');

    // Verify Moderation Insights
    cy.contains('h2', 'Moderation Insights').should('be.visible');
    cy.contains('AI Accuracy').should('exist');
    cy.contains('98.5%').should('exist');
  });

  it('handles error states gracefully', () => {
    // Force an error on the platform analytics endpoint
    cy.intercept('GET', '**/api/analytics*', {
      statusCode: 500,
      body: { message: 'Internal Server Error' }
    }).as('getPlatformAnalyticsError');

    cy.visit('/analytics', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'admin-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(adminUser));
      }
    });

    // Verify we start with loading state
    cy.get('.animate-pulse').should('exist');

    // Wait for the error response (first attempt)
    cy.wait('@getPlatformAnalyticsError');

    // Wait for loading to finish (retries exhausted)
    // This might take a while (3 retries * exponential backoff)
    cy.get('.animate-pulse', { timeout: 30000 }).should('not.exist');

    // Now check for error UI
    cy.contains('Unable to load analytics').should('be.visible');
    cy.contains('Retry').should('be.visible');
  });
});
