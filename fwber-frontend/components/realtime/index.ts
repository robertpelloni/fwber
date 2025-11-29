/**
 * Realtime Components
 * 
 * Unified presence, typing indicator, and connection status components
 * for WebSocket-powered real-time features.
 */

// Core presence components
export {
  PresenceProvider,
  PresenceIndicator,
  TypingIndicator,
  OnlineUsersList,
  ConnectionStatusBadge,
  useTypingNotifier,
  usePresenceContext,
} from './PresenceComponents';

// Types
export type {
  OnlineUser,
  TypingIndicatorData,
  WebSocketContextValue,
} from './PresenceComponents';

// WebSocket bridge for integration with existing WebSocketContext
export {
  WebSocketPresenceProvider,
  RealtimeProvider,
} from './WebSocketBridge';

// Proximity presence views
export {
  ProximityPresenceView,
  ProximityPresenceCompact,
} from './ProximityPresenceView';
