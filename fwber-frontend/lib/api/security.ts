import { apiClient } from './client';

export const securityApi = {
  storePublicKey: async (publicKey: string, keyType: string = 'ECDH', deviceId?: string) => {
    return apiClient.post('/security/keys', {
      public_key: publicKey,
      key_type: keyType,
      device_id: deviceId,
    });
  },

  getPublicKey: async (userId: number) => {
    return apiClient.get<{ user_id: number; public_key: string }>(`/security/keys/${userId}`);
  },

  getMyKey: async () => {
    return apiClient.get<{ public_key: string }>('/security/keys/me');
  },
};
