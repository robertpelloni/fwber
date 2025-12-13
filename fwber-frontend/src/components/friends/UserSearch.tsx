'use client';

interface UserSearchProps {
  searchResults: any[];
  onSendRequest: (id: number) => void;
}

export default function UserSearch({ searchResults, onSendRequest }: UserSearchProps) {
  return (
    <div>
      {searchResults.map((user: any) => (
        <div key={user.id} className="flex items-center justify-between p-2 border-b">
          <span>{user.name}</span>
          <button
            onClick={() => onSendRequest(user.id)}
            className="px-2 py-1 bg-blue-500 text-white rounded"
          >
            Add Friend
          </button>
        </div>
      ))}
    </div>
  );
}
