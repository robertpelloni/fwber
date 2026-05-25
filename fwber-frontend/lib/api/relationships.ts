import { api } from './client'

export type RelationshipLinkType = 'dating' | 'partner' | 'spouse' | 'other'
export type RelationshipLinkVisibility = 'public' | 'friends' | 'private'

export interface RelationshipLinkUser {
  id: number
  name: string
  display_name?: string | null
  avatar_url?: string | null
}

export interface RelationshipLink {
  id: number
  relationship_type: RelationshipLinkType
  relationship_type_label: string
  visibility: RelationshipLinkVisibility
  visibility_label: string
  note?: string | null
  requested_at?: string | null
  confirmed_at?: string | null
  is_confirmed: boolean
  requested_by_user_id: number
  related_user: RelationshipLinkUser | null
}

export async function getRelationshipLinks(): Promise<RelationshipLink[]> {
  const response = await api.get<{ links: RelationshipLink[] }>('/relationship-links')
  return response.links
}

export async function getPendingRelationshipLinkRequests(): Promise<RelationshipLink[]> {
  const response = await api.get<{ requests: RelationshipLink[] }>('/relationship-links/requests')
  return response.requests
}

export async function createRelationshipLink(payload: {
  related_user_id: number
  relationship_type: RelationshipLinkType
  visibility: RelationshipLinkVisibility
  note?: string
}): Promise<RelationshipLink> {
  const response = await api.post<{ link: RelationshipLink }>('/relationship-links', payload)
  return response.link
}

export async function respondToRelationshipLink(linkId: number, status: 'accepted' | 'declined'): Promise<RelationshipLink | null> {
  const response = await api.post<{ link?: RelationshipLink; message?: string }>(`/relationship-links/${linkId}/respond`, { status })
  return response.link ?? null
}

export async function updateRelationshipLink(
  linkId: number,
  payload: Partial<{
    relationship_type: RelationshipLinkType
    visibility: RelationshipLinkVisibility
    note: string | null
  }>
): Promise<RelationshipLink> {
  const response = await api.put<{ link: RelationshipLink }>(`/relationship-links/${linkId}`, payload)
  return response.link
}

export async function deleteRelationshipLink(linkId: number): Promise<void> {
  await api.delete(`/relationship-links/${linkId}`)
}
