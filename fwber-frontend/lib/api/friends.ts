const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export async function getFriends(token: string) {
  const response = await fetch(`${API_BASE_URL}/friends`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.json();
}

export async function getFriendRequests(token: string) {
  const response = await fetch(`${API_BASE_URL}/friends/requests`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.json();
}

export async function sendFriendRequest(token: string, friend_id: number) {
  const response = await fetch(`${API_BASE_URL}/friends/requests`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ friend_id }),
  });
  return response.json();
}

export async function respondToFriendRequest(token: string, userId: number, status: 'accepted' | 'declined') {
  const response = await fetch(`${API_BASE_URL}/friends/requests/${userId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ status }),
  });
  return response.json();
}

export async function removeFriend(token: string, friendId: number) {
  const response = await fetch(`${API_BASE_URL}/friends/${friendId}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${token}` },
  });
  return response.json();
}
