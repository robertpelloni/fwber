import axios from 'axios';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

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
  const response = await axios.post(
    `${API_URL}/video/initiate`,
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
  const response = await axios.put(
    `${API_URL}/video/${callId}/status`,
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
  const response = await axios.get(`${API_URL}/video/history`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });
  return response.data.data; // Assuming paginated response
};
