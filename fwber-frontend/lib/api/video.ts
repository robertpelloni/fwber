import { apiClient } from './client';


export interface VideoCallLog {
  id: number;
  caller_id: number;
  receiver_id: number;
  started_at: string | null;
  created_at: string;
  ended_at: string | null;
  status: 'initiated' | 'connected' | 'missed' | 'rejected' | 'ended';
  duration: number | null;
  caller?: {
    id: number;
    name: string;
    avatar_url: string | null;
  };
  receiver?: {
    id: number;
    name: string;
    avatar_url: string | null;
  };
}

export const initiateCall = async (token: string, recipientId: string): Promise<VideoCallLog> => {
  const response: any = await apiClient.post(
    `/video/initiate`,
    { recipient_id: recipientId },
    {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    }
  );
  return response.data;
};

export const updateCallStatus = async (
  token: string,
  callId: number,
  status: 'connected' | 'missed' | 'rejected' | 'ended',
  duration?: number
): Promise<VideoCallLog> => {
  const response: any = await apiClient.put(
    `/video/${callId}/status`,
    { status, duration },
    {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    }
  );
  return response.data;
};

export const getCallHistory = async (token: string): Promise<VideoCallLog[]> => {
  const response: any = await apiClient.get(`/video/history`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });
  return response.data.data; // Assuming paginated response
};
