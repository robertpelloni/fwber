'use client';

interface FriendListProps {
  friends: any[];
  onRemoveFriend: (id: number) => void;
}

export default function FriendList({ friends, onRemoveFriend }: FriendListProps) {
  if (!friends) return <div>Loading...</div>;

  return (
    <div>
      {friends.map((friend: any) => (
        <div key={friend.id} className="flex items-center justify-between p-2 border-b">
          <span>{friend.name}</span>
          <button
            onClick={() => onRemoveFriend(friend.id)}
            className="px-2 py-1 bg-red-500 text-white rounded"
          >
            Remove
          </button>
        </div>
      ))}
    </div>
  );
}
