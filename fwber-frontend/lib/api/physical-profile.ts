import axios from 'axios';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export type PhysicalProfile = {
  height_cm?: number;
  body_type?: string;
  hair_color?: string;
  eye_color?: string;
  skin_tone?: string;
  ethnicity?: string;
  facial_hair?: string;
  tattoos?: boolean;
  piercings?: boolean;
  dominant_hand?: 'left' | 'right' | 'ambi';
  fitness_level?: 'low' | 'average' | 'fit' | 'athletic';
  clothing_style?: string;
  avatar_prompt?: string;
  avatar_status?: string;
};

export const physicalProfileApi = {
  get: async (token: string): Promise<{ data: PhysicalProfile }> => {
    const res = await axios.get(`${API_BASE_URL}/physical-profile`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  upsert: async (token: string, data: PhysicalProfile): Promise<{ data: PhysicalProfile }> => {
    const res = await axios.put(`${API_BASE_URL}/physical-profile`, data, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },

  requestAvatar: async (token: string, style: string): Promise<{ data: PhysicalProfile }> => {
    const res = await axios.post(`${API_BASE_URL}/physical-profile/avatar/request`, { style }, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  },
};
