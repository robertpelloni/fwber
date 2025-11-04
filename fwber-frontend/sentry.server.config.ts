// This file configures the initialization of Sentry on the server.
// The config you add here will be used whenever the server handles a request.
// https://docs.sentry.io/platforms/javascript/guides/nextjs/

import * as Sentry from "@sentry/nextjs";

Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,

  // Adjust this value in production, or use tracesSampler for greater control
  tracesSampleRate: 1.0,

  // Setting this option to true will print useful information to the console while you're setting up Sentry.
  debug: false,

  // Filter out expected errors
  beforeSend(event, hint) {
    const error = hint.originalException;
    
    // Ignore expected prerender errors
    if (error && typeof error === 'object' && 'message' in error) {
      const message = String(error.message).toLowerCase();
      if (message.includes('authentication required') || 
          message.includes('prerender') ||
          message.includes('cannot find module \'critters\'')) {
        return null;
      }
    }

    return event;
  },
});
