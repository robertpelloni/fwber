'use client';

import { useState } from 'react';
import FriendList from '@/components/friends/FriendList';
import FriendRequestList from '@/components/friends/FriendRequestList';
import UserSearch from '@/components/friends/UserSearch';

export default function FriendsPage() {
  const [activeTab, setActiveTab] = useState('friends');

  return (
    <div className="container mx-auto p-4">
      <h1 className="text-2xl font-bold mb-4">Friends</h1>
      <div className="flex border-b mb-4">
        <button
          className={`px-4 py-2 ${activeTab === 'friends' ? 'border-b-2 border-blue-500' : ''}`}
          onClick={() => setActiveTab('friends')}
        >
          My Friends
        </button>
        <button
          className={`px-4 py-2 ${activeTab === 'requests' ? 'border-b-2 border-blue-500' : ''}`}
          onClick={() => setActiveTab('requests')}
        >
          Friend Requests
        </button>
        <button
          className={`px-4 py-2 ${activeTab === 'search' ? 'border-b-2 border-blue-500' : ''}`}
          onClick={() => setActiveTab('search')}
        >
          Search Users
        </button>
      </div>

      <div>
        {activeTab === 'friends' && <FriendList />}
        {activeTab === 'requests' && <FriendRequestList />}
        {activeTab === 'search' && <UserSearch />}
      </div>
    </div>
  );
}
