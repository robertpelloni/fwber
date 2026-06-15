import DOMPurify from 'isomorphic-dompurify';

/**
 * Sanitizes an HTML string to prevent XSS.
 * This is crucial for rendering untrusted federated content.
 * Using isomorphic-dompurify ensures safety during both SSR and client-side rendering.
 */
export function sanitizeHtml(html: string): string {
    return DOMPurify.sanitize(html, {
        ALLOWED_TAGS: ['p', 'br', 'span', 'a', 'b', 'i', 'em', 'strong', 'ul', 'li', 'ol'],
        ALLOWED_ATTR: ['href', 'target', 'rel']
    });
}
