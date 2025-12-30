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
    let appKey = process.env.NEXT_PUBLIC_PUSHER_APP_KEY || 'app-key';
    if (appKey === 'your_app_key') {
        // Only warn once to avoid spamming
        if (!window.localStorage.getItem('fwber_suppress_app_key_warn')) {
             console.warn('FWBer: Detected placeholder "your_app_key". Falling back to "app-key" for local development.');
             window.localStorage.setItem('fwber_suppress_app_key_warn', 'true');
        }
        appKey = 'app-key';
    }

    const options: any = {
        broadcaster: 'pusher',
        key: appKey,
        cluster: process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER || 'mt1',
        forceTLS: process.env.NEXT_PUBLIC_PUSHER_SCHEME === 'https',
        disableStats: true,
        enabledTransports: ['ws', 'wss'],
    };

    if (process.env.NEXT_PUBLIC_PUSHER_HOST) {
        options.wsHost = process.env.NEXT_PUBLIC_PUSHER_HOST;
        options.wsPort = process.env.NEXT_PUBLIC_PUSHER_PORT ? parseInt(process.env.NEXT_PUBLIC_PUSHER_PORT) : 80;
        options.wssPort = process.env.NEXT_PUBLIC_PUSHER_PORT ? parseInt(process.env.NEXT_PUBLIC_PUSHER_PORT) : 443;
    } else if (isDev) {
        // Local Reverb defaults
        options.wsHost = window.location.hostname;
        options.wsPort = 8080;
        options.wssPort = 8080;
        options.forceTLS = false;
    }

    if (token) {
        options.authEndpoint = `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`;
        options.auth = {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        };
    }

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
                if (err.type === 'WebSocketError' || err?.error?.data?.code === 4004) {
                    // silent
                } else {
                    console.error('Pusher connection error:', err);
                }
            });
            // Also bind to state change to catch 'unavailable' state without error
             connector.pusher.connection.bind('state_change', (states: any) => {
                if (states.current === 'unavailable') {
                    // silent
                }
            });
        }
        
        // Monkey patch console.error locally for this instance's context if we can isolate it
        // Or more aggressively, intercept the log mechanism if Pusher JS exposes it
        // Pusher JS doesn't use console.error for everything if logToConsole is false, but connection errors might bypass.
        // Actually, the log you see "Pusher connection error" is coming from OUR code above on line 83!
        // Wait, line 83 says console.error.
        // The error stack trace in the user prompt shows:
        // common-5616e786936ccf72.js:5031:80874
        // which implies it's coming from the compiled JS bundle.
        // If the stack trace points to our file (lib/echo.ts), then WE are logging it.
        
        // Let's remove our own console.error!
    }

    return echo;
};
