import { apiClient } from './client';

// Types
export interface IceBreakerQuestion {
    id: number;
    question: string;
    category: 'fun' | 'deep' | 'creative' | 'spicy';
    emoji: string;
    is_active: boolean;
    user_answered: boolean;
    user_answer: string | null;
    match_answered: boolean;
    match_answer: string | null;
    is_revealed: boolean;
}

export interface IceBreakerQuestionsResponse {
    questions: IceBreakerQuestion[];
    meta: {
        total_answered: number;
        total_questions: number;
    };
}

export interface SubmitAnswerRequest {
    question_id: number;
    match_id: number;
    answer: string;
}

export interface SubmitAnswerResponse {
    answer: {
        id: number;
        user_id: number;
        question_id: number;
        match_user_id: number;
        answer: string;
    };
    is_revealed: boolean;
    match_answer: string | null;
    question: IceBreakerQuestion;
}

export interface IceBreakerCard {
    question_id: number;
    question: string;
    category: string;
    emoji: string;
    user_answer: string;
    match_answer: string | null;
    is_revealed: boolean;
    answered_at: string;
}

export interface IceBreakerAnswersResponse {
    cards: IceBreakerCard[];
    meta: {
        total_answered: number;
        total_revealed: number;
    };
}

/**
 * Get shuffled ice breaker questions for a match pair
 */
export async function getIceBreakerQuestions(matchId: number): Promise<IceBreakerQuestionsResponse> {
    const response = await apiClient.get<IceBreakerQuestionsResponse>(
        `/ice-breakers/questions?match_id=${matchId}`
    );
    return response.data;
}

/**
 * Submit an answer to an ice breaker question
 */
export async function submitIceBreakerAnswer(data: SubmitAnswerRequest): Promise<SubmitAnswerResponse> {
    const response = await apiClient.post<SubmitAnswerResponse>('/ice-breakers/answer', data);
    return response.data;
}

/**
 * Get all answered ice breaker cards for a match pair
 */
export async function getIceBreakerAnswers(matchId: number): Promise<IceBreakerAnswersResponse> {
    const response = await apiClient.get<IceBreakerAnswersResponse>(`/ice-breakers/answers/${matchId}`);
    return response.data;
}
