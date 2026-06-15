import DOMPurify from 'dompurify';

/**
 * Sanitizes an HTML string to prevent XSS.
 * This is crucial for rendering untrusted federated content.
 */
export function sanitizeHtml(html: string): string {
    if (typeof window === 'undefined') {
        return html; // Fallback for SSR
    }
    return DOMPurify.sanitize(html, {
        ALLOWED_TAGS: ['p', 'br', 'span', 'a', 'b', 'i', 'em', 'strong', 'ul', 'li', 'ol'],
        ALLOWED_ATTR: ['href', 'target', 'rel']
    });
}
