import { apiClient } from './client';

export interface VerificationStatus {
  is_verified: boolean;
  verified_at: string | null;
}

export interface VerificationResult {
  success: boolean;
  verified: boolean;
  similarity?: number;
  message: string;
}

export const verificationApi = {
  getStatus: async (): Promise<VerificationStatus> => {
    const response = await apiClient.get<VerificationStatus>('/verification/status');
    return response.data;
  },

  verify: async (photo: File): Promise<VerificationResult> => {
    const formData = new FormData();
    formData.append('photo', photo);

    const token = typeof window !== 'undefined' ? localStorage.getItem('fwber_token') : null;
    const headers: Record<string, string> = {};
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const response = await apiClient.post<VerificationResult>('/verification/verify', formData);

    return response.data;
  },
};
