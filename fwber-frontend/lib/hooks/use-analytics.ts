import { useEffect, useCallback, useRef } from 'react';
import { usePathname, useSearchParams } from 'next/navigation';
import { safeLocalStorageGet, safeSessionStorageGet, safeSessionStorageSet } from '@/lib/browser-storage';

export function useAnalytics() {
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const sessionIdRef = useRef<string | null>(null);

    // Initialize session ID
    useEffect(() => {
        if (typeof window !== 'undefined') {
            let sid = safeSessionStorageGet('fwber_analytics_session_id');
            if (!sid) {
                sid = crypto.randomUUID();
                safeSessionStorageSet('fwber_analytics_session_id', sid);
            }
            sessionIdRef.current = sid;
        }
    }, []);

    const sendEvent = useCallback((eventName: string, payload?: any) => {
        if (typeof window === 'undefined') return;

        // Use current session ID or generate a temporary one if not ready
        const sid = sessionIdRef.current || safeSessionStorageGet('fwber_analytics_session_id') || crypto.randomUUID();

        // Use the same token key as AuthContext
        const token = safeLocalStorageGet('fwber_token');

        const payloadData = {
            session_id: sid,
            events: [
                {
                    event_name: eventName,
                    payload: payload || {},
                    url: window.location.href,
                }
            ]
        };

        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };

        // Add token if we have one so we get authenticated analytics
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        // Always go through the Next.js proxy in the browser
        fetch('/api/analytics/events', {
            method: 'POST',
            headers,
            body: JSON.stringify(payloadData),
            keepalive: true,
        }).catch((err) => console.error('Analytics error:', err));
    }, []);

    // Track page views automatically
    useEffect(() => {
        if (pathname) {
            // give it a tiny delay to ensure session ID is loaded
            setTimeout(() => {
                const url = pathname + (searchParams?.toString() ? `?${searchParams.toString()}` : '');
                sendEvent('page_view', { path: url });
            }, 100);
        }
    }, [pathname, searchParams, sendEvent]);

    return {
        trackEvent: sendEvent
    };
}
