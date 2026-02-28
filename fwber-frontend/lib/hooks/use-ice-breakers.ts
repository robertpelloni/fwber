import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    getIceBreakerQuestions,
    submitIceBreakerAnswer,
    getIceBreakerAnswers,
    type SubmitAnswerRequest,
} from '../api/ice-breakers';

export const iceBreakerKeys = {
    all: ['ice-breakers'] as const,
    questions: (matchId: number) => [...iceBreakerKeys.all, 'questions', matchId] as const,
    answers: (matchId: number) => [...iceBreakerKeys.all, 'answers', matchId] as const,
};

export function useIceBreakerQuestions(matchId: number) {
    return useQuery({
        queryKey: iceBreakerKeys.questions(matchId),
        queryFn: () => getIceBreakerQuestions(matchId),
        enabled: !!matchId,
        staleTime: 30 * 1000,
    });
}

export function useIceBreakerAnswers(matchId: number) {
    return useQuery({
        queryKey: iceBreakerKeys.answers(matchId),
        queryFn: () => getIceBreakerAnswers(matchId),
        enabled: !!matchId,
        staleTime: 60 * 1000,
    });
}

export function useSubmitIceBreakerAnswer() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: SubmitAnswerRequest) => submitIceBreakerAnswer(data),
        onSuccess: (_, variables) => {
            queryClient.invalidateQueries({ queryKey: iceBreakerKeys.questions(variables.match_id) });
            queryClient.invalidateQueries({ queryKey: iceBreakerKeys.answers(variables.match_id) });
        },
    });
}
