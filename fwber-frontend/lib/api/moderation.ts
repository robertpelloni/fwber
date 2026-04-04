import axios from 'axios';

const rawApiBaseUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
const API_BASE_URL = rawApiBaseUrl.replace(/\/$/, '').endsWith('/api')
  ? rawApiBaseUrl.replace(/\/$/, '')
  : `${rawApiBaseUrl.replace(/\/$/, '')}/api`;

export type ModerationStats = {
  flagged_artifacts: number;
  active_throttles: number;
  pending_spoof_detections: number;
  pending_merchants: number;
  moderation_actions_today: number;
};

export interface MerchantVerificationQueueItem {
  id: number;
  business_name: string;
  category: string;
  description?: string | null;
  address?: string | null;
  location_name?: string | null;
  verification_status: 'pending' | 'verified' | 'rejected';
  verification_notes?: string | null;
  trust_score: number;
  trust_tier: string;
  trust_breakdown: Record<string, number>;
  user?: { id: number; name?: string; email?: string };
}

export const moderationApi = {
  dashboard: async (token: string): Promise<{ stats: ModerationStats; recent_actions: any[] }> => {
    const res = await axios.get(`${API_BASE_URL}/moderation/dashboard`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  flaggedContent: async (token: string, page: number = 1) => {
    const res = await axios.get(`${API_BASE_URL}/moderation/flagged-content`, {
      params: { page },
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  reviewFlag: async (
    artifactId: number,
    payload: { action: 'approve' | 'remove' | 'throttle_user' | 'ban_user'; reason: string; throttle_severity?: number; throttle_duration_hours?: number },
    token: string
  ) => {
    const res = await axios.post(`${API_BASE_URL}/moderation/flags/${artifactId}/review`, payload, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  spoofDetections: async (token: string, page: number = 1) => {
    const res = await axios.get(`${API_BASE_URL}/moderation/spoof-detections`, {
      params: { page },
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  reviewSpoof: async (
    detectionId: number,
    payload: { action: 'confirm' | 'dismiss'; reason: string; apply_throttle?: boolean },
    token: string
  ) => {
    const res = await axios.post(`${API_BASE_URL}/moderation/spoofs/${detectionId}/review`, payload, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  throttles: async (token: string, page: number = 1) => {
    const res = await axios.get(`${API_BASE_URL}/moderation/throttles`, {
      params: { page },
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  removeThrottle: async (throttleId: number, token: string) => {
    const res = await axios.delete(`${API_BASE_URL}/moderation/throttles/${throttleId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  actions: async (token: string, page: number = 1) => {
    const res = await axios.get(`${API_BASE_URL}/moderation/actions`, {
      params: { page },
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  userProfile: async (userId: number, token: string) => {
    const res = await axios.get(`${API_BASE_URL}/moderation/users/${userId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  merchants: async (token: string, page: number = 1, status: string = 'pending', search: string = '') => {
    const res = await axios.get(`${API_BASE_URL}/moderation/merchants`, {
      params: { page, status, search },
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  reviewMerchant: async (
    merchantId: number,
    payload: { verification_status: 'pending' | 'verified' | 'rejected'; verification_notes?: string },
    token: string
  ) => {
    const res = await axios.patch(`${API_BASE_URL}/moderation/merchants/${merchantId}`, payload, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },
};
