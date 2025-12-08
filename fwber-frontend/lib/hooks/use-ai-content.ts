import { useState } from 'react';
import axios from 'axios';
import { useMutation } from '@tanstack/react-query';

interface GenerationResult {
  suggestions: Array<{
    id: string;
    content: string;
    provider: string;
    confidence: number;
    safety_score: number;
    type: string;
    timestamp: string;
  }>;
  total_providers: number;
  generation_time: string;
  generation_id: string;
}

interface BioParams {
  personality?: string;
  interests?: string[];
  goals?: string;
  style?: 'casual' | 'formal' | 'humorous' | 'romantic';
}

interface PostParams {
  context?: string;
  topic?: string;
}

interface StarterParams {
  target_user_id?: number;
  context?: string;
}

export function useAIContent() {
  const [error, setError] = useState<string | null>(null);

  const getHeaders = () => {
    const token = localStorage.getItem('fwber_token');
    return {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    };
  };

  const generateBio = useMutation({
    mutationFn: async (params: BioParams) => {
      const response = await axios.post<GenerationResult>(
        `${process.env.NEXT_PUBLIC_API_URL}/content/generate-bio`,
        params,
        { headers: getHeaders() }
      );
      return response.data;
    },
    onError: (err: any) => {
      setError(err.response?.data?.message || 'Failed to generate bio');
    },
  });

  const generatePosts = useMutation({
    mutationFn: async ({ boardId, params }: { boardId: number; params: PostParams }) => {
      const response = await axios.post<GenerationResult>(
        `${process.env.NEXT_PUBLIC_API_URL}/content/generate-posts/${boardId}`,
        params,
        { headers: getHeaders() }
      );
      return response.data;
    },
    onError: (err: any) => {
      setError(err.response?.data?.message || 'Failed to generate posts');
    },
  });

  const generateStarters = useMutation({
    mutationFn: async (params: StarterParams) => {
      const response = await axios.post<GenerationResult>(
        `${process.env.NEXT_PUBLIC_API_URL}/content/generate-starters`,
        params,
        { headers: getHeaders() }
      );
      return response.data;
    },
    onError: (err: any) => {
      setError(err.response?.data?.message || 'Failed to generate conversation starters');
    },
  });

  return {
    generateBio,
    generatePosts,
    generateStarters,
    error,
    setError,
  };
}
