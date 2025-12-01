'use client';

import { useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
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
    const registerDeviceToken = async () => {
      try {
        const registration = await navigator.serviceWorker.getRegistration();
        if (registration && process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY) {
          const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY),
          });

          await api.post(
            '/notifications/subscribe',
            subscription.toJSON()
          );
        }
      } catch (error) {
        console.error('Error registering device token:', error);
      }
    };

    const requestNotificationPermission = async () => {
      if ('Notification' in window) {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
          registerDeviceToken();
        }
      }
    };

    if (isAuthenticated && token) {
      requestNotificationPermission();
    }
  }, [isAuthenticated, token]);

  return null;
}
