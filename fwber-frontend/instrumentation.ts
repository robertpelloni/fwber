import type { NodeOptions, EdgeOptions } from '@sentry/nextjs';

export async function register() {
  if (process.env.NEXT_RUNTIME === 'edge') {
    const Sentry = await import('@sentry/nextjs');
    
    const edgeOptions: EdgeOptions = {
      dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
      tracesSampleRate: 1.0,
      debug: false,
    };
    
    Sentry.init(edgeOptions);
    return;
  }

  // Default to the node runtime (which also covers route handlers)
  if (process.env.NEXT_RUNTIME === 'nodejs') {
    const Sentry = await import('@sentry/nextjs');

    const baseOptions: NodeOptions = {
      dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
      tracesSampleRate: 1.0,
      debug: false,
    };

    const serverOptions: NodeOptions = {
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

    Sentry.init(serverOptions);
  }
}

