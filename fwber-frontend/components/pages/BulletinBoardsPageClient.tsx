"use client";

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import { LocationCoords } from '@/lib/api/bulletin-boards';
import { useRouter } from 'next/navigation';
import { 
  useBulletinBoards, 
  useBulletinBoard, 
  useBulletinBoardMessages, 
  usePostMessage, 
  useCreateBulletinBoard 
} from '@/lib/hooks/use-bulletin-boards';
import { useBulletinBoardMercure } from '@/lib/hooks/use-mercure-sse';
import { usePostSuggestionsGeneration } from '@/lib/hooks/use-content-generation';

export default function BulletinBoardsPageClient() {
  const { user } = useAuth();
  const router = useRouter();
  
  const [location, setLocation] = useState<LocationCoords | null>(null);
  const [selectedBoardId, setSelectedBoardId] = useState<number | null>(null);
  const [newMessage, setNewMessage] = useState('');
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [showSuggestions, setShowSuggestions] = useState(false);
  
  // Use optimized hooks
  const { data: boardsData, isLoading: boardsLoading, error: boardsError } = useBulletinBoards({
    lat: location?.lat || 0,
    lng: location?.lng || 0,
    radius: 5000,
  });
  
  const { data: boardData, isLoading: boardLoading } = useBulletinBoard(
    selectedBoardId || 0,
    location || undefined
  );
  
  const { data: messagesData, isLoading: messagesLoading } = useBulletinBoardMessages(
    selectedBoardId || 0
  );
  
  const postMessageMutation = usePostMessage();
  const createBoardMutation = useCreateBulletinBoard();
  
  // Set up real-time updates with Mercure
  const { isConnected: mercureConnected } = useBulletinBoardMercure(selectedBoardId || 0);

  const { data: suggestionsData, isLoading: suggestionsLoading } = usePostSuggestionsGeneration(
    selectedBoardId || 0,
    {
      context: {
        location: location ? {
          latitude: location.lat,
          longitude: location.lng,
        } : undefined,
      }
    }
  );

  // Get user's current location
  useEffect(() => {
    if (!navigator.geolocation) {
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setLocation({
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        });
      },
      (error) => {
        console.error('Location error:', error.message);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000, // 5 minutes
      }
    );
  }, []);

  const handlePostMessage = async () => {
    if (!selectedBoardId || !newMessage.trim() || !location) return;

    postMessageMutation.mutate({
      boardId: selectedBoardId,
      content: newMessage.trim(),
      location,
      options: {
        is_anonymous: isAnonymous,
        expires_in_hours: 24, // Messages expire in 24 hours
      }
    });

    setNewMessage('');
  };

  const handleCreateBoard = async () => {
    if (!location) return;

    createBoardMutation.mutate({
      location,
      radius: 1000, // 1km radius
    });
  };

  if (!user) {
    router.push('/login');
    return null;
  }

  if (boardsLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading bulletin boards...</p>
        </div>
      </div>
    );
  }

  if (boardsError) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-red-600 text-xl mb-4">⚠️</div>
          <p className="text-red-600 mb-4">Failed to load bulletin boards</p>
          <button
            onClick={() => window.location.reload()}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-4xl mx-auto p-4">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="flex items-center justify-between mb-2">
            <h1 className="text-2xl font-bold text-gray-900">
              📍 Local Bulletin Boards
            </h1>
            <div className="flex items-center space-x-2">
              <div className={`w-2 h-2 rounded-full ${mercureConnected ? 'bg-green-500' : 'bg-red-500'}`}></div>
              <span className="text-sm text-gray-600">
                {mercureConnected ? 'Real-time' : 'Connecting...'}
              </span>
            </div>
          </div>
          <p className="text-gray-600">
            Connect with people in your area through location-based discussions
          </p>
          {location && (
            <p className="text-sm text-gray-500 mt-2">
              Your location: {location.lat.toFixed(4)}, {location.lng.toFixed(4)}
            </p>
          )}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Bulletin Boards List */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-sm p-6">
              <div className="flex justify-between items-center mb-4">
                <h2 className="text-lg font-semibold text-gray-900">
                  Nearby Boards
                </h2>
                <button
                  onClick={handleCreateBoard}
                  disabled={createBoardMutation.isPending}
                  className="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {createBoardMutation.isPending ? 'Creating...' : '+ New Board'}
                </button>
              </div>

              <div className="space-y-3">
                {boardsData?.boards.map((board) => (
                  <div
                    key={board.id}
                    onClick={() => setSelectedBoardId(board.id)}
                    className={`p-4 rounded-lg border cursor-pointer transition-colors ${
                      selectedBoardId === board.id
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <h3 className="font-medium text-gray-900">{board.name}</h3>
                    <p className="text-sm text-gray-600 mt-1">
                      {board.message_count} messages • {board.active_users} active
                    </p>
                    <p className="text-xs text-gray-500 mt-1">
                      {board.radius_meters}m radius
                    </p>
                    {board.last_activity_at && (
                      <p className="text-xs text-gray-500">
                        Last activity: {new Date(board.last_activity_at).toLocaleString()}
                      </p>
                    )}
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Messages Area */}
          <div className="lg:col-span-2">
            {selectedBoardId && boardData ? (
              <div className="bg-white rounded-lg shadow-sm">
                {/* Board Header */}
                <div className="p-6 border-b border-gray-200">
                  <h2 className="text-xl font-semibold text-gray-900">
                    {boardData.board.name}
                  </h2>
                  <p className="text-gray-600 mt-1">
                    {boardData.board.description}
                  </p>
                  <p className="text-sm text-gray-500 mt-2">
                    {boardData.board.message_count} messages • {boardData.board.active_users} active users
                  </p>
                </div>

                {/* Messages */}
                <div className="h-96 overflow-y-auto p-6 space-y-4">
                  {messagesLoading ? (
                    <div className="text-center text-gray-500 py-8">
                      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                      <p className="mt-2">Loading messages...</p>
                    </div>
                  ) : messagesData?.messages.data.length === 0 ? (
                    <div className="text-center text-gray-500 py-8">
                      <p>No messages yet. Be the first to post!</p>
                    </div>
                  ) : (
                    messagesData?.messages.data.map((message) => (
                      <div key={message.id} className="border-b border-gray-100 pb-4 last:border-b-0">
                        <div className="flex items-start space-x-3">
                          <div className="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                            {message.is_anonymous ? (
                              <span className="text-gray-600 text-sm">?</span>
                            ) : (
                              <span className="text-gray-600 text-sm">
                                {message.user?.name?.charAt(0) || 'U'}
                              </span>
                            )}
                          </div>
                          <div className="flex-1">
                            <div className="flex items-center space-x-2">
                              <span className="font-medium text-gray-900">
                                {message.is_anonymous ? 'Anonymous' : message.user?.name || 'Unknown'}
                              </span>
                              <span className="text-xs text-gray-500">
                                {new Date(message.created_at).toLocaleString()}
                              </span>
                            </div>
                            <p className="text-gray-800 mt-1">{message.content}</p>
                            {message.expires_at && (
                              <p className="text-xs text-gray-500 mt-1">
                                Expires: {new Date(message.expires_at).toLocaleString()}
                              </p>
                            )}
                          </div>
                        </div>
                      </div>
                    ))
                  )}
                </div>

                {/* Message Input */}
                <div className="p-6 border-t border-gray-200">
                  {showSuggestions && (
                    <div className="mb-4 bg-blue-50 p-4 rounded-lg">
                      <div className="flex justify-between items-center mb-2">
                        <h3 className="font-medium text-blue-900">AI Suggestions</h3>
                        <button 
                          onClick={() => setShowSuggestions(false)}
                          className="text-blue-500 hover:text-blue-700"
                        >
                          ✕
                        </button>
                      </div>
                      {suggestionsLoading ? (
                        <div className="text-center py-4">
                          <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                        </div>
                      ) : (
                        <div className="space-y-2">
                          {suggestionsData?.data?.suggestions?.map((suggestion: any, index: number) => (
                            <div 
                              key={index} 
                              className="bg-white p-3 rounded border border-blue-100 hover:border-blue-300 cursor-pointer transition-colors"
                              data-testid="suggestion-item"
                              onClick={() => {
                                setNewMessage(suggestion.content);
                                setShowSuggestions(false);
                              }}
                            >
                              <p className="text-sm text-gray-800">{suggestion.content}</p>
                              <div className="flex justify-between items-center mt-1">
                                <span className="text-xs text-blue-600" data-testid="relevance-score">
                                  Relevance: {Math.round((suggestion.confidence || 0.8) * 100)}%
                                </span>
                                <button 
                                  className="text-xs font-medium text-blue-600 hover:text-blue-800"
                                  data-testid="use-suggestion"
                                >
                                  Use This
                                </button>
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  )}

                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          id="anonymous"
                          checked={isAnonymous}
                          onChange={(e) => setIsAnonymous(e.target.checked)}
                          className="rounded border-gray-300"
                        />
                        <label htmlFor="anonymous" className="text-sm text-gray-600">
                          Post anonymously
                        </label>
                      </div>
                      <button
                        onClick={() => setShowSuggestions(!showSuggestions)}
                        className="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center space-x-1"
                        data-testid="post-suggestions"
                      >
                        <span>✨ Get AI Suggestions</span>
                      </button>
                    </div>
                    <div className="flex space-x-3">
                      <input
                        type="text"
                        value={newMessage}
                        onChange={(e) => setNewMessage(e.target.value)}
                        placeholder="Share something with your local community..."
                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onKeyPress={(e) => e.key === 'Enter' && handlePostMessage()}
                      />
                      <button
                        onClick={handlePostMessage}
                        disabled={!newMessage.trim() || postMessageMutation.isPending}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {postMessageMutation.isPending ? 'Posting...' : 'Post'}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ) : (
              <div className="bg-white rounded-lg shadow-sm p-12 text-center">
                <div className="text-gray-400 text-6xl mb-4">📍</div>
                <h3 className="text-lg font-medium text-gray-900 mb-2">
                  Select a Bulletin Board
                </h3>
                <p className="text-gray-600">
                  Choose a board from the list to start participating in local discussions
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
