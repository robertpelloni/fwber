import { NextRequest, NextResponse } from 'next/server';

/**
 * Sentry Tunnel Endpoint
 *
 * Proxies Sentry envelope requests through our own domain to avoid
 * being blocked by browser ad-blockers (ERR_BLOCKED_BY_CLIENT).
 * The client sends envelopes here instead of directly to ingest.sentry.io.
 */
const SENTRY_HOST = 'o4510305653882880.ingest.us.sentry.io';
const KNOWN_PROJECT_IDS = ['4510305656176640'];

export async function POST(req: NextRequest) {
  try {
    const envelope = await req.text();
    const pieces = envelope.split('\n');

    if (pieces.length < 2) {
      return NextResponse.json({ error: 'Invalid envelope' }, { status: 400 });
    }

    // Parse the header to extract the DSN and verify project ID
    const header = JSON.parse(pieces[0]);

    if (!header.dsn) {
      return NextResponse.json({ error: 'Missing DSN' }, { status: 400 });
    }

    const dsn = new URL(header.dsn);
    const projectId = dsn.pathname.replace('/', '');

    if (!KNOWN_PROJECT_IDS.includes(projectId)) {
      return NextResponse.json({ error: 'Invalid project ID' }, { status: 400 });
    }

    // Forward to Sentry
    const sentryUrl = `https://${SENTRY_HOST}/api/${projectId}/envelope/`;

    const response = await fetch(sentryUrl, {
      method: 'POST',
      body: envelope,
      headers: {
        'Content-Type': 'application/x-sentry-envelope',
      },
    });

    return new NextResponse(response.body, {
      status: response.status,
      headers: {
        'Content-Type': 'application/json',
      },
    });
  } catch (error) {
    console.error('[sentry-tunnel] Error forwarding envelope:', error);
    return NextResponse.json({ error: 'Tunnel error' }, { status: 500 });
  }
}
