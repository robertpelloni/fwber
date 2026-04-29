import { apiClient } from './client';

export interface VerificationStatus {
  status: string;
  is_verified: boolean;
  is_id_verified: boolean;
  verified_at: string | null;
  verification_photo_path: string | null;
}

export interface VerificationResult {
  success: boolean;
  verified: boolean;
  similarity?: number;
  message: string;
}

export const verificationApi = {
  getStatus: async (): Promise<VerificationStatus> => {
    return await apiClient.get<VerificationStatus>('/verification/status');
  },

  verify: async (photo: File): Promise<VerificationResult> => {
    const formData = new FormData();
    formData.append('photo', photo);

    return await apiClient.post<VerificationResult>('/verification/verify', formData);
  },
};
