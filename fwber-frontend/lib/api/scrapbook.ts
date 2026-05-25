import { apiClient } from './client';

export interface ScrapbookEntry {
    id: number;
    user_id: number;
    match_user_id: number;
    type: 'text' | 'image' | 'voice' | 'link';
    content: string;
    media_url: string | null;
    media_type: string | null;
    emoji: string | null;
    color: string | null;
    is_pinned: boolean;
    is_mine: boolean;
    created_at: string;
    updated_at: string;
}

export interface ScrapbookResponse {
    entries: ScrapbookEntry[];
    meta: {
        total: number;
        pinned: number;
    };
}

export interface CreateScrapbookEntry {
    match_id: number;
    type: 'text' | 'image' | 'voice' | 'link';
    content: string;
    media_url?: string;
    media_type?: string;
    emoji?: string;
    color?: string;
}

export async function getScrapbook(matchId: number): Promise<ScrapbookResponse> {
    const response = await apiClient.get<ScrapbookResponse>(`/scrapbook/${matchId}`);
    return response.data;
}

export async function addScrapbookEntry(data: CreateScrapbookEntry): Promise<{ entry: ScrapbookEntry }> {
    const response = await apiClient.post<{ entry: ScrapbookEntry }>('/scrapbook', data);
    return response.data;
}

export async function toggleScrapbookPin(id: number): Promise<{ entry: ScrapbookEntry }> {
    const response = await apiClient.patch<{ entry: ScrapbookEntry }>(`/scrapbook/${id}/pin`);
    return response.data;
}

export async function deleteScrapbookEntry(id: number): Promise<void> {
    await apiClient.delete(`/scrapbook/${id}`);
}
