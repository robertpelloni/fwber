'use client';

import { useEffect, useRef } from 'react';
import { useWebSocket } from '@/lib/hooks/use-websocket';
import { useToast } from '@/components/ToastProvider';
import { useRouter } from 'next/navigation';

// Maps notification types to display logic
const NOTIFICATION_HANDLERS: Record<string, {
  getTitle: (n: any) => string;
  getMessage: (n: any) => string;
  getAction: (n: any, router: ReturnType<typeof useRouter>) => { label: string; onClick: () => void } | undefined;
}> = {
  // ── Social ────────────────────────────────────
  new_match: {
    getTitle: () => 'New Match! 🔥',
    getMessage: (n) => n.message || 'You have a new match!',
    getAction: (n, router) => ({
      label: 'View Match',
      onClick: () => router.push('/messages'),
    }),
  },
  new_message: {
    getTitle: (n) => `Message from ${n.sender_name || 'someone'}`,
    getMessage: (n) => n.message || 'You have a new message',
    getAction: (n, router) => ({
      label: 'Open Chat',
      onClick: () => router.push('/messages'),
    }),
  },
  gift_received: {
    getTitle: (n) => `${n.sender_name} sent you a ${n.gift_name}!`,
    getMessage: (n) => n.message || 'Check your profile to see it.',
    getAction: (_, router) => ({
      label: 'View Profile',
      onClick: () => router.push('/profile'),
    }),
  },
  group_match_request: {
    getTitle: () => 'Group Match Request',
    getMessage: (n) => n.message || 'Someone wants to connect your groups!',
    getAction: (_, router) => ({
      label: 'View Requests',
      onClick: () => router.push('/scenes'),
    }),
  },
  event_invitation: {
    getTitle: () => 'Event Invitation 📅',
    getMessage: (n) => n.message || 'You\'re invited to an event!',
    getAction: (_, router) => ({
      label: 'View Event',
      onClick: () => router.push('/plans'),
    }),
  },
  federated_follow: {
    getTitle: (n) => `New Follower`,
    getMessage: (n) => n.message || 'Someone started following you.',
    getAction: () => undefined,
  },

  // ── Engagement ────────────────────────────────
  relationship_tier_upgraded: {
    getTitle: () => 'Relationship Upgraded! ⬆️',
    getMessage: (n) => n.message || 'Your relationship tier has increased!',
    getAction: (_, router) => ({
      label: 'View Matches',
      onClick: () => router.push('/matching'),
    }),
  },
  photo_unlocked: {
    getTitle: () => 'Photo Unlocked! 📸',
    getMessage: (n) => n.message || 'A photo has been unlocked!',
    getAction: (_, router) => ({
      label: 'View Photos',
      onClick: () => router.push('/profile'),
    }),
  },
  achievement_unlocked: {
    getTitle: () => 'Achievement Unlocked! 🏆',
    getMessage: (n) => n.message || 'You earned a new achievement!',
    getAction: (_, router) => ({
      label: 'View Achievements',
      onClick: () => router.push('/reputation'),
    }),
  },
  streak_updated: {
    getTitle: () => 'Daily Streak! 🔥',
    getMessage: (n) => n.message || 'Your streak continues!',
    getAction: () => undefined,
  },

  // ── Account ───────────────────────────────────
  payment_failed: {
    getTitle: () => 'Payment Issue ⚠️',
    getMessage: (n) => n.message || 'A payment could not be processed.',
    getAction: (_, router) => ({
      label: 'Manage Subscription',
      onClick: () => router.push('/settings/subscription'),
    }),
  },
  subscription_expired: {
    getTitle: () => 'Subscription Expired',
    getMessage: (n) => n.message || 'Your premium subscription has expired.',
    getAction: (_, router) => ({
      label: 'Renew',
      onClick: () => router.push('/premium'),
    }),
  },
  data_export_completed: {
    getTitle: () => 'Data Export Ready',
    getMessage: (n) => n.message || 'Your data export is ready to download.',
    getAction: (_, router) => ({
      label: 'Download',
      onClick: () => router.push('/settings/account'),
    }),
  },
  event_reminder: {
    getTitle: () => 'Event Reminder 📅',
    getMessage: (n) => n.message || 'An event is starting soon!',
    getAction: (_, router) => ({
      label: 'View Event',
      onClick: () => router.push('/plans'),
    }),
  },
};

export default function NotificationListener() {
  const { notifications } = useWebSocket();
  const { showMatch, showSuccess, showError } = useToast();
  const router = useRouter();
  const shownIds = useRef(new Set<string>());

  useEffect(() => {
    if (notifications.length === 0) return;

    const latest = notifications[notifications.length - 1];

    // Deduplicate by id or by reference
    const notifId = latest.id || latest.type + '_' + (latest.created_at || Date.now());
    if (shownIds.current.has(notifId)) return;
    shownIds.current.add(notifId);

    // Keep set from growing unbounded (max 100)
    if (shownIds.current.size > 100) {
      const arr = Array.from(shownIds.current);
      shownIds.current = new Set(arr.slice(-50));
    }

    // Find handler
    const handler = NOTIFICATION_HANDLERS[latest.type];

    if (handler) {
      const title = handler.getTitle(latest);
      const message = handler.getMessage(latest);
      const action = handler.getAction(latest, router);

      showMatch(title, message, action || {});
    } else {
      // Generic fallback for untyped notifications
      const title = latest.title || 'Notification';
      const message = latest.message || 'You have a new notification.';
      showSuccess(title, message);
    }
  }, [notifications, showMatch, showSuccess, showError, router]);

  return null;
}
