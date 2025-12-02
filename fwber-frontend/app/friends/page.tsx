'use client';

import { useState } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import FriendList from '@/components/friends/FriendList';
import FriendRequestList from '@/components/friends/FriendRequestList';
import UserSearch from '@/components/friends/UserSearch';

type Tab = 'friends' | 'requests' | 'search';

export default function FriendsPage() {
  const [activeTab, setActiveTab] = useState<Tab>('friends');

  const renderTabContent = () => {
    switch (activeTab) {
      case 'friends':
        return <FriendList />;
      case 'requests':
        return <FriendRequestList />;
      case 'search':
        return <UserSearch />;
      default:
        return null;
    }
  };

  const getTabClass = (tabName: Tab) => {
    return `px-4 py-2 font-medium text-sm rounded-md ${
      activeTab === tabName
        ? 'bg-blue-600 text-white'
        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    }`;
  };

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <header className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900">Friends</h1>
            <p className="text-gray-600 mt-1">Manage your friends and friend requests.</p>
          </header>

          <div className="mb-6 border-b border-gray-200">
            <nav className="flex space-x-4">
              <button onClick={() => setActiveTab('friends')} className={getTabClass('friends')}>
                My Friends
              </button>
              <button onClick={() => setActiveTab('requests')} className={getTabClass('requests')}>
                Friend Requests
              </button>
              <button onClick={() => setActiveTab('search')} className={getTabClass('search')}>
                Find Friends
              </button>
            </nav>
          </div>

          <div>
            {renderTabContent()}
          </div>
        </div>
      </div>
    </ProtectedRoute>
  );
}
