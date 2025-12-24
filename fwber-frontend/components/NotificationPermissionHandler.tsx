'use client';

import { useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import axios from 'axios';
import { api } from '@/lib/api/client';

function urlBase64ToUint8Array(base64String: string) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

export default function NotificationPermissionHandler() {
  const { isAuthenticated, token } = useAuth();

  useEffect(() => {
    // Only run if authenticated
    if (!isAuthenticated || !token) return;

    // Check if browser supports notifications
    if (!('Notification' in window)) return;

    // Helper to register the token with the backend
    const registerWithBackend = async (subscription: PushSubscription) => {
        try {
            await api.post(
                '/notifications/subscribe',
                subscription.toJSON()
            );
            console.log('Push notification subscription active');
        } catch (error) {
            console.error('Failed to sync subscription with backend:', error);
        }
    };

    // Main registration logic
    const initializeNotifications = async () => {
        try {
            // Get SW registration
            const registration = await navigator.serviceWorker.ready;

            // Check existing subscription
            let subscription = await registration.pushManager.getSubscription();

            // If permission is already granted but no subscription, create one
            if (!subscription && Notification.permission === 'granted' && process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY),
                });
            }

            // Sync with backend if we have a subscription
            if (subscription) {
                await registerWithBackend(subscription);
            } else if (Notification.permission === 'default') {
                // If default, we can prompt (or show a UI to prompt)
                // For now, we'll silently wait for user action or a better prompt time
                // to avoid annoying popups on load.
            }

        } catch (error) {
            console.error('Error initializing notifications:', error);
        }
    };

    initializeNotifications();

  }, [isAuthenticated, token]);

  return null;
}
