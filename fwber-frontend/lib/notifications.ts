export type NotificationRoutePayload = {
  type?: string
  data?: Record<string, unknown>
}

export type NormalizedNotificationType =
  | 'message'
  | 'match'
  | 'like'
  | 'friend_request'
  | 'view'
  | 'system'
  | 'gift'
  | 'event'

export function normalizeNotificationType(type?: string): NormalizedNotificationType {
  switch (type) {
    case 'message':
    case 'new_message':
    case 'NewMessageNotification':
      return 'message'
    case 'match':
    case 'new_match':
    case 'NewMatchNotification':
      return 'match'
    case 'like':
    case 'friend_request':
    case 'view':
    case 'gift':
    case 'event':
      return type
    default:
      return 'system'
  }
}

export function getNotificationRoute(notification: NotificationRoutePayload): string {
  const data = notification.data ?? {}
  const explicitUrl = typeof data.url === 'string' ? data.url : null

  if (explicitUrl) {
    return explicitUrl
  }

  const normalizedType = normalizeNotificationType(notification.type)

  switch (normalizedType) {
    case 'message': {
      const userId = data.user_id ?? data.sender_id
      return typeof userId === 'number' || typeof userId === 'string'
        ? `/messages?user=${userId}`
        : '/messages'
    }
    case 'match': {
      const userId = data.user_id ?? data.matched_user_id ?? data.target_user_id
      return typeof userId === 'number' || typeof userId === 'string'
        ? `/profile/${userId}`
        : '/matches'
    }
    case 'friend_request':
      return '/friends'
    case 'view':
      return typeof data.user_id === 'number' || typeof data.user_id === 'string'
        ? `/profile/${data.user_id}`
        : '/profile'
    case 'gift':
      return '/wallet?tab=gifts'
    case 'event':
      return typeof data.event_id === 'number' || typeof data.event_id === 'string'
        ? `/events/${data.event_id}`
        : '/events'
    default:
      return '#'
  }
}

export function getNotificationActionLabel(notification: NotificationRoutePayload): string {
  const normalizedType = normalizeNotificationType(notification.type)

  switch (normalizedType) {
    case 'message':
      return 'Reply'
    case 'match':
      return 'View Profile'
    case 'friend_request':
      return 'Open Friends'
    case 'view':
      return 'View Profile'
    default:
      return 'Open'
  }
}
