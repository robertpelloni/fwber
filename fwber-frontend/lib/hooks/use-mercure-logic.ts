import { usePusherLogic } from './use-pusher-logic';

// Re-export types from Pusher logic
export type {
  ConnectionStatus as MercureConnectionStatus, // Alias for compatibility
  Message as MercureMessage, // Alias for compatibility
  OnlineUser,
  PresenceUpdate,
  MessageStatus,
  ChatMessage,
  TypingIndicator,
  VideoSignal,
  NotificationPayload
} from './use-pusher-logic';

/**
 * @deprecated This hook is deprecated. Use usePusherLogic instead.
 * It is kept for backward compatibility during the migration from Mercure to Pusher.
 */
export function useMercureLogic(options: { autoConnect?: boolean } = {}) {
  return usePusherLogic(options);
}
