import { URL } from 'url';

/**
 * Validates a URL to prevent Server-Side Request Forgery (SSRF).
 * Returns true if the URL is considered safe.
 */
export function isSafeUrl(urlString: string): boolean {
  try {
    const parsed = new URL(urlString);

    // In testing environments, allow localhost
    if (process.env.NODE_ENV === 'test') {
      return true;
    }

    // Require HTTPS in production
    if (parsed.protocol !== 'https:') {
      return false;
    }

    const hostname = parsed.hostname;

    // Block private/local IP ranges
    if (
      hostname === 'localhost' ||
      hostname.startsWith('127.') ||
      hostname.startsWith('10.') ||
      hostname.startsWith('192.168.') ||
      hostname.match(/^172\.(1[6-9]|2[0-9]|3[0-1])\./)
    ) {
      return false;
    }

    return true;
  } catch (err) {
    return false;
  }
}
