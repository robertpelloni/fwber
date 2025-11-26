'use client';

import { useState, useRef, useEffect, useMemo, useCallback } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useChatroom, useChatroomMessages, useSendMessage, useAddReaction, useRemoveReaction } from '@/lib/hooks/use-chatrooms';
import { useAuth } from '@/lib/auth-context';
import { 
  ConnectionStatusBadge, 
  PresenceIndicator,
  TypingIndicator,
  OnlineUsersList,
  PresenceProvider
} from '@/components/realtime';

export default function ChatroomPage() {
  const params = useParams();
  const router = useRouter();
  const { user } = useAuth();
  const chatroomId = parseInt(params.id as string);
  
  const [newMessage, setNewMessage] = useState('');
  const [isTyping, setIsTyping] = useState(false);
  const [showOnlineUsers, setShowOnlineUsers] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const typingTimeoutRef = useRef<NodeJS.Timeout>();

  const { data: chatroomData, isLoading: chatroomLoading } = useChatroom(chatroomId);
  const { data: messagesData, isLoading: messagesLoading } = useChatroomMessages(chatroomId);
  const sendMessageMutation = useSendMessage();
  const addReactionMutation = useAddReaction();
  const removeReactionMutation = useRemoveReaction();

  const chatroom = chatroomData?.chatroom;
  const messages = useMemo(() => messagesData?.data || [], [messagesData?.data]);
  
  // Get member IDs for presence tracking
  const memberIds = useMemo(() => {
    return chatroom?.members?.map((m: { user_id: number }) => String(m.user_id)) || [];
  }, [chatroom?.members]);

  // Auto-scroll to bottom when new messages arrive
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  // Handle typing indicator
  const handleTyping = () => {
    setIsTyping(true);
    if (typingTimeoutRef.current) {
      clearTimeout(typingTimeoutRef.current);
    }
    typingTimeoutRef.current = setTimeout(() => {
      setIsTyping(false);
    }, 1000);
  };

  const handleSendMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newMessage.trim() || sendMessageMutation.isPending) return;

    try {
      await sendMessageMutation.mutateAsync({
        chatroomId,
        data: {
          content: newMessage.trim(),
          message_type: 'text',
        },
      });
      setNewMessage('');
    } catch (error) {
      console.error('Failed to send message:', error);
    }
  };

  const handleReaction = async (messageId: number, emoji: string) => {
    try {
      // Check if user already reacted with this emoji
      const message = messages.find(m => m.id === messageId);
      const userReaction = message?.reactions?.find(r => r.user_id === user?.id && r.emoji === emoji);
      
      if (userReaction) {
        await removeReactionMutation.mutateAsync({
          chatroomId,
          messageId,
          data: { emoji },
        });
      } else {
        await addReactionMutation.mutateAsync({
          chatroomId,
          messageId,
          data: { emoji },
        });
      }
    } catch (error) {
      console.error('Failed to toggle reaction:', error);
    }
  };

  const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60);
    
    if (diffInHours < 1) {
      const diffInMinutes = Math.floor(diffInHours * 60);
      return `${diffInMinutes}m ago`;
    } else if (diffInHours < 24) {
      return `${Math.floor(diffInHours)}h ago`;
    } else {
      return date.toLocaleDateString();
    }
  };

  if (chatroomLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading chatroom...</p>
        </div>
      </div>
    );
  }

  if (!chatroom) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Chatroom not found</h1>
          <p className="text-gray-600 mb-4">The chatroom you&apos;re looking for doesn&apos;t exist or you don&apos;t have access to it.</p>
          <button
            onClick={() => router.push('/chatrooms')}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
          >
            Back to Chatrooms
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Chatroom Header */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
          <div className="flex items-start justify-between">
            <div>
              <div className="flex items-center gap-3 mb-2">
                <h1 className="text-2xl font-bold text-gray-900">{chatroom.display_name}</h1>
                <ConnectionStatusBadge />
              </div>
              {chatroom.description && (
                <p className="text-gray-600 mb-4">{chatroom.description}</p>
              )}
              <div className="flex items-center gap-4 text-sm text-gray-500">
                <button 
                  onClick={() => setShowOnlineUsers(!showOnlineUsers)}
                  className="flex items-center gap-1 hover:text-blue-600 transition-colors"
                >
                  👥 {chatroom.member_count} members
                </button>
                <span>💬 {chatroom.message_count} messages</span>
                {chatroom.city && <span>📍 {chatroom.city}</span>}
                <span>🕒 Last active {formatTime(chatroom.last_activity_at)}</span>
              </div>
              
              {/* Online Users Panel */}
              {showOnlineUsers && (
                <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                  <OnlineUsersList userIds={memberIds} maxDisplay={8} />
                </div>
              )}
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
        </div>

        {/* Chat Interface */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col h-[600px]">
          {/* Messages Area */}
          <div className="flex-1 overflow-y-auto p-6 space-y-4">
            {messagesLoading ? (
              <div className="text-center py-8">
                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                <p className="mt-2 text-gray-600">Loading messages...</p>
              </div>
            ) : messages.length > 0 ? (
              messages.map((message) => (
                <div key={message.id} className="flex items-start space-x-3">
                  <div className="flex-shrink-0 relative">
                    <div className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                      {message.user?.name?.charAt(0).toUpperCase() || '?'}
                    </div>
                    <div className="absolute -bottom-0.5 -right-0.5">
                      <PresenceIndicator userId={String(message.user?.id || message.user_id)} size="sm" />
                    </div>
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center space-x-2 mb-1">
                      <span className="text-sm font-medium text-gray-900">
                        {message.display_user}
                      </span>
                      <span className="text-xs text-gray-500">
                        {formatTime(message.created_at)}
                      </span>
                      {message.is_edited && (
                        <span className="text-xs text-gray-400">(edited)</span>
                      )}
                    </div>
                    <p className="text-gray-900 whitespace-pre-wrap">{message.display_content}</p>
                    
                    {/* Reactions */}
                    {message.reactions && message.reactions.length > 0 && (
                      <div className="flex flex-wrap gap-1 mt-2">
                        {Object.entries(message.reaction_summary).map(([emoji, count]) => (
                          <button
                            key={emoji}
                            onClick={() => handleReaction(message.id, emoji)}
                            className="inline-flex items-center px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded-full transition-colors"
                          >
                            <span className="mr-1">{emoji}</span>
                            <span>{count}</span>
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-8">
                <div className="text-gray-400 mb-4">
                  <svg className="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
                <h3 className="text-lg font-medium text-gray-900 mb-2">No messages yet</h3>
                <p className="text-gray-600">Start the conversation by sending the first message!</p>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>

          {/* Message Input */}
          <div className="border-t border-gray-200 p-4">
            <form onSubmit={handleSendMessage} className="flex space-x-3">
              <div className="flex-1">
                <textarea
                  value={newMessage}
                  onChange={(e) => {
                    setNewMessage(e.target.value);
                    handleTyping();
                  }}
                  placeholder="Type your message..."
                  rows={2}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                  disabled={sendMessageMutation.isPending}
                />
              </div>
              <button
                type="submit"
                disabled={!newMessage.trim() || sendMessageMutation.isPending}
                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {sendMessageMutation.isPending ? 'Sending...' : 'Send'}
              </button>
            </form>
            
            {/* Typing Indicator - Real-time from WebSocket or local state */}
            <TypingIndicator contextId={String(chatroomId)} contextType="chatroom" className="mt-2" />
            {isTyping && (
              <div className="mt-2 text-sm text-gray-500">
                <span className="animate-pulse">You are typing...</span>
              </div>
            )}
          </div>
        </div>

        {/* Quick Reactions */}
        <div className="mt-4 flex items-center space-x-2">
          <span className="text-sm text-gray-500">Quick reactions:</span>
          {['👍', '❤️', '😂', '😮', '😢', '😡'].map((emoji) => (
            <button
              key={emoji}
              onClick={() => {
                // This would need to be implemented to react to the last message
                console.log('React with:', emoji);
              }}
              className="text-lg hover:scale-110 transition-transform"
            >
              {emoji}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
