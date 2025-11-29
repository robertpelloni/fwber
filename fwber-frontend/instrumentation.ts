import * as Sentry from '@sentry/nextjs';

const baseOptions: Sentry.NodeOptions = {
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  tracesSampleRate: 1.0,
  debug: false,
};

const serverOptions: Sentry.NodeOptions = {
  ...baseOptions,
  beforeSend(event, hint) {
    const error = hint?.originalException;
    if (error && typeof error === 'object' && 'message' in error) {
      const message = String((error as { message?: string }).message ?? '').toLowerCase();
      if (
        message.includes('authentication required') ||
        message.includes('prerender') ||
        message.includes("cannot find module 'critters'")
      ) {
        return null;
      }
    }

    return event;
  },
};

const edgeOptions: Sentry.EdgeOptions = {
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  tracesSampleRate: 1.0,
  debug: false,
};

export function register() {
  if (process.env.NEXT_RUNTIME === 'edge') {
    Sentry.init(edgeOptions);
    return;
  }

  // Default to the node runtime (which also covers route handlers)
  Sentry.init(serverOptions);
}
