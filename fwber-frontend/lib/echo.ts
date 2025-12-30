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
    // However, the issue is that env vars are baked in at build time.
    
    // Default config handles local dev (Reverb on localhost:8080) well. 
    // BUT for production, we need to respect the environment variables provided there.
    // If they are missing in production, falling back to 127.0.0.1 is wrong if the user is visiting the site.
    
    const isDev = process.env.NODE_ENV === 'development';
    
    // Handle the case where the user has 'your_app_key' in their .env file (common copy-paste error)
    let appKey = process.env.NEXT_PUBLIC_PUSHER_APP_KEY || 'app-key';
    if (appKey === 'your_app_key') {
        console.warn('FWBer: Detected placeholder "your_app_key" in environment variables. Falling back to "app-key" for local development.');
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

    // Only set wsHost/wsPort if they are explicitly provided in the environment
    // OR if we are in development mode and want to default to local Reverb.
    // If we are in production and these are missing, we should NOT set them (letting Pusher default to its cloud servers)
    // UNLESS we are self-hosting Reverb in production too.
    
    if (process.env.NEXT_PUBLIC_PUSHER_HOST) {
        options.wsHost = process.env.NEXT_PUBLIC_PUSHER_HOST;
        options.wsPort = process.env.NEXT_PUBLIC_PUSHER_PORT ? parseInt(process.env.NEXT_PUBLIC_PUSHER_PORT) : 80;
        options.wssPort = process.env.NEXT_PUBLIC_PUSHER_PORT ? parseInt(process.env.NEXT_PUBLIC_PUSHER_PORT) : 443;
    } else if (isDev) {
        // Local Reverb defaults
        // Use window.location.hostname to allow testing on local network devices (e.g. mobile)
        // assuming the Reverb server is reachable on the same host as the frontend
        options.wsHost = window.location.hostname;
        options.wsPort = 8080;
        options.wssPort = 8080;
        options.forceTLS = false;
    } else {
        // Production fallback if ENV vars are missing
        // Since we are on shared hosting without a dedicated WebSocket server,
        // we likely want to default to Pusher Cloud unless explicitly told otherwise.
        // However, if the user intends to use Reverb on a different port, they MUST set the env vars.
    }

    if (token) {
        options.authEndpoint = `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`;
        options.auth = {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        };
    }

    // Fix for Pusher connection error by explicitly disabling stats
    if (process.env.NODE_ENV === 'development') {
         // @ts-ignore
         Pusher.logToConsole = true;
    }

    return new Echo({
        ...options,
        disableStats: true,
        enabledTransports: ['ws', 'wss'],
    });
};
