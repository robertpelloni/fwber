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

    // Use fetch directly to avoid JSON.stringify in apiClient
    const baseUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
    const url = baseUrl.endsWith('/api') ? baseUrl : `${baseUrl}/api`;
    
    const response = await fetch(`${url}/verification/verify`, {
      method: 'POST',
      headers,
      body: formData,
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw { response: { data: errorData } }; // Mimic axios/apiClient error structure for the UI
    }

    return response.json();
  },
};
