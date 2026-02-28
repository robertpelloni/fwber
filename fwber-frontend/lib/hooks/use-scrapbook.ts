import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    getScrapbook,
    addScrapbookEntry,
    toggleScrapbookPin,
    deleteScrapbookEntry,
    type CreateScrapbookEntry,
} from '../api/scrapbook';

export const scrapbookKeys = {
    all: ['scrapbook'] as const,
    entries: (matchId: number) => [...scrapbookKeys.all, matchId] as const,
};

export function useScrapbook(matchId: number) {
    return useQuery({
        queryKey: scrapbookKeys.entries(matchId),
        queryFn: () => getScrapbook(matchId),
        enabled: !!matchId,
        staleTime: 30 * 1000,
    });
}

export function useAddScrapbookEntry() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (data: CreateScrapbookEntry) => addScrapbookEntry(data),
        onSuccess: (_, variables) => {
            queryClient.invalidateQueries({ queryKey: scrapbookKeys.entries(variables.match_id) });
        },
    });
}

export function useTogglePin() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => toggleScrapbookPin(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: scrapbookKeys.all });
        },
    });
}

export function useDeleteEntry() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => deleteScrapbookEntry(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: scrapbookKeys.all });
        },
    });
}
