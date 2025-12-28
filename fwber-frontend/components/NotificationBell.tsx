'use client';

import { useState, useEffect, useCallback } from 'react';
import Link from 'next/link';
import { Bell, X, Check, MessageSquare, Heart, UserPlus, Eye, AlertCircle } from 'lucide-react';
import { useAuth } from '@/lib/auth-context';
import { PresenceIndicator } from '@/components/realtime/PresenceComponents';

interface Notification {
  id: string;
  type: 'message' | 'match' | 'like' | 'friend_request' | 'view' | 'system';
  title: string;
  body: string;
  timestamp: string;
  read: boolean;
  data?: {
    user_id?: number;
    user_name?: string;
    match_id?: number;
    conversation_id?: number;
  };
}

interface NotificationBellProps {
  className?: string;
}

export function NotificationBell({ className = '' }: NotificationBellProps) {
  const { token } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isLoading, setIsLoading] = useState(false);

  // Fetch notifications
  const fetchNotifications = useCallback(async () => {
    if (!token) return;

    setIsLoading(true);
    try {
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/notifications`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (response.ok) {
        const data = await response.json();
        setNotifications(data.notifications || []);
        setUnreadCount(data.unread_count || 0);
      }
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
    } finally {
      setIsLoading(false);
    }
  }, [token]);

  // Mark notification as read
  const markAsRead = async (notificationId: string) => {
    if (!token) return;

    try {
      await fetch(`${process.env.NEXT_PUBLIC_API_URL}/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
      });
      setNotifications(prev =>
        prev.map(n => (n.id === notificationId ? { ...n, read: true } : n))
      );
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
    }
  };

  // Mark all as read
  const markAllAsRead = async () => {
    if (!token) return;

    try {
      await fetch(`${process.env.NEXT_PUBLIC_API_URL}/notifications/read-all`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
      });
      setNotifications(prev => prev.map(n => ({ ...n, read: true })));
      setUnreadCount(0);
    } catch (error) {
      console.error('Failed to mark all as read:', error);
    }
  };

  // Fetch on mount and periodically
  useEffect(() => {
    fetchNotifications();
    const interval = setInterval(fetchNotifications, 30000); // Refresh every 30s
    return () => clearInterval(interval);
  }, [fetchNotifications]);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      const target = e.target as HTMLElement;
      if (!target.closest('.notification-bell')) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('click', handleClickOutside);
      return () => document.removeEventListener('click', handleClickOutside);
    }
  }, [isOpen]);

  const getNotificationIcon = (type: Notification['type']) => {
    switch (type) {
      case 'message':
        return <MessageSquare className="h-4 w-4 text-blue-500" />;
      case 'match':
        return <Heart className="h-4 w-4 text-pink-500" />;
      case 'like':
        return <Heart className="h-4 w-4 text-red-500" />;
      case 'friend_request':
        return <UserPlus className="h-4 w-4 text-green-500" />;
      case 'view':
        return <Eye className="h-4 w-4 text-purple-500" />;
      case 'system':
        return <AlertCircle className="h-4 w-4 text-yellow-500" />;
      default:
        return <Bell className="h-4 w-4 text-gray-500" />;
    }
  };

  const getNotificationLink = (notification: Notification): string => {
    switch (notification.type) {
      case 'message':
        return `/messages?user=${notification.data?.user_id}`;
      case 'match':
        return `/matches`;
      case 'friend_request':
        return `/friends`;
      case 'view':
        return `/profile/${notification.data?.user_id}`;
      default:
        return '#';
    }
  };

  const formatTime = (timestamp: string): string => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
  };

  return (
    <div className={`notification-bell relative ${className}`}>
      {/* Bell Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2 rounded-full hover:bg-gray-100 transition-colors"
        aria-label="Notifications"
      >
        <Bell className="h-6 w-6 text-gray-600" />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white ring-2 ring-white">
            {unreadCount > 9 ? '9+' : unreadCount}
          </span>
        )}
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div className="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
            <h3 className="font-semibold text-gray-900">Notifications</h3>
            <div className="flex items-center gap-2">
              {unreadCount > 0 && (
                <button
                  onClick={markAllAsRead}
                  className="text-xs text-blue-600 hover:text-blue-800 font-medium"
                >
                  Mark all read
                </button>
              )}
              <button
                onClick={() => setIsOpen(false)}
                className="p-1 rounded hover:bg-gray-200"
                aria-label="Close notifications"
                title="Close"
              >
                <X className="h-4 w-4 text-gray-500" />
              </button>
            </div>
          </div>

          {/* Notifications List */}
          <div className="max-h-96 overflow-y-auto">
            {isLoading ? (
              <div className="flex items-center justify-center py-8">
                <div className="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent" />
              </div>
            ) : notifications.length === 0 ? (
              <div className="text-center py-8">
                <Bell className="mx-auto h-10 w-10 text-gray-300 mb-2" />
                <p className="text-gray-500 text-sm">No notifications yet</p>
              </div>
            ) : (
              <div className="divide-y divide-gray-100">
                {notifications.map((notification) => (
                  <Link
                    key={notification.id}
                    href={getNotificationLink(notification)}
                    onClick={() => {
                      if (!notification.read) markAsRead(notification.id);
                      setIsOpen(false);
                    }}
                    className={`flex items-start gap-3 p-4 hover:bg-gray-50 transition-colors ${
                      !notification.read ? 'bg-blue-50' : ''
                    }`}
                  >
                    {/* Icon */}
                    <div className="flex-shrink-0 mt-0.5">
                      {notification.data?.user_id ? (
                        <div className="relative">
                          <div className="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-medium">
                            {notification.data.user_name?.charAt(0).toUpperCase() || '?'}
                          </div>
                          <div className="absolute -bottom-0.5 -right-0.5">
                            <PresenceIndicator
                              userId={String(notification.data.user_id)}
                              size="sm"
                            />
                          </div>
                        </div>
                      ) : (
                        <div className="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                          {getNotificationIcon(notification.type)}
                        </div>
                      )}
                    </div>

                    {/* Content */}
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-gray-900 truncate">
                        {notification.title}
                      </p>
                      <p className="text-sm text-gray-500 line-clamp-2">
                        {notification.body}
                      </p>
                      <p className="text-xs text-gray-400 mt-1">
                        {formatTime(notification.timestamp)}
                      </p>
                    </div>

                    {/* Unread indicator */}
                    {!notification.read && (
                      <div className="flex-shrink-0">
                        <div className="w-2 h-2 bg-blue-500 rounded-full" />
                      </div>
                    )}
                  </Link>
                ))}
              </div>
            )}
          </div>

          {/* Footer */}
          {notifications.length > 0 && (
            <div className="border-t border-gray-100 px-4 py-3 bg-gray-50">
              <Link
                href="/notifications"
                onClick={() => setIsOpen(false)}
                className="text-sm text-blue-600 hover:text-blue-800 font-medium"
              >
                View all notifications
              </Link>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

/**
 * Compact notification indicator for mobile/small spaces
 */
export function NotificationDot({ className = '' }: { className?: string }) {
  const { token } = useAuth();
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    if (!token) return;

    const fetchCount = async () => {
      try {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/notifications/count`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        if (response.ok) {
          const data = await response.json();
          setUnreadCount(data.unread_count || 0);
        }
      } catch (error) {
        console.error('Failed to fetch notification count:', error);
      }
    };

    fetchCount();
    const interval = setInterval(fetchCount, 30000);
    return () => clearInterval(interval);
  }, [token]);

  if (unreadCount === 0) return null;

  return (
    <span className={`flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white ${className}`}>
      {unreadCount > 9 ? '9+' : unreadCount}
    </span>
  );
}
