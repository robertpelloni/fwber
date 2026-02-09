'use client';

import { useEffect, useRef } from 'react';
import { useWebSocket } from '@/lib/hooks/use-websocket';
import { useToast } from '@/components/ToastProvider';
import { useRouter } from 'next/navigation';

export default function NotificationListener() {
  const { notifications } = useWebSocket();
  const { showMatch } = useToast();
  const router = useRouter();
  const lastNotificationRef = useRef<any>(null);

  useEffect(() => {
    if (notifications.length === 0) return;

    const latest = notifications[notifications.length - 1];
    
    // Simple check to avoid duplicate toasts for the same notification object reference
    if (latest !== lastNotificationRef.current) {
      lastNotificationRef.current = latest;

      // Handle Gift Received
      if (latest.type === 'gift_received') {
        // Use showMatch for the heart icon and pink color
        showMatch(
          `${latest.sender_name} sent you a ${latest.gift_name}!`,
          latest.message || 'Check your profile to see it.',
          {
            label: 'View Profile',
            onClick: () => router.push('/profile')
          }
        );
      }
      
      // Handle other types if needed
    }
  }, [notifications, showMatch, router]);

  return null;
}
