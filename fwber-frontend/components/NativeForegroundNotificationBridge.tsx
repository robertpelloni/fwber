'use client'

import { useEffect, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { useToast } from '@/components/ToastProvider'
import { getNotificationActionLabel, getNotificationRoute, normalizeNotificationType } from '@/lib/notifications'

interface NativeNotificationPayload {
  title?: string
  body?: string
  data?: {
    type?: string
    url?: string
    [key: string]: unknown
  }
  receivedAt?: string
}

/**
 * Bridges native Expo foreground notifications into the web UI toast system.
 *
 * Why this exists:
 * - the mobile shell already receives push notifications even while the app is open
 * - without a bridge, those events are logged in React Native but invisible inside the web app
 * - by translating native pushes into the existing toast system, foreground mobile sessions
 *   feel immediate and actionable without forcing the user into the OS notification tray
 */
export default function NativeForegroundNotificationBridge() {
  const router = useRouter()
  const { showInfo, showMatch, showMessage } = useToast()
  const lastNotificationKeyRef = useRef<string | null>(null)

  useEffect(() => {
    const handleNativeNotification = (event: Event) => {
      const detail = (event as CustomEvent<NativeNotificationPayload>).detail
      if (!detail) {
        return
      }

      const title = detail.title?.trim() || 'New activity'
      const body = detail.body?.trim() || 'Open fwber to view the latest update.'
      const notificationType = typeof detail.data?.type === 'string' ? detail.data.type : 'system'
      const targetUrl = getNotificationRoute({ type: notificationType, data: detail.data })
      const notificationKey = JSON.stringify({ title, body, targetUrl, notificationType, receivedAt: detail.receivedAt })

      if (notificationKey === lastNotificationKeyRef.current) {
        return
      }

      lastNotificationKeyRef.current = notificationKey

      const action = targetUrl !== '#'
        ? {
            label: getNotificationActionLabel({ type: notificationType, data: detail.data }),
            onClick: () => router.push(targetUrl),
          }
        : undefined

      const normalizedType = normalizeNotificationType(notificationType)

      if (normalizedType === 'match') {
        showMatch(title, body, action)
        return
      }

      if (normalizedType === 'message') {
        showMessage(title, body, action)
        return
      }

      showInfo(title, body)
    }

    window.addEventListener('fwber:native-notification', handleNativeNotification as EventListener)

    return () => {
      window.removeEventListener('fwber:native-notification', handleNativeNotification as EventListener)
    }
  }, [router, showInfo, showMatch, showMessage])

  return null
}
