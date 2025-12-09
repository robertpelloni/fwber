'use client';

interface FriendRequestListProps {
  friendRequests: any[];
  onRespondToRequest: (userId: number, status: 'accepted' | 'declined') => void;
}

export default function FriendRequestList({ friendRequests, onRespondToRequest }: FriendRequestListProps) {
  if (!friendRequests) return <div>Loading...</div>;

  return (
    <div>
      {friendRequests.map((request: any) => (
        <div key={request.id} className="flex items-center justify-between p-2 border-b">
          <span>{request.user.name}</span>
          <div>
            <button
              onClick={() => onRespondToRequest(request.user.id, 'accepted')}
              className="px-2 py-1 bg-green-500 text-white rounded mr-2"
            >
              Accept
            </button>
            <button
              onClick={() => onRespondToRequest(request.user.id, 'declined')}
              className="px-2 py-1 bg-red-500 text-white rounded"
            >
              Decline
            </button>
          </div>
        </div>
      ))}
    </div>
  );
}
