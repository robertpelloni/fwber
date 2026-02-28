import { apiClient } from './client';

export interface DateIdea {
    title: string;
    description: string;
    reason: string;
    venue?: string;
    estimated_cost?: string;
    duration?: string;
    conversation_starter?: string;
}

export interface DateIdeasResponse {
    ideas: DateIdea[];
}

/**
 * Get AI-generated date ideas for a matched pair
 */
export async function getDateIdeas(matchId: number, location?: string): Promise<DateIdeasResponse> {
    const params = location ? `?location=${encodeURIComponent(location)}` : '';
    const response = await apiClient.get<DateIdeasResponse>(
        `/wingman/date-ideas/${matchId}${params}`
    );
    return response.data;
}
