import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  createJournal,
  deleteJournal,
  getJournalPrivacySettings,
  getJournals,
  type CreateJournalPayload,
  type JournalEntry,
  type JournalPrivacySettings,
  type JournalVisibility,
  updateJournalPrivacySettings,
} from '../api/journals';

export const journalKeys = {
  all: ['journals'] as const,
  list: (visibility?: JournalVisibility) => [...journalKeys.all, visibility ?? 'all'] as const,
  privacy: ['journals', 'privacy'] as const,
};

export function useJournals(visibility?: JournalVisibility) {
  return useQuery<JournalEntry[]>({
    queryKey: journalKeys.list(visibility),
    queryFn: () => getJournals(visibility),
  });
}

export function useCreateJournal() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: CreateJournalPayload) => createJournal(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: journalKeys.all });
    },
  });
}

export function useDeleteJournal() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => deleteJournal(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: journalKeys.all });
    },
  });
}

export function useJournalPrivacySettings() {
  return useQuery<JournalPrivacySettings>({
    queryKey: journalKeys.privacy,
    queryFn: getJournalPrivacySettings,
  });
}

export function useUpdateJournalPrivacySettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: updateJournalPrivacySettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: journalKeys.privacy });
      queryClient.invalidateQueries({ queryKey: journalKeys.all });
    },
  });
}
