/**
 * Standalone toast utility for replacing native alert() calls.
 * 
 * Uses the browser Notification API as fallback if the React ToastProvider
 * is not available (e.g., outside component tree).
 * 
 * Per VISION.md: "Never use native alert() or confirm(). Use the established Toast system."
 */

type ToastType = 'success' | 'error' | 'info';

let toastCallback: ((type: ToastType, message: string) => void) | null = null;

/**
 * Register the global toast handler (called by ToastProvider on mount).
 */
export function registerToastHandler(handler: (type: ToastType, message: string) => void) {
  toastCallback = handler;
}

/**
 * Show a toast notification. Falls back to console if no handler registered.
 */
export function showToast(type: ToastType, message: string) {
  if (toastCallback) {
    toastCallback(type, message);
  } else {
    // Fallback: just log it (no native alert!)
    console.log(`[${type.toUpperCase()}] ${message}`);
  }
}

/**
 * Convenience: show success toast.
 */
export function toastSuccess(message: string) {
  showToast('success', message);
}

/**
 * Convenience: show error toast.
 */
export function toastError(message: string) {
  showToast('error', message);
}

/**
 * Convenience: show info toast.
 */
export function toastInfo(message: string) {
  showToast('info', message);
}
