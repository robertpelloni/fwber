'use client';

interface FriendListProps {
  friends: any[];
  onRemoveFriend: (friendId: number) => void;
}

export default function FriendList({ friends, onRemoveFriend }: FriendListProps) {
  return (
    <div className="space-y-4">
      {friends.map((friend) => (
        <div key={friend.id} className="flex items-center justify-between p-4 bg-white rounded-lg shadow">
          <div>
            <p className="font-semibold">{friend.name}</p>
            <p className="text-sm text-gray-500">{friend.email}</p>
          </div>
          <button
            onClick={() => onRemoveFriend(friend.id)}
            className="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg"
          >
            Remove
          </button>
        </div>
      ))}
    </div>
  );
}
