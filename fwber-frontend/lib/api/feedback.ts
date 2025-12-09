import { api } from './client';

export interface FeedbackData {
  category: 'bug' | 'feature' | 'general' | 'safety';
  message: string;
  page_url?: string;
  metadata?: any;
}

export async function submitFeedback(token: string, data: FeedbackData) {
  // Note: token is handled by apiClient via localStorage, but we keep the signature for compatibility
  // or if we want to override headers. For now, apiClient handles auth automatically.
  return api.post('/feedback', data);
}
