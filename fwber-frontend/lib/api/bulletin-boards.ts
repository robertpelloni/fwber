import { useAuth } from '../auth-context';
import { storeOfflineMessage } from '../offline-store';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export interface BulletinBoard {
  id: number;
  geohash: string;
  center_lat: number;
  center_lng: number;
  radius_meters: number;
  name: string;
  description?: string;
  is_active: boolean;
  message_count: number;
  active_users: number;
  last_activity_at: string;
  created_at: string;
  updated_at: string;
  recent_messages?: BulletinMessage[];
}

export interface BulletinMessage {
  id: number;
  bulletin_board_id: number;
  user_id: number;
  content: string;
  metadata?: any;
  is_anonymous: boolean;
  is_moderated: boolean;
  expires_at?: string;
  reaction_count: number;
  reply_count: number;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
    avatar_url?: string;
  };
  author_name?: string;
  author_avatar?: string;
}

export interface LocationCoords {
  lat: number;
  lng: number;
}

export interface BulletinBoardFilters {
  lat: number;
  lng: number;
  radius?: number;
}

export class BulletinBoardAPI {
  private token: string;

  constructor(token: string) {
    this.token = token;
  }

  private async request(endpoint: string, options: RequestInit = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.token}`,
        ...options.headers,
      },
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Request failed' }));
      throw new Error(error.message || `HTTP ${response.status}`);
    }

    return response.json();
  }

  /**
   * Get bulletin boards near a location
   */
  async getNearbyBoards(filters: BulletinBoardFilters): Promise<{
    boards: BulletinBoard[];
    user_location: LocationCoords;
    search_radius: number;
  }> {
    const params = new URLSearchParams({
      lat: filters.lat.toString(),
      lng: filters.lng.toString(),
      ...(filters.radius && { radius: filters.radius.toString() }),
    });

    return this.request(`/bulletin-boards?${params}`);
  }

  /**
   * Get a specific bulletin board
   */
  async getBoard(id: number, location?: LocationCoords): Promise<{
    board: BulletinBoard;
    messages: BulletinMessage[];
  }> {
    const params = location 
      ? new URLSearchParams({
          lat: location.lat.toString(),
          lng: location.lng.toString(),
        })
      : '';

    return this.request(`/bulletin-boards/${id}?${params}`);
  }

  /**
   * Create or find a bulletin board for a location
   */
  async createOrFindBoard(location: LocationCoords, radius?: number): Promise<{
    board: BulletinBoard;
    created: boolean;
  }> {
    return this.request('/bulletin-boards', {
      method: 'POST',
      body: JSON.stringify({
        lat: location.lat,
        lng: location.lng,
        ...(radius && { radius }),
      }),
    });
  }

  /**
   * Post a message to a bulletin board
   */
  async postMessage(
    boardId: number,
    content: string,
    location: LocationCoords,
    options: {
      is_anonymous?: boolean;
      expires_in_hours?: number;
    } = {}
  ): Promise<{
    message: BulletinMessage;
    board: BulletinBoard;
  }> {
    try {
      return await this.request(`/bulletin-boards/${boardId}/messages`, {
        method: 'POST',
        body: JSON.stringify({
          content,
          lat: location.lat,
          lng: location.lng,
          ...options,
        }),
      });
    } catch (error) {
      // Check if offline
      if (typeof navigator !== 'undefined' && !navigator.onLine) {
        console.log('Offline, storing message for sync');
        await storeOfflineMessage({
          boardId,
          content,
          lat: location.lat,
          lng: location.lng,
          is_anonymous: options.is_anonymous,
          token: this.token, // Store token to replay request
          createdAt: Date.now()
        });
        
        // Register sync
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
          const registration = await navigator.serviceWorker.ready;
          // @ts-ignore - SyncManager is not in all TS definitions
          await registration.sync.register('bulletin-message');
        }
        
        // Return a fake message so the UI updates optimistically
        return {
          message: {
            id: -Date.now(), // Negative ID for temp
            bulletin_board_id: boardId,
            user_id: 0, // Unknown
            content,
            is_anonymous: !!options.is_anonymous,
            is_moderated: false,
            reaction_count: 0,
            reply_count: 0,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
          },
          board: {} as any // Partial board
        };
      }
      throw error;
    }
  }

  /**
   * Get messages for a bulletin board
   */
  async getMessages(
    boardId: number,
    options: {
      per_page?: number;
      since?: string;
    } = {}
  ): Promise<{
    messages: {
      data: BulletinMessage[];
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
    board: BulletinBoard;
  }> {
    const params = new URLSearchParams();
    if (options.per_page) params.append('per_page', options.per_page.toString());
    if (options.since) params.append('since', options.since);

    return this.request(`/bulletin-boards/${boardId}/messages?${params}`);
  }


}

/**
 * Hook to use bulletin board API
 */
export function useBulletinBoardAPI() {
  const { token } = useAuth();
  
  if (!token) {
    throw new Error('Authentication required');
  }

  return new BulletinBoardAPI(token);
}
