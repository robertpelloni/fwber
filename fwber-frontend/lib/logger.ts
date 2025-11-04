/**
 * Centralized logging utility for fwber frontend
 * Logs to console in development, sends to Sentry in production
 */

import * as Sentry from '@sentry/nextjs';

type LogLevel = 'debug' | 'info' | 'warn' | 'error';
type LogContext = 'auth' | 'websocket' | 'location' | 'api' | 'ui' | 'general';

interface LogMetadata {
  context: LogContext;
  level: LogLevel;
  message: string;
  data?: any;
  userId?: string | number;
  timestamp: string;
}

const isDevelopment = process.env.NODE_ENV === 'development';

/**
 * Core logging function
 */
function log(
  context: LogContext,
  level: LogLevel,
  message: string,
  data?: any
): void {
  const metadata: LogMetadata = {
    context,
    level,
    message,
    data,
    timestamp: new Date().toISOString(),
  };

  // Always log to console
  const consoleMessage = `[${context.toUpperCase()}] ${message}`;
  
  switch (level) {
    case 'debug':
      if (isDevelopment) console.debug(consoleMessage, data);
      break;
    case 'info':
      console.info(consoleMessage, data);
      break;
    case 'warn':
      console.warn(consoleMessage, data);
      // Send warnings to Sentry with breadcrumb
      Sentry.addBreadcrumb({
        category: context,
        message,
        level: 'warning',
        data,
      });
      break;
    case 'error':
      console.error(consoleMessage, data);
      // Send errors to Sentry
      Sentry.captureException(new Error(message), {
        level: 'error',
        tags: { context },
        extra: data,
      });
      break;
  }
}

/**
 * Auth-related logging
 */
export const logAuth = {
  login: (email: string, success: boolean, error?: any) => {
    if (success) {
      log('auth', 'info', 'User login successful', { email });
    } else {
      log('auth', 'error', 'User login failed', { email, error });
    }
  },

  register: (email: string, success: boolean, error?: any) => {
    if (success) {
      log('auth', 'info', 'User registration successful', { email });
    } else {
      log('auth', 'error', 'User registration failed', { email, error });
    }
  },

  logout: (userId?: string | number) => {
    log('auth', 'info', 'User logged out', { userId });
  },

  tokenRefresh: (success: boolean, error?: any) => {
    if (success) {
      log('auth', 'debug', 'Token refreshed successfully');
    } else {
      log('auth', 'warn', 'Token refresh failed', { error });
    }
  },

  sessionRestored: (userId: string | number) => {
    log('auth', 'info', 'Session restored from localStorage', { userId });
  },
};

/**
 * WebSocket-related logging
 */
export const logWebSocket = {
  connected: (connectionId?: string) => {
    log('websocket', 'info', 'WebSocket connected', { connectionId });
  },

  disconnected: (reason?: string) => {
    log('websocket', 'warn', 'WebSocket disconnected', { reason });
  },

  reconnecting: (attempt: number, maxAttempts: number, delay?: number) => {
    log('websocket', 'info', 'WebSocket reconnecting', { attempt, maxAttempts, delay });
  },

  reconnectFailed: (attempts: number) => {
    log('websocket', 'error', 'WebSocket reconnection failed after max attempts', { attempts });
  },

  messageSent: (type: string, recipientId?: string, messageId?: string) => {
    log('websocket', 'debug', 'Message sent', { type, recipientId, messageId });
  },

  messageReceived: (type: string, senderId?: string) => {
    log('websocket', 'debug', 'Message received', { type, senderId });
  },

  messageQueued: (messageId: string, queueSize: number) => {
    log('websocket', 'debug', 'Message queued (offline)', { messageId, queueSize });
  },

  messageAcknowledged: (messageId: string) => {
    log('websocket', 'debug', 'Message acknowledged', { messageId });
  },

  messageRetry: (messageId: string, retries: number) => {
    log('websocket', 'warn', 'Message retry attempt', { messageId, retries });
  },

  messageFailed: (messageId: string, retries: number) => {
    log('websocket', 'error', 'Message delivery failed after retries', { messageId, retries });
  },

  error: (error: any, context?: string) => {
    log('websocket', 'error', 'WebSocket error', { error, context });
  },

  heartbeatSent: () => {
    log('websocket', 'debug', 'Heartbeat ping sent');
  },

  heartbeatReceived: () => {
    log('websocket', 'debug', 'Heartbeat pong received');
  },

  heartbeatTimeout: (timeSinceLastResponse?: number) => {
    log('websocket', 'warn', 'Heartbeat timeout - forcing reconnection', { timeSinceLastResponse });
  },
};

/**
 * Location-related logging
 */
export const logLocation = {
  permissionGranted: (coords: { latitude: number; longitude: number }) => {
    log('location', 'info', 'Location permission granted', { coords });
  },

  permissionDenied: (error: any) => {
    log('location', 'warn', 'Location permission denied', { error });
  },

  updated: (coords: { latitude: number; longitude: number }) => {
    log('location', 'debug', 'Location updated', { coords });
  },

  error: (error: any) => {
    log('location', 'error', 'Location error', { error });
  },
};

/**
 * API-related logging
 */
export const logAPI = {
  request: (method: string, url: string, data?: any) => {
    log('api', 'debug', `API ${method} request`, { url, data });
  },

  response: (method: string, url: string, status: number, data?: any) => {
    if (status >= 400) {
      log('api', 'error', `API ${method} error ${status}`, { url, status, data });
    } else {
      log('api', 'debug', `API ${method} response ${status}`, { url, status });
    }
  },

  success: (message: string, data?: any) => {
    log('api', 'info', message, data);
  },

  warning: (message: string, data?: any) => {
    log('api', 'warn', message, data);
  },

  error: (method: string, url: string, error: any) => {
    log('api', 'error', `API ${method} failed`, { url, error });
  },
};

/**
 * UI-related logging
 */
export const logUI = {
  pageView: (path: string, userId?: string | number) => {
    log('ui', 'debug', 'Page view', { path, userId });
  },

  error: (componentName: string, error: any) => {
    log('ui', 'error', `Component error: ${componentName}`, { error });
  },

  interaction: (action: string, element: string, data?: any) => {
    log('ui', 'debug', `User interaction: ${action}`, { element, data });
  },
};

/**
 * General logging
 */
export const logger = {
  debug: (message: string, data?: any) => log('general', 'debug', message, data),
  info: (message: string, data?: any) => log('general', 'info', message, data),
  warn: (message: string, data?: any) => log('general', 'warn', message, data),
  error: (message: string, data?: any) => log('general', 'error', message, data),
};

/**
 * Set user context for Sentry
 */
export const setUserContext = (user: { id: string | number; email: string; name?: string }) => {
  Sentry.setUser({
    id: String(user.id),
    email: user.email,
    username: user.name,
  });
};

/**
 * Clear user context (on logout)
 */
export const clearUserContext = () => {
  Sentry.setUser(null);
};

/**
 * Add custom breadcrumb
 */
export const addBreadcrumb = (
  category: string,
  message: string,
  data?: any,
  level: 'debug' | 'info' | 'warning' | 'error' = 'info'
) => {
  Sentry.addBreadcrumb({
    category,
    message,
    level,
    data,
    timestamp: Date.now() / 1000,
  });
};
