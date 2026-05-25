import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  followTopic,
  getFollowedTopics,
  getTopic,
  getTopics,
  type TopicFilters,
  unfollowTopic,
} from '../api/topics'

const topicKeysRoot = ['topics'] as const

export const topicKeys = {
  all: topicKeysRoot,
  list: (filters: TopicFilters = {}) => [...topicKeysRoot, 'list', filters] as const,
  followed: [...topicKeysRoot, 'followed'] as const,
  detail: (slug: string) => [...topicKeysRoot, 'detail', slug] as const,
}

export function useTopics(filters: TopicFilters = {}) {
  return useQuery({
    queryKey: topicKeys.list(filters),
    queryFn: () => getTopics(filters),
  })
}

export function useFollowedTopics() {
  return useQuery({
    queryKey: topicKeys.followed,
    queryFn: getFollowedTopics,
  })
}

export function useTopic(slug: string) {
  return useQuery({
    queryKey: topicKeys.detail(slug),
    queryFn: () => getTopic(slug),
    enabled: !!slug,
  })
}

export function useFollowTopic() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (slug: string) => followTopic(slug),
    onSuccess: (topic) => {
      queryClient.invalidateQueries({ queryKey: topicKeys.all })
      queryClient.invalidateQueries({ queryKey: topicKeys.detail(topic.slug) })
    },
  })
}

export function useUnfollowTopic() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (slug: string) => unfollowTopic(slug),
    onSuccess: (topic) => {
      queryClient.invalidateQueries({ queryKey: topicKeys.all })
      queryClient.invalidateQueries({ queryKey: topicKeys.detail(topic.slug) })
    },
  })
}
