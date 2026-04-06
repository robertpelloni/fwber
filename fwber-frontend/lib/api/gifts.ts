import { apiClient } from './client';

export interface Gift {
  id: number;
  name: string;
  description: string;
  cost: number;
  icon_url: string;
  category: string;
  is_active: boolean;
}

export interface UserGift {
  id: number;
  sender_id: number;
  receiver_id: number;
  gift_id: number;
  message: string | null;
  cost_at_time: number;
  created_at: string;
  gift: Gift;
  sender: any; // User type
}

export async function getGifts(): Promise<Gift[]> {
  const response = await apiClient.get<{ data: Gift[] }>('/gifts');
  return response.data.data;
}

export async function sendGift(receiverId: number, giftId: number, message?: string): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>('/gifts/send', {
    receiver_id: receiverId,
    gift_id: giftId,
    message,
  });
  return response.data;
}

export async function getReceivedGifts(): Promise<UserGift[]> {
  const response = await apiClient.get<UserGift[]>('/gifts/received');
  return response.data;
}
