import { apiClient } from './client';

export interface NotificationPreference {
  type: string;
  label: string;
  mail: boolean;
  push: boolean;
  database: boolean;
}

export interface UpdateNotificationPreferenceRequest {
  mail?: boolean;
  push?: boolean;
  database?: boolean;
}

export async function getNotificationPreferences(): Promise<NotificationPreference[]> {
  const response = await apiClient.get<NotificationPreference[]>('/notification-preferences');
  return response.data;
}

export async function updateNotificationPreference(
  type: string,
  data: UpdateNotificationPreferenceRequest
): Promise<NotificationPreference> {
  const response = await apiClient.put<NotificationPreference>(`/notification-preferences/${type}`, data);
  return response.data;
}
