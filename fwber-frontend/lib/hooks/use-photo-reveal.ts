/**
 * React Query hooks for Photo Reveal functionality
 */

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { photoAPI } from '@/lib/api/photos';

export interface PhotoRevealStatus {
  photoId: string;
  matchId: string;
  isRevealed: boolean;
  revealedAt?: string;
}

/**
 * Hook to reveal a photo to a match
 */
export function useRevealPhotoMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ photoId, matchId }: { photoId: string; matchId: string }) => {
      return photoAPI.revealPhoto(photoId, matchId);
    },
    onSuccess: (_, variables) => {
      // Invalidate any cached reveal status
      queryClient.invalidateQueries({
        queryKey: ['photo-reveal', variables.photoId, variables.matchId],
      });
      // Invalidate match data to reflect reveal status
      queryClient.invalidateQueries({
        queryKey: ['matches'],
      });
    },
  });
}

/**
 * Hook to fetch the original (unblurred) photo blob
 */
export function useOriginalPhoto(photoId: string | null, enabled = true) {
  return useQuery({
    queryKey: ['photo-original', photoId],
    queryFn: async () => {
      if (!photoId) throw new Error('Photo ID required');
      const blob = await photoAPI.getOriginalPhoto(photoId);
      return URL.createObjectURL(blob);
    },
    enabled: !!photoId && enabled,
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 10 * 60 * 1000, // 10 minutes
  });
}

/**
 * Combined hook for photo reveal workflow
 */
export function usePhotoReveal(photoId: string, matchId: string) {
  const revealMutation = useRevealPhotoMutation();
  const originalPhotoQuery = useOriginalPhoto(photoId, false);

  const reveal = async () => {
    // First reveal the photo via API
    await revealMutation.mutateAsync({ photoId, matchId });
    // Then fetch the original
    await originalPhotoQuery.refetch();
  };

  return {
    reveal,
    isRevealing: revealMutation.isPending,
    revealError: revealMutation.error,
    originalUrl: originalPhotoQuery.data,
    isLoadingOriginal: originalPhotoQuery.isLoading,
    originalError: originalPhotoQuery.error,
  };
}
