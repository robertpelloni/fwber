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

    // If we're in production on fwber.me, we likely want to use real Pusher or a properly configured Reverb
    // For now, if NEXT_PUBLIC_PUSHER_HOST is not explicitly set in env, let's NOT default to 127.0.0.1 if we are not in development.
    
    const isDev = process.env.NODE_ENV === 'development';
    
    // Handle the case where the user has 'your_app_key' in their .env file (common copy-paste error)
    let appKey = process.env.NEXT_PUBLIC_REVERB_APP_KEY || process.env.NEXT_PUBLIC_PUSHER_APP_KEY || 'app-key';
    if (appKey === 'your_app_key') {
        // Only warn once to avoid spamming
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
        // Local Reverb defaults
        if (!options.wsHost) options.wsHost = window.location.hostname;
        if (!options.wsPort) options.wsPort = 8080;
        if (!options.wssPort) options.wssPort = 8080;
        if (!options.forceTLS) options.forceTLS = false;
    } else {
        // Production defaults if no host specified (assume standard Pusher)
        if (!options.wsHost) { // Fallback to pusher if Reverb vars missing
             options.broadcaster = 'pusher';
             options.cluster = process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER || 'mt1';
             options.forceTLS = true;
        }
    }

    if (token) {
        options.authEndpoint = `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`;
        options.auth = {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        };
    } else {
        options.authEndpoint = `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`;
        options.auth = {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        };
    }

    // Always ensure credentials are sent for Sanctum cookie auth
    options.withCredentials = true;
    // Ensure auth options also carry the flag if the library expects it there
    if (!options.auth) options.auth = {};
    options.auth.withCredentials = true;

    if (isDev) {
         // @ts-ignore
         Pusher.logToConsole = false; 
    }

    const echo = new Echo({
        ...options,
        disableStats: true,
        enabledTransports: ['ws', 'wss'],
    });

    // Suppress connection errors in development if Reverb is not running
    if (isDev) {
        const connector = echo.connector as any;
        if (connector?.pusher?.connection) {
            // Bind before any other listeners might trigger
            connector.pusher.connection.bind('error', (err: any) => {
                // If it's a connection refused/timeout error (WebSocketError), suppress it in dev
                // because it just means the developer hasn't started 'php artisan reverb:start'
                if (err.type === 'WebSocketError' || err?.error?.data?.code === 4004 || err?.error?.data?.code === 4005) {
                    // silent
                } else {
                    // console.error('Pusher connection error:', err);
                }
            });
            // Also bind to state change to catch 'unavailable' state without error
             connector.pusher.connection.bind('state_change', (states: any) => {
                if (states.current === 'unavailable') {
                    // silent
                }
            });
        }
    }

    return echo;
};
