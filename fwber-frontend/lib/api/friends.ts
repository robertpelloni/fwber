import { api } from './client'

export interface FriendUser {
  id: number
  name: string
  email: string
  profile?: {
    display_name?: string | null
    avatar_url?: string | null
  } | null
}

export interface FriendRequest {
  id: number
  user_id: number
  friend_id: number
  status: 'pending' | 'accepted' | 'declined'
  user?: FriendUser
}

export async function getFriends(_token?: string) {
  return api.get<FriendUser[]>('/friends')
}

export async function getFriendRequests(_token?: string) {
  return api.get<FriendRequest[]>('/friends/requests')
}

export async function searchUsers(_token: string | undefined, query: string) {
  return api.get<FriendUser[]>(`/users/search?q=${encodeURIComponent(query)}`)
}

export async function sendFriendRequest(_token: string | undefined, friendId: number) {
  return api.post('/friends/requests', { friend_id: friendId })
}

export async function respondToFriendRequest(_token: string | undefined, userId: number, status: 'accepted' | 'declined') {
  return api.post(`/friends/requests/${userId}`, { status })
}

export async function removeFriend(_token: string | undefined, friendId: number) {
  return api.delete(`/friends/${friendId}`)
}
