/**
 * Echo initialization for real-time WebSocket communication
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: any;
        Echo: any;
    }
}

export const initEcho = (token?: string) => {
    if (typeof window === 'undefined') return null;

    window.Pusher = Pusher;

    const isDev = process.env.NODE_ENV === 'development';
    
    let appKey = process.env.NEXT_PUBLIC_REVERB_APP_KEY || process.env.NEXT_PUBLIC_PUSHER_APP_KEY || 'app-key';
    if (appKey === 'your_app_key') {
        if (!window.localStorage.getItem('fwber_suppress_app_key_warn')) {
             console.warn('fwber: Detected placeholder "your_app_key". Falling back to "app-key" for local development.');
             window.localStorage.setItem('fwber_suppress_app_key_warn', 'true');
        }
        appKey = 'app-key';
    }

    const options: any = {
        broadcaster: 'reverb',
        key: appKey,
        wsHost: process.env.NEXT_PUBLIC_REVERB_HOST,
        wsPort: process.env.NEXT_PUBLIC_REVERB_PORT,
        wssPort: process.env.NEXT_PUBLIC_REVERB_PORT,
        forceTLS: (process.env.NEXT_PUBLIC_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    };

    if (process.env.NEXT_PUBLIC_PUSHER_HOST) {
        options.broadcaster = 'pusher';
        options.wsHost = process.env.NEXT_PUBLIC_PUSHER_HOST;
        options.wsPort = process.env.NEXT_PUBLIC_PUSHER_PORT ? parseInt(process.env.NEXT_PUBLIC_PUSHER_PORT) : 80;
        options.wssPort = process.env.NEXT_PUBLIC_PUSHER_PORT ? parseInt(process.env.NEXT_PUBLIC_PUSHER_PORT) : 443;
        options.forceTLS = process.env.NEXT_PUBLIC_PUSHER_SCHEME === 'https';
        options.cluster = process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER || 'mt1';
    } else if (isDev) {
        if (!options.wsHost) options.wsHost = window.location.hostname;
        if (!options.wsPort) options.wsPort = 8080;
        if (!options.wssPort) options.wssPort = 8080;
        if (!options.forceTLS) options.forceTLS = false;
    } else {
        if (!options.wsHost) { 
             if (!process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER) {
                 options.wsHost = window.location.hostname;
                 options.wsPort = 8080;
                 options.wssPort = 8080;
                 options.forceTLS = window.location.protocol === 'https:';
             }
        }
    }

    const apiBaseUrl = typeof window !== 'undefined' ? '/api' : (process.env.NEXT_PUBLIC_API_URL || '');

    if (token) {
        options.authEndpoint = `${apiBaseUrl}/broadcasting/auth`;
        options.auth = {
            headers: {
                Authorization: `Bearer ${token}`,
                'X-Requested-With': 'XMLHttpRequest',
            },
        };
    }

    options.withCredentials = true;
    if (!options.auth) options.auth = {};
    options.auth.withCredentials = true;

    if (isDev) {
         (Pusher as any).logToConsole = false; 
    }

    const echo = new Echo({
        ...options,
        disableStats: true,
    });

    const connector = echo.connector as any;
    if (connector?.pusher?.connection) {
        connector.pusher.connection.bind('error', (err: any) => {
            if (err.type === 'WebSocketError' || err?.error?.data?.code === 4004 || err?.error?.data?.code === 4005) {
                // suppress
            }
        });
        connector.pusher.connection.bind('state_change', (states: any) => {
            if (states.current === 'unavailable') {
                // handle unavailable
            }
        });
    }

    return echo;
};
