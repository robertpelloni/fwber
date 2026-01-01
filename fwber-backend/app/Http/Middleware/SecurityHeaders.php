<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 *
 * Adds security-related HTTP headers to all responses to protect against
 * common web vulnerabilities (XSS, clickjacking, MIME sniffing, etc.)
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS in production
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri());
        }

        $response = $next($request);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection (for older browsers that support it)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Prevent clickjacking attacks
        // Use SAMEORIGIN to allow framing by same origin (useful for admin panels)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Referrer policy - control referrer information sent with requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (CSP)
        // Strict by default in production; relaxed in non-production or when explicitly allowed via env
        $isProd = app()->environment('production');
        $relaxed = (bool) env('CSP_RELAXED', !$isProd);

        $scriptDirectives = "script-src 'self'";

        // Production: remove unsafe-inline if possible, or strictly document why it is needed.
        // For now, we will default to 'self' in production unless CSP_RELAXED is true.
        // If your frontend needs inline scripts (e.g. for some libs), you might need to add hashes or nonces.
        
        // Strict style-src in production (no unsafe-inline) unless relaxed
        $styleDirectives = ($isProd && !$relaxed) 
            ? "style-src 'self'" 
            : "style-src 'self' 'unsafe-inline'";
        
        $frameAncestors = "frame-ancestors 'none'";

        $csp = implode('; ', [
            "default-src 'self'",
            $scriptDirectives,
            $styleDirectives,
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self' https: wss:",
            $frameAncestors,
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // Permissions Policy (formerly Feature Policy)
        // Disable potentially dangerous browser features
        $permissionsPolicy = implode(', ', [
            'geolocation=(self)',
            'microphone=(self)',
            'camera=(self)',
            'payment=(self)',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()'
        ]);
        $response->headers->set('Permissions-Policy', $permissionsPolicy);

        // Strict Transport Security (HSTS)
        // Only enable this if you're using HTTPS
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Remove headers that leak server information
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
