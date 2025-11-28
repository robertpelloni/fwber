/**
 * Safety API Client
 * 
 * Purpose: Type-safe API client for user safety operations (blocking, reporting)
 * 
 * Created: 2025-11-19
 */

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export interface BlockResponse {
  data: {
    id: number;
    blocker_id: number;
    blocked_id: number;
    created_at: string;
    updated_at: string;
  };
}

export interface ReportResponse {
  data: {
    id: number;
    reporter_id: number;
    accused_id: number;
    message_id: number | null;
    reason: string;
    details: string | null;
    status: string;
    created_at: string;
    updated_at: string;
  };
}

/**
 * Block a user
 */
export async function blockUser(token: string, blockedId: number): Promise<BlockResponse> {
  const response = await fetch(`${API_BASE_URL}/blocks`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      blocked_id: blockedId,
    }),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to block user' }));
    throw new Error(error.message || 'Failed to block user');
  }

  return response.json();
}

/**
 * Unblock a user
 */
export async function unblockUser(token: string, blockedId: number): Promise<{ ok: boolean }> {
  const response = await fetch(`${API_BASE_URL}/blocks/${blockedId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to unblock user' }));
    throw new Error(error.message || 'Failed to unblock user');
  }

  return response.json();
}

/**
 * Report a user
 */
export async function reportUser(
  token: string, 
  accusedId: number, 
  reason: string, 
  details?: string, 
  messageId?: number
): Promise<ReportResponse> {
  const response = await fetch(`${API_BASE_URL}/reports`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      accused_id: accusedId,
      reason: reason,
      details: details,
      message_id: messageId,
    }),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to report user' }));
    throw new Error(error.message || 'Failed to report user');
  }

  return response.json();
}
