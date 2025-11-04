/**
 * Shared API client utilities
 * This module provides a centralized way to make authenticated API calls.
 */

const BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export interface RequestOptions extends RequestInit {
  params?: Record<string, string | number | boolean | undefined>;
  body?: string; // Allow body on DELETE requests
  retry?: number; // Number of retry attempts
  retryDelay?: number; // Delay between retries in ms
}

/**
 * Custom API Error class with detailed information
 */
export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public statusText: string,
    public data?: any
  ) {
    super(message);
    this.name = 'ApiError';
  }

  get isAuthError(): boolean {
    return this.status === 401 || this.status === 403;
  }

  get isNotFound(): boolean {
    return this.status === 404;
  }

  get isValidationError(): boolean {
    return this.status === 422;
  }

  get isServerError(): boolean {
    return this.status >= 500;
  }

  get isClientError(): boolean {
    return this.status >= 400 && this.status < 500;
  }
}

/**
 * Network error for when fetch itself fails
 */
export class NetworkError extends Error {
  constructor(message: string = 'Network request failed. Please check your connection.') {
    super(message);
    this.name = 'NetworkError';
  }
}

/**
 * Get authorization headers from localStorage
 */
function getAuthHeaders(): Record<string, string> {
  const token = typeof window !== 'undefined' ? localStorage.getItem('fwber_token') : null;
  return token ? { Authorization: `Bearer ${token}` } : {};
}

/**
 * Delay helper for retries
 */
function delay(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Make an authenticated API request with retry logic
 */
async function request<T>(
  endpoint: string,
  options: RequestOptions = {}
): Promise<{ data: T; status: number }> {
  const {
    params,
    headers,
    retry = 0,
    retryDelay = 1000,
    ...fetchOptions
  } = options;

  // Build URL with query parameters
  let url = `${BASE_URL}${endpoint}`;
  if (params) {
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        searchParams.append(key, String(value));
      }
    });
    const queryString = searchParams.toString();
    if (queryString) {
      url += `?${queryString}`;
    }
  }

  // Merge headers
  const finalHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...getAuthHeaders(),
    ...(headers as Record<string, string>),
  };

  let lastError: Error | null = null;
  const maxAttempts = retry + 1;

  for (let attempt = 0; attempt < maxAttempts; attempt++) {
    try {
      // Make request
      const response = await fetch(url, {
        ...fetchOptions,
        headers: finalHeaders,
      });

      // Parse response - handle empty responses
      let data: any;
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        data = await response.json();
      } else {
        const text = await response.text();
        data = text ? { message: text } : {};
      }

      if (!response.ok) {
        const errorMessage = data.message || data.error || `Request failed with status ${response.status}`;
        const apiError = new ApiError(
          errorMessage,
          response.status,
          response.statusText,
          data
        );

        // Don't retry client errors (except 429 rate limit) or auth errors
        if ((apiError.isClientError && response.status !== 429) || apiError.isAuthError) {
          throw apiError;
        }

        // For server errors or rate limits, retry
        lastError = apiError;
        if (attempt < maxAttempts - 1) {
          await delay(retryDelay * Math.pow(2, attempt)); // Exponential backoff
          continue;
        }
        throw apiError;
      }

      return { data, status: response.status };

    } catch (error) {
      // Network errors (fetch failed)
      if (error instanceof TypeError || error.message === 'Failed to fetch') {
        const networkError = new NetworkError();
        lastError = networkError;

        // Retry network errors
        if (attempt < maxAttempts - 1) {
          await delay(retryDelay * Math.pow(2, attempt));
          continue;
        }
        throw networkError;
      }

      // Re-throw ApiError and other errors
      throw error;
    }
  }

  // Should never reach here, but TypeScript needs it
  throw lastError || new Error('Request failed after all retries');
}

/**
 * API client with common HTTP methods
 */
export const apiClient = {
  get: <T>(endpoint: string, options?: RequestOptions) =>
    request<T>(endpoint, { ...options, method: 'GET', retry: options?.retry ?? 2 }),

  post: <T>(endpoint: string, body?: any, options?: RequestOptions) =>
    request<T>(endpoint, {
      ...options,
      method: 'POST',
      body: body ? JSON.stringify(body) : undefined,
      retry: options?.retry ?? 1, // POST requests get 1 retry by default
    }),

  put: <T>(endpoint: string, body?: any, options?: RequestOptions) =>
    request<T>(endpoint, {
      ...options,
      method: 'PUT',
      body: body ? JSON.stringify(body) : undefined,
      retry: options?.retry ?? 1,
    }),

  patch: <T>(endpoint: string, body?: any, options?: RequestOptions) =>
    request<T>(endpoint, {
      ...options,
      method: 'PATCH',
      body: body ? JSON.stringify(body) : undefined,
      retry: options?.retry ?? 1,
    }),

  delete: <T>(endpoint: string, options?: RequestOptions) =>
    request<T>(endpoint, { ...options, method: 'DELETE', retry: options?.retry ?? 0 }),
};

/**
 * Check if error is a specific type of API error
 */
export function isApiError(error: unknown): error is ApiError {
  return error instanceof ApiError;
}

export function isNetworkError(error: unknown): error is NetworkError {
  return error instanceof NetworkError;
}

/**
 * Get user-friendly error message from error
 */
export function getErrorMessage(error: unknown): string {
  if (isApiError(error)) {
    return error.message;
  }
  if (isNetworkError(error)) {
    return error.message;
  }
  if (error instanceof Error) {
    return error.message;
  }
  return 'An unexpected error occurred';
}
