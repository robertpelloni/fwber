import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getPublicApiOrigin } from '@/lib/api/client';
import { safeLocalStorageGet, safeLocalStorageSet } from '@/lib/browser-storage';

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
    const isFwberProductionHost = /(^|\.)fwber\.me$/i.test(window.location.hostname);
    const apiOrigin = getPublicApiOrigin();

    // Reverb app keys are public client identifiers. We still prefer environment-driven
    // configuration, but a production fallback keeps the live site from silently breaking
    // when frontend env drift causes the websocket key/host to disappear from Vercel.
    let appKey = process.env.NEXT_PUBLIC_REVERB_APP_KEY
        || process.env.NEXT_PUBLIC_PUSHER_APP_KEY
        || (isFwberProductionHost ? 'cbb98008592ac1b78cec' : 'app-key');

    if (appKey === 'your_app_key') {
        if (!safeLocalStorageGet('fwber_suppress_app_key_warn')) {
            console.warn('fwber: Detected placeholder "your_app_key". Falling back to a safe default.');
            safeLocalStorageSet('fwber_suppress_app_key_warn', 'true');
        }
        appKey = isFwberProductionHost ? 'cbb98008592ac1b78cec' : 'app-key';
    }

    const hasExplicitRealtimeConfig = Boolean(
        process.env.NEXT_PUBLIC_REVERB_HOST ||
        process.env.NEXT_PUBLIC_PUSHER_HOST ||
        process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER
    );

    if (!isDev && !hasExplicitRealtimeConfig && !isFwberProductionHost) {
        return null;
    }

    const defaultReverbHost = process.env.NEXT_PUBLIC_REVERB_HOST || (isFwberProductionHost ? 'ws.fwber.me' : undefined);
    const defaultReverbScheme = process.env.NEXT_PUBLIC_REVERB_SCHEME || (isFwberProductionHost ? 'https' : undefined);
    const defaultReverbPort = process.env.NEXT_PUBLIC_REVERB_PORT || ((defaultReverbScheme ?? 'https') === 'https' ? '443' : '80');

    const options: any = {
        broadcaster: 'reverb',
        key: appKey,
        wsHost: defaultReverbHost,
        wsPort: defaultReverbPort,
        wssPort: defaultReverbPort,
        forceTLS: (defaultReverbScheme ?? 'https') === 'https',
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
    } else if (!options.wsHost) {
        options.wsHost = 'ws.fwber.me';
        options.wsPort = 443;
        options.wssPort = 443;
        options.forceTLS = true;
    }

    options.authEndpoint = `${apiOrigin}/broadcasting/auth`;
    options.auth = {
        headers: token
            ? { Authorization: `Bearer ${token}` }
            : { 'X-Requested-With': 'XMLHttpRequest' },
    };

    options.withCredentials = true;
    options.auth.withCredentials = true;

    if (isDev) {
        (Pusher as any).logToConsole = false;
    }

    const echo = new Echo({
        ...options,
        disableStats: true,
        enabledTransports: ['ws', 'wss'],
    });

    if (isDev) {
        const connector = echo.connector as any;
        if (connector?.pusher?.connection) {
            connector.pusher.connection.bind('error', (err: any) => {
                if (err.type === 'WebSocketError' || err?.error?.data?.code === 4004 || err?.error?.data?.code === 4005) {
                    return;
                }
            });

            connector.pusher.connection.bind('state_change', (states: any) => {
                if (states.current === 'unavailable') {
                    return;
                }
            });
        }
    }

    return echo;
};
