import { logAPI } from './logger';

export interface ApiError {
  message: string;
  status?: number;
  code?: string;
  details?: any;
  timestamp: string;
}

export interface RetryConfig {
  maxRetries?: number;
  retryDelay?: number;
  retryableStatuses?: number[];
  exponentialBackoff?: boolean;
}

const DEFAULT_RETRY_CONFIG: Required<RetryConfig> = {
  maxRetries: 3,
  retryDelay: 1000, // 1 second
  retryableStatuses: [408, 429, 500, 502, 503, 504],
  exponentialBackoff: true,
};

/**
 * Enhanced API request with automatic retry logic
 */
export async function apiRequestWithRetry<T>(
  request: () => Promise<T>,
  config: RetryConfig = {}
): Promise<T> {
  const {
    maxRetries,
    retryDelay,
    retryableStatuses,
    exponentialBackoff,
  } = { ...DEFAULT_RETRY_CONFIG, ...config };

  let lastError: any;
  
  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    try {
      const result = await request();
      if (attempt > 0) {
        logAPI.success(`Request succeeded after ${attempt} retries`);
      }
      return result;
    } catch (error: any) {
      lastError = error;
      
      // Check if we should retry
      const shouldRetry = attempt < maxRetries && isRetryableError(error, retryableStatuses);
      
      if (!shouldRetry) {
        logAPI.error('retry', 'request', error);
        throw normalizeApiError(error);
      }
      
      // Calculate delay with exponential backoff
      const delay = exponentialBackoff 
        ? retryDelay * Math.pow(2, attempt)
        : retryDelay;
      
      logAPI.warning(`Request failed, retrying in ${delay}ms (attempt ${attempt + 1}/${maxRetries})`);
      
      await sleep(delay);
    }
  }
  
  throw normalizeApiError(lastError);
}

/**
 * Check if an error is retryable
 */
function isRetryableError(error: any, retryableStatuses: number[]): boolean {
  // Network errors are retryable
  if (error.name === 'NetworkError' || error.message === 'Failed to fetch') {
    return true;
  }
  
  // Check status code
  if (error.status && retryableStatuses.includes(error.status)) {
    return true;
  }
  
  return false;
}

/**
 * Normalize different error formats into ApiError
 */
function normalizeApiError(error: any): ApiError {
  const apiError: ApiError = {
    message: 'An unexpected error occurred',
    timestamp: new Date().toISOString(),
  };

  if (error.response) {
    // Axios-style error
    apiError.status = error.response.status;
    apiError.message = error.response.data?.message || error.response.statusText;
    apiError.code = error.response.data?.code;
    apiError.details = error.response.data;
  } else if (error.status) {
    // Fetch API error
    apiError.status = error.status;
    apiError.message = error.message || error.statusText;
  } else if (error.message) {
    // Generic error
    apiError.message = error.message;
  }

  return apiError;
}

/**
 * Sleep utility for delays
 */
function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Rate limiter for client-side request throttling
 */
export class RateLimiter {
  private queue: Array<() => void> = [];
  private processing = false;
  private lastRequestTime = 0;

  constructor(
    private requestsPerSecond: number = 10,
    private burstSize: number = 5
  ) {}

  /**
   * Execute a request with rate limiting
   */
  async execute<T>(request: () => Promise<T>): Promise<T> {
    return new Promise((resolve, reject) => {
      this.queue.push(async () => {
        try {
          const result = await request();
          resolve(result);
        } catch (error) {
          reject(error);
        }
      });

      this.processQueue();
    });
  }

  private async processQueue() {
    if (this.processing || this.queue.length === 0) {
      return;
    }

    this.processing = true;

    while (this.queue.length > 0) {
      const now = Date.now();
      const timeSinceLastRequest = now - this.lastRequestTime;
      const minDelay = 1000 / this.requestsPerSecond;

      // Wait if we're going too fast
      if (timeSinceLastRequest < minDelay) {
        await sleep(minDelay - timeSinceLastRequest);
      }

      const request = this.queue.shift();
      if (request) {
        this.lastRequestTime = Date.now();
        await request();
      }
    }

    this.processing = false;
  }

  /**
   * Get current queue size
   */
  getQueueSize(): number {
    return this.queue.length;
  }

  /**
   * Clear the queue
   */
  clearQueue(): void {
    this.queue = [];
  }
}

/**
 * Global rate limiter instance
 */
export const globalRateLimiter = new RateLimiter(10, 5);

/**
 * HTTP status code helpers
 */
export const HttpStatus = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  CONFLICT: 409,
  UNPROCESSABLE_ENTITY: 422,
  TOO_MANY_REQUESTS: 429,
  INTERNAL_SERVER_ERROR: 500,
  BAD_GATEWAY: 502,
  SERVICE_UNAVAILABLE: 503,
  GATEWAY_TIMEOUT: 504,
};

/**
 * User-friendly error messages
 */
export function getErrorMessage(error: ApiError): string {
  if (error.status) {
    switch (error.status) {
      case HttpStatus.UNAUTHORIZED:
        return 'Please log in to continue.';
      case HttpStatus.FORBIDDEN:
        return 'You don\'t have permission to do that.';
      case HttpStatus.NOT_FOUND:
        return 'The requested resource was not found.';
      case HttpStatus.CONFLICT:
        return 'This action conflicts with existing data.';
      case HttpStatus.TOO_MANY_REQUESTS:
        return 'Too many requests. Please slow down.';
      case HttpStatus.INTERNAL_SERVER_ERROR:
      case HttpStatus.BAD_GATEWAY:
      case HttpStatus.SERVICE_UNAVAILABLE:
      case HttpStatus.GATEWAY_TIMEOUT:
        // In development, show the actual error message
        if (process.env.NODE_ENV === 'development' && error.message) {
          return `Server Error: ${error.message}`;
        }
        return 'Server error. Please try again later.';
      default:
        return error.message;
    }
  }
  
  return error.message;
}

/**
 * Enhanced fetch wrapper with retry and rate limiting
 */
export async function enhancedFetch<T = any>(
  url: string,
  options: RequestInit = {},
  retryConfig?: RetryConfig
): Promise<T> {
  return globalRateLimiter.execute(() => 
    apiRequestWithRetry(async () => {
      const response = await fetch(url, options);
      
      if (!response.ok) {
        const error: any = new Error(response.statusText);
        error.status = response.status;
        error.statusText = response.statusText;
        
        try {
          error.response = { data: await response.json() };
        } catch {
          // Response body is not JSON
        }
        
        throw error;
      }
      
      return response.json();
    }, retryConfig)
  );
}
