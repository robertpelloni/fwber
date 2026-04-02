import { api } from '@/lib/api/client'

export interface FederationActor {
  id: string
  type: string
  preferredUsername: string
  name?: string
  summary?: string
  icon?: { url: string } | null
  server: string
  url?: string | null
  inbox?: string | null
  outbox?: string | null
  isVerified?: boolean
  gender?: string | null
  orientation?: string | null
  relationshipStatus?: string | null
  cachedPostsCount?: number
  lastCachedPostAt?: string | null
  followingStatus?: string | null
  followerStatus?: string | null
}

export interface FederationConnection {
  id: number
  actor_uri: string
  username?: string | null
  domain?: string | null
  status?: string | null
  created_at?: string
  updated_at?: string
}

export interface FederatedPost {
  id: number
  actor_uri: string
  actor_username?: string | null
  actor_domain?: string | null
  actor_avatar?: string | null
  content: string
  published_at: string
  url?: string | null
  metadata?: {
    name?: string
    preferredUsername?: string
    summary?: string
  }
}

interface FederationActorDetailResponse {
  actor: FederationActor
}

interface FederationPostsResponse {
  posts?: FederatedPost[]
}

export function buildFederationActorExplorerHref(actorUri: string): string {
  return `/settings/federation/actors?uri=${encodeURIComponent(actorUri)}`
}

export function formatFederationHandle(
  username?: string | null,
  domain?: string | null,
  fallback?: string
): string {
  if (username && domain) {
    return `@${username}@${domain}`
  }

  return fallback || 'Unknown federated actor'
}

export async function getFederationActorDetail(actorUri: string): Promise<FederationActor> {
  const response = await api.get<FederationActorDetailResponse>('/federation/actors/detail', {
    params: { uri: actorUri },
  })

  return response.actor
}

export async function getFederatedPosts(params?: {
  actorUri?: string
  limit?: number
}): Promise<FederatedPost[]> {
  const response = await api.get<FederationPostsResponse>('/federation/posts', {
    params: {
      actor_uri: params?.actorUri,
      limit: params?.limit,
    },
  })

  return Array.isArray(response.posts) ? response.posts : []
}
