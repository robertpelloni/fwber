import { api } from './client';

export type JournalVisibility = 'public' | 'friends' | 'circle' | 'private';

export interface JournalGroup {
  id: number;
  name: string;
  privacy: 'public' | 'private';
}

export interface JournalEntry {
  id: number;
  user_id: number;
  title: string | null;
  content: string;
  visibility: JournalVisibility;
  visibility_label: string;
  circle_group_id: number | null;
  circle_group?: JournalGroup | null;
  tags: string[];
  mood_emoji: string | null;
  accent_color: string | null;
  can_edit: boolean;
  created_at: string;
  updated_at: string;
}

export interface JournalPrivacySettings {
  default_visibility: JournalVisibility;
  circle_group_id: number | null;
  available_groups: JournalGroup[];
}

export interface CreateJournalPayload {
  title?: string;
  content: string;
  visibility?: JournalVisibility;
  circle_group_id?: number | null;
  tags?: string[];
  mood_emoji?: string;
  accent_color?: string;
}

export async function getJournals(visibility?: JournalVisibility): Promise<JournalEntry[]> {
  const response = await api.get<{ journals: JournalEntry[] }>('/journals', {
    params: visibility ? { visibility } : undefined,
  });

  return response.journals;
}

export async function createJournal(payload: CreateJournalPayload): Promise<JournalEntry> {
  const response = await api.post<{ journal: JournalEntry }>('/journals', payload);
  return response.journal;
}

export async function deleteJournal(id: number): Promise<void> {
  await api.delete(`/journals/${id}`);
}

export async function getJournalPrivacySettings(): Promise<JournalPrivacySettings> {
  const response = await api.get<{ data: JournalPrivacySettings }>('/settings/privacy/journals');
  return response.data;
}

export async function updateJournalPrivacySettings(payload: {
  default_visibility: JournalVisibility;
  circle_group_id?: number | null;
}): Promise<JournalPrivacySettings> {
  const response = await api.put<{ data: JournalPrivacySettings }>('/settings/privacy/journals', payload);
  return response.data;
}
