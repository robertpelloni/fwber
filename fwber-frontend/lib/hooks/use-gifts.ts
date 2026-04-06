import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getGifts, sendGift, getReceivedGifts, Gift } from '@/lib/api/gifts';

export function useGifts() {
  return useQuery({
    queryKey: ['gifts'],
    queryFn: getGifts,
    staleTime: 1000 * 60 * 60, // 1 hour
  });
}

export function useReceivedGifts() {
  return useQuery({
    queryKey: ['received-gifts'],
    queryFn: getReceivedGifts,
  });
}

export function useSendGift() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ receiverId, giftId, message }: { receiverId: number; giftId: number; message?: string }) =>
      sendGift(receiverId, giftId, message),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['wallet'] }); // Update balance
      queryClient.invalidateQueries({ queryKey: ['received-gifts'] });
    },
  });
}
