const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export interface FeedbackData {
  category: 'bug' | 'feature' | 'general' | 'safety';
  message: string;
  page_url?: string;
  metadata?: any;
}

export async function submitFeedback(token: string, data: FeedbackData) {
  const response = await fetch(`${API_BASE_URL}/feedback`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to submit feedback');
  }

  return response.json();
}
