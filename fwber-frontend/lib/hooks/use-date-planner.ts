import { useQuery } from '@tanstack/react-query';
import { getDateIdeas } from '../api/date-planner';

export const datePlannerKeys = {
    all: ['date-planner'] as const,
    ideas: (matchId: number, location?: string) =>
        [...datePlannerKeys.all, 'ideas', matchId, location || ''] as const,
};

export function useDateIdeas(matchId: number, location?: string) {
    return useQuery({
        queryKey: datePlannerKeys.ideas(matchId, location),
        queryFn: () => getDateIdeas(matchId, location),
        enabled: !!matchId,
        staleTime: 5 * 60 * 1000, // 5 minutes — AI responses are expensive
    });
}
