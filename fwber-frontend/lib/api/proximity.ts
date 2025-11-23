import axios from 'axios';
import type {
  LocalPulseResponse,
  ProximityArtifact,
  CreateArtifactRequest,
  LocalPulseParams,
  ProximityChatroom,
} from '@/types/proximity';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export const proximityApi = {
  /**
   * Get Local Pulse merged feed (artifacts + match candidates)
   */
  getLocalPulse: async (params: LocalPulseParams, token: string): Promise<LocalPulseResponse> => {
    const { lat, lng, radius = 1000 } = params;
    const response = await axios.get<LocalPulseResponse>(
      `${API_BASE_URL}/proximity/local-pulse`,
      {
        params: { lat, lng, radius },
        headers: { Authorization: `Bearer ${token}` },
      }
    );
    return response.data;
  },

  /**
   * Get proximity artifacts feed only
   */
  getArtifactsFeed: async (
    lat: number,
    lng: number,
    radius: number = 1000,
    type?: string,
    token?: string
  ): Promise<{ artifacts: ProximityArtifact[] }> => {
    const response = await axios.get(`${API_BASE_URL}/proximity/feed`, {
      params: { lat, lng, radius, type },
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
    return response.data;
  },

  /**
   * Create a new proximity artifact
   */
  createArtifact: async (data: CreateArtifactRequest, token: string): Promise<{ artifact: ProximityArtifact }> => {
    const response = await axios.post(
      `${API_BASE_URL}/proximity/artifacts`,
      data,
      {
        headers: { Authorization: `Bearer ${token}` },
      }
    );
    return response.data;
  },

  /**
   * Get single artifact by ID
   */
  getArtifact: async (id: number, token?: string): Promise<{ artifact: ProximityArtifact }> => {
    const response = await axios.get(`${API_BASE_URL}/proximity/artifacts/${id}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
    return response.data;
  },

  /**
   * Flag an artifact for moderation
   */
  flagArtifact: async (id: number, token: string): Promise<{ message: string }> => {
    const response = await axios.post(
      `${API_BASE_URL}/proximity/artifacts/${id}/flag`,
      {},
      {
        headers: { Authorization: `Bearer ${token}` },
      }
    );
    return response.data;
  },

  /**
   * Delete (remove) an artifact (owner only)
   */
  deleteArtifact: async (id: number, token: string): Promise<{ message: string }> => {
    const response = await axios.delete(`${API_BASE_URL}/proximity/artifacts/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  },

  /**
   * Find nearby proximity chatrooms
   */
  findNearbyChatrooms: async (
    lat: number,
    lng: number,
    radius: number = 1000,
    token: string
  ): Promise<{ chatrooms: ProximityChatroom[] }> => {
    const response = await axios.get(`${API_BASE_URL}/proximity-chatrooms/nearby`, {
      params: { lat, lng, radius },
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  },
};
