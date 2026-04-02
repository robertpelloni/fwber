import { api } from './client'
import type { Group } from './groups'
import type { JournalEntry } from './journals'
import type { ProximityArtifact } from '@/types/proximity'

export interface Topic {
  id: number
  slug: string
  label: string
  description: string | null
  emoji: string | null
  category: string
  aliases: string[]
  is_featured: boolean
  sort_order: number
  follower_count: number
  group_count: number
  journal_count: number
  artifact_count: number
  is_followed: boolean
}

export interface TopicListResponse {
  topics: Topic[]
}

export interface TopicDetailResponse {
  topic: Topic
  groups: Group[]
  journals: JournalEntry[]
  artifacts: ProximityArtifact[]
}

export interface TopicFilters {
  search?: string
  category?: string
  featured?: boolean
  followed?: boolean
}

export async function getTopics(filters: TopicFilters = {}): Promise<Topic[]> {
  const params: Record<string, string | number | boolean | undefined> = {
    search: filters.search,
    category: filters.category,
    featured: filters.featured,
    followed: filters.followed,
  }

  const response = await api.get<TopicListResponse>('/topics', {
    params,
  })

  return response.topics
}

export async function getFollowedTopics(): Promise<Topic[]> {
  const response = await api.get<TopicListResponse>('/topics/followed')
  return response.topics
}

export async function getTopic(slug: string): Promise<TopicDetailResponse> {
  return api.get<TopicDetailResponse>(`/topics/${slug}`)
}

export async function followTopic(slug: string): Promise<Topic> {
  const response = await api.post<{ topic: Topic }>(`/topics/${slug}/follow`)
  return response.topic
}

export async function unfollowTopic(slug: string): Promise<Topic> {
  const response = await api.delete<{ topic: Topic }>(`/topics/${slug}/follow`)
  return response.topic
}
