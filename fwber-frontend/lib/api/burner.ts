import { apiClient } from './client';

export interface BurnerLinkResponse {
    token: string;
    expires_at: string;
    url: string;
}

export interface JoinBurnerResponse {
    message: string;
    chatroom_id?: number;
}

/**
 * Generate a new burner link
 */
export async function createBurnerLink(): Promise<BurnerLinkResponse> {
    const response = await apiClient.post<BurnerLinkResponse>('/burner-links');
    return response.data;
}

/**
 * Join an ephemeral chat using a burner link token
 */
export async function joinBurnerLink(token: string): Promise<JoinBurnerResponse> {
    const response = await apiClient.post<JoinBurnerResponse>(`/burner-links/${token}/join`);
    return response.data;
}
