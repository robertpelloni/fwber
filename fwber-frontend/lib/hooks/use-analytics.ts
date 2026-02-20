import { useEffect, useCallback, useRef } from 'react';
import { usePathname, useSearchParams } from 'next/navigation';

export function useAnalytics() {
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const sessionIdRef = useRef<string | null>(null);

    // Initialize session ID
    useEffect(() => {
        if (typeof window !== 'undefined') {
            let sid = sessionStorage.getItem('fwber_analytics_session_id');
            if (!sid) {
                sid = crypto.randomUUID();
                sessionStorage.setItem('fwber_analytics_session_id', sid);
            }
            sessionIdRef.current = sid;
        }
    }, []);

    const sendEvent = useCallback((eventName: string, payload?: any) => {
        if (typeof window === 'undefined') return;

        // Use current session ID or generate a temporary one if not ready
        const sid = sessionIdRef.current || sessionStorage.getItem('fwber_analytics_session_id') || crypto.randomUUID();

        // Safety check: ensure backend token exists
        const token = localStorage.getItem('auth_token');

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

        const baseUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

        fetch(`${baseUrl}/api/analytics/events`, {
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
