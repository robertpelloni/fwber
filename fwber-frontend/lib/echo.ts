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

    const options: any = {
        broadcaster: 'pusher',
        key: process.env.NEXT_PUBLIC_PUSHER_APP_KEY,
        cluster: process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER,
        forceTLS: true,
    };

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
