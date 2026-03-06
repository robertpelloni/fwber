import { useMutation } from '@tanstack/react-query';
import { createBurnerLink, joinBurnerLink, BurnerLinkResponse, JoinBurnerResponse } from '../api/burner';
import { AxiosError } from 'axios';

export function useCreateBurnerLink() {
    return useMutation<BurnerLinkResponse, AxiosError, void>({
        mutationFn: () => createBurnerLink(),
    });
}

export function useJoinBurnerLink() {
    return useMutation<JoinBurnerResponse, AxiosError, string>({
        mutationFn: (token: string) => joinBurnerLink(token),
    });
}
