'use client';

import { useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import axios from 'axios';

export default function NotificationPermissionHandler() {
  const { isAuthenticated, token } = useAuth();

  useEffect(() => {
    const registerDeviceToken = async () => {
      try {
        const registration = await navigator.serviceWorker.getRegistration();
        if (registration) {
          const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY,
          });

          await axios.post(
            `${process.env.NEXT_PUBLIC_API_URL}/device-tokens`,
            {
              token: subscription.endpoint,
              type: 'web',
            },
            {
              headers: { Authorization: `Bearer ${token}` },
            }
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
