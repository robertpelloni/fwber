'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useChatrooms, useChatroomCategories, usePopularChatrooms, useSearchChatrooms } from '@/lib/hooks/use-chatrooms';
import type { ChatroomFilters } from '@/lib/api/chatrooms';

export default function ChatroomsPage() {
  const [filters, setFilters] = useState<ChatroomFilters>({});
  const [searchQuery, setSearchQuery] = useState('');
  const [showSearch, setShowSearch] = useState(false);

  const { data: chatrooms, isLoading: chatroomsLoading } = useChatrooms(filters);
  const { data: categories } = useChatroomCategories();
  const { data: popularChatrooms } = usePopularChatrooms();
  const { data: searchResults, isLoading: searchLoading } = useSearchChatrooms(searchQuery, filters);

  const handleFilterChange = (key: keyof ChatroomFilters, value: string) => {
    setFilters(prev => ({
      ...prev,
      [key]: value || undefined,
    }));
  };

  const handleSearch = (query: string) => {
    setSearchQuery(query);
    setShowSearch(query.length >= 2);
  };

  const displayChatrooms = showSearch ? searchResults?.data : chatrooms?.data;
  const isLoading = showSearch ? searchLoading : chatroomsLoading;

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">
            💬 Real-time Chatrooms
          </h1>
          <p className="text-gray-600">
            Join location-based chatrooms and connect with people in your area
          </p>
        </div>

        {/* Search and Filters */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
          <div className="flex flex-col lg:flex-row gap-4">
            {/* Search */}
            <div className="flex-1">
              <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-2">
                Search Chatrooms
              </label>
              <input
                type="text"
                id="search"
                placeholder="Search by name or description..."
                value={searchQuery}
                onChange={(e) => handleSearch(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            {/* Type Filter */}
            <div className="lg:w-48">
              <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-2">
                Type
              </label>
              <select
                id="type"
                value={filters.type || ''}
                onChange={(e) => handleFilterChange('type', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Types</option>
                <option value="interest">💬 Interest</option>
                <option value="city">🏙️ City</option>
                <option value="event">🎉 Event</option>
                <option value="private">🔒 Private</option>
              </select>
            </div>

            {/* Category Filter */}
            <div className="lg:w-48">
              <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-2">
                Category
              </label>
              <select
                id="category"
                value={filters.category || ''}
                onChange={(e) => handleFilterChange('category', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Categories</option>
                {categories?.map((category) => (
                  <option key={category} value={category}>
                    {category}
                  </option>
                ))}
              </select>
            </div>

            {/* Sort Filter */}
            <div className="lg:w-48">
              <label htmlFor="sort" className="block text-sm font-medium text-gray-700 mb-2">
                Sort By
              </label>
              <select
                id="sort"
                value={filters.sort || 'most_active'}
                onChange={(e) => handleFilterChange('sort', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="most_active">Most Active</option>
                <option value="newest">Newest</option>
                <option value="most_members">Most Members</option>
              </select>
            </div>
          </div>
        </div>

        {/* Popular Chatrooms */}
        {!showSearch && popularChatrooms && popularChatrooms.length > 0 && (
          <div className="mb-8">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">🔥 Popular Chatrooms</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {popularChatrooms.slice(0, 6).map((chatroom) => (
                <Link
                  key={chatroom.id}
                  href={`/chatrooms/${chatroom.id}`}
                  className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow"
                >
                  <div className="flex items-start justify-between mb-2">
                    <h3 className="font-medium text-gray-900">{chatroom.display_name}</h3>
                    <span className="text-sm text-gray-500">{chatroom.member_count} members</span>
                  </div>
                  {chatroom.description && (
                    <p className="text-sm text-gray-600 mb-2 line-clamp-2">{chatroom.description}</p>
                  )}
                  <div className="flex items-center justify-between text-sm text-gray-500">
                    <span>Created by {chatroom.creator?.name}</span>
                    <span>{new Date(chatroom.last_activity_at).toLocaleDateString()}</span>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        )}

        {/* Chatrooms List */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200">
          <div className="px-6 py-4 border-b border-gray-200">
            <h2 className="text-xl font-semibold text-gray-900">
              {showSearch ? `Search Results for "${searchQuery}"` : 'All Chatrooms'}
            </h2>
          </div>

          {isLoading ? (
            <div className="p-8 text-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
              <p className="mt-2 text-gray-600">Loading chatrooms...</p>
            </div>
          ) : displayChatrooms && displayChatrooms.length > 0 ? (
            <div className="divide-y divide-gray-200">
              {displayChatrooms.map((chatroom) => (
                <Link
                  key={chatroom.id}
                  href={`/chatrooms/${chatroom.id}`}
                  className="block p-6 hover:bg-gray-50 transition-colors"
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        <h3 className="text-lg font-medium text-gray-900">{chatroom.display_name}</h3>
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          {chatroom.type}
                        </span>
                        {!chatroom.is_public && (
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Private
                          </span>
                        )}
                      </div>
                      
                      {chatroom.description && (
                        <p className="text-gray-600 mb-3 line-clamp-2">{chatroom.description}</p>
                      )}
                      
                      <div className="flex items-center gap-4 text-sm text-gray-500">
                        <span>👥 {chatroom.member_count} members</span>
                        <span>💬 {chatroom.message_count} messages</span>
                        {chatroom.city && <span>📍 {chatroom.city}</span>}
                        <span>🕒 {new Date(chatroom.last_activity_at).toLocaleDateString()}</span>
                      </div>
                    </div>
                    
                    <div className="text-right">
                      <div className="text-sm text-gray-500 mb-1">
                        Created by {chatroom.creator?.name}
                      </div>
                      <div className="text-xs text-gray-400">
                        {new Date(chatroom.created_at).toLocaleDateString()}
                      </div>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          ) : (
            <div className="p-8 text-center">
              <div className="text-gray-400 mb-4">
                <svg className="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
              </div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">No chatrooms found</h3>
              <p className="text-gray-600 mb-4">
                {showSearch 
                  ? 'Try adjusting your search terms or filters'
                  : 'Be the first to create a chatroom in your area'
                }
              </p>
              <Link
                href="/chatrooms/create"
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
              >
                Create Chatroom
              </Link>
            </div>
          )}
        </div>

        {/* Pagination */}
        {displayChatrooms && displayChatrooms.length > 0 && (
          <div className="mt-8 flex justify-center">
            <nav className="flex items-center space-x-2">
              <button className="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Previous
              </button>
              <button className="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md">
                1
              </button>
              <button className="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Next
              </button>
            </nav>
          </div>
        )}
      </div>
    </div>
  );
}
