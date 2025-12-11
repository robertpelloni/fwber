"use client";

import { useState, useEffect, useRef } from 'react';
import { useWebSocketChat, ChatMessage, OnlineUser, useWebSocket } from '@/lib/hooks/use-websocket';
import { useAuth } from '@/lib/auth-context';
import { MessageMetadata } from '@/components/MessageStatusIndicator';
import { UserAvatar, PresenceIndicator, PresenceStatus } from '@/components/PresenceIndicator';
import { WingmanSuggestions } from '@/components/ai/WingmanSuggestions';
import AudioRecorder from '@/components/AudioRecorder';
import { api } from '@/lib/api';
import { Languages, Loader2, Sparkles } from 'lucide-react';
import { useTranslation } from '@/lib/hooks/use-translation';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { MatchInsights } from '@/components/matches/MatchInsights';

interface RealTimeChatProps {
  recipientId: string;
  recipientName?: string;
  className?: string;
}

function TranslateButton({ text, isOwnMessage }: { text: string, isOwnMessage: boolean }) {
  const { translate, isLoading } = useTranslation();
  const [translatedText, setTranslatedText] = useState<string | null>(null);

  const handleTranslate = async () => {
    if (translatedText) {
      setTranslatedText(null);
      return;
    }
    // Default to English for now
    const result = await translate(text, 'en');
    if (result) setTranslatedText(result);
  };

  return (
    <div className={`flex flex-col ${isOwnMessage ? 'items-end' : 'items-start'}`}>
      {translatedText && (
        <div className="text-xs text-gray-200 italic mb-1 border-l-2 border-blue-500 pl-2 bg-black/20 p-1 rounded">
          {translatedText}
        </div>
      )}
      <button 
        onClick={handleTranslate}
        className="text-[10px] text-gray-400 hover:text-white flex items-center gap-1 mt-1 opacity-50 hover:opacity-100 transition-opacity"
        title="Translate"
      >
        {isLoading ? <Loader2 className="h-3 w-3 animate-spin" /> : <Languages className="h-3 w-3" />}
        {translatedText ? 'Original' : 'Translate'}
      </button>
    </div>
  );
}

export default function RealTimeChat({ 
  recipientId, 
  recipientName = 'User',
  className = '' 
}: RealTimeChatProps) {
  const [message, setMessage] = useState('');
  const [isRecordingVoice, setIsRecordingVoice] = useState(false);
  const [isConnected, setIsConnected] = useState(false);
  const [loadingHistory, setLoadingHistory] = useState(true);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const { user } = useAuth();
  
  const {
    messages,
    typingIndicators,
    onlineUsers,
    sendMessage,
    handleTypingChange,
    isTyping,
  } = useWebSocketChat(recipientId);

  const { loadConversationHistory, connectionStatus } = useWebSocket();

  // Update isConnected based on global connection status
  useEffect(() => {
    setIsConnected(connectionStatus.connected);
  }, [connectionStatus.connected]);

  // Load conversation history on mount
  useEffect(() => {
    if (recipientId && user?.id) {
      setLoadingHistory(true);
      loadConversationHistory(recipientId).finally(() => {
        setLoadingHistory(false);
      });
    }
  }, [recipientId, user?.id, loadConversationHistory]);

  // Find recipient's online status
  const recipientUser = (onlineUsers as OnlineUser[]).find(u => u.user_id === recipientId);
  const recipientStatus: PresenceStatus = (recipientUser?.status as PresenceStatus) || 'offline';

  // Scroll to bottom when new messages arrive
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  // Check if recipient is typing
  const recipientTyping = typingIndicators.find(
    indicator => indicator.from_user_id === recipientId && indicator.is_typing
  );

  const handleSendMessage = (e: React.FormEvent) => {
    e.preventDefault();
    if (message.trim() && recipientId) {
      sendMessage(message.trim());
      setMessage('');
    }
  };

  const handleMessageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setMessage(value);
    handleTypingChange(value);
  };

  const handleVoiceMessage = async (audioFile: File, duration: number) => {
    if (!recipientId) return;

    const formData = new FormData();
    formData.append('receiver_id', recipientId);
    formData.append('media', audioFile);
    formData.append('media_duration', duration.toString());
    formData.append('message_type', 'audio');

    try {
      await api.post('/messages', formData);
      // Message will be received via WebSocket
    } catch (error) {
      console.error('Failed to send voice message:', error);
      // Ideally show a toast here
    }
  };

  return (
    <div className={`flex flex-col h-96 bg-gray-800 rounded-lg ${className}`}>
      {/* Chat Header */}
      <div className="flex items-center justify-between p-4 border-b border-gray-700">
        <div className="flex items-center space-x-3">
          <UserAvatar 
            name={recipientName}
            status={recipientStatus}
            size="md"
          />
          <div>
            <h3 className="text-white font-semibold">{recipientName}</h3>
            <PresenceIndicator 
              status={recipientStatus}
              lastSeen={recipientUser?.last_seen}
              showLabel
            />
          </div>
        </div>
        <div className="flex items-center space-x-2">
          <Dialog>
            <DialogTrigger asChild>
              <button 
                className="p-2 hover:bg-gray-700 rounded-full text-gray-400 hover:text-yellow-400 transition-colors"
                title="View Match Insights"
              >
                <Sparkles className="w-5 h-5" />
              </button>
            </DialogTrigger>
            <DialogContent className="bg-gray-900 border-gray-800 text-white max-w-md">
              <MatchInsights matchId={recipientId} />
            </DialogContent>
          </Dialog>

          {recipientTyping && (
            <div className="flex items-center space-x-1 text-gray-400 text-sm">
              <div className="flex space-x-1">
                <div className="w-1 h-1 bg-gray-400 rounded-full animate-bounce"></div>
                <div className="w-1 h-1 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }}></div>
                <div className="w-1 h-1 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
              </div>
              <span>typing...</span>
            </div>
          )}
        </div>
      </div>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {(messages as ChatMessage[]).length === 0 ? (
          <div className="text-center text-gray-400 py-8">
            <p>No messages yet. Start a conversation!</p>
          </div>
        ) : (
          (messages as ChatMessage[]).map((msg, index) => {
            const isOwnMessage = msg.from_user_id === user?.id;
            return (
              <div
                key={msg.message_id || msg.id || index}
                className={`flex ${isOwnMessage ? 'justify-end' : 'justify-start'}`}
              >
                <div
                  className={`max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${
                    isOwnMessage
                      ? 'bg-red-600 text-white'
                      : 'bg-gray-700 text-white'
                  }`}
                >
                  {(msg.message_type === 'audio' || msg.message?.type === 'audio') ? (
                    <div className="flex flex-col gap-1 min-w-[200px]">
                        <audio controls src={msg.media_url} className="w-full h-8" />
                        {msg.media_duration && <span className="text-xs opacity-75">{msg.media_duration}s</span>}
                    </div>
                  ) : (
                    <div>
                      <p className="text-sm">{msg.message?.content || msg.content}</p>
                      {!isOwnMessage && (msg.message?.content || msg.content) && (
                        <TranslateButton 
                          text={msg.message?.content || msg.content || ''} 
                          isOwnMessage={isOwnMessage} 
                        />
                      )}
                    </div>
                  )}
                  <MessageMetadata 
                    timestamp={msg.timestamp}
                    status={msg.status}
                    isOwnMessage={isOwnMessage}
                    className="mt-1"
                  />
                </div>
              </div>
            );
          })
        )}
        <div ref={messagesEndRef} />
      </div>

      {/* Message Input */}
      <div className="p-4 border-t border-gray-700">
        <div className="mb-2">
          <WingmanSuggestions 
            matchId={recipientId}
            onSelectSuggestion={(suggestion) => {
              setMessage(suggestion);
              handleTypingChange(suggestion);
            }}
            mode={(messages as ChatMessage[]).length === 0 ? 'ice-breaker' : 'reply'}
          />
        </div>
        <form onSubmit={handleSendMessage} className="flex space-x-2 items-center">
          <AudioRecorder 
            onRecordingComplete={handleVoiceMessage} 
            onRecordingStateChange={setIsRecordingVoice}
          />
          {!isRecordingVoice && (
            <>
              <input
                type="text"
                value={message}
                onChange={handleMessageChange}
                placeholder="Type a message..."
                className="flex-1 bg-gray-700 text-white px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                disabled={!isConnected}
              />
              <button
                type="submit"
                disabled={!message.trim() || !isConnected}
                className="bg-red-600 hover:bg-red-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg transition-colors"
              >
                Send
              </button>
            </>
          )}
        </form>
      </div>
    </div>
  );
}

/**
 * Chat list component showing all active conversations
 */
export function ChatList({ className = '' }: { className?: string }) {
  const { chatMessages, onlineUsers } = useWebSocketChat();
  
  // Group messages by conversation
  const conversations = (chatMessages as ChatMessage[]).reduce<Record<string, ChatMessage[]>>((acc, msg) => {
    const otherUserId = msg.from_user_id || msg.to_user_id || '';
    if (!acc[otherUserId]) {
      acc[otherUserId] = [];
    }
    acc[otherUserId].push(msg);
    return acc;
  }, {});

  return (
    <div className={`bg-gray-800 rounded-lg ${className}`}>
      <div className="p-4 border-b border-gray-700">
        <h3 className="text-white font-semibold">Active Conversations</h3>
      </div>
      
      <div className="divide-y divide-gray-700">
        {Object.entries(conversations).map(([userId, messages]) => {
          const lastMessage = messages[messages.length - 1];
          const isOnline = (onlineUsers as OnlineUser[]).some(user => user.user_id === userId);
          
          return (
            <div key={userId} className="p-4 hover:bg-gray-700 cursor-pointer transition-colors">
              <div className="flex items-center space-x-3">
                <div className="relative">
                  <div className="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center">
                    <span className="text-white font-bold">
                      {userId.charAt(0).toUpperCase()}
                    </span>
                  </div>
                  {isOnline && (
                    <div className="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-800"></div>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-white font-medium truncate">User {userId}</p>
                  <p className="text-gray-400 text-sm truncate">
                    {lastMessage?.message?.content || lastMessage?.content}
                  </p>
                </div>
                <div className="text-gray-400 text-xs">
                  {new Date(lastMessage.timestamp).toLocaleTimeString()}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

/**
 * Online users component
 */
export function OnlineUsers({ className = '' }: { className?: string }) {
  const { onlineUsers } = useWebSocketChat();
  
  return (
    <div className={`bg-gray-800 rounded-lg ${className}`}>
      <div className="p-4 border-b border-gray-700">
        <h3 className="text-white font-semibold">Online Users ({(onlineUsers as OnlineUser[]).length})</h3>
      </div>
      
      <div className="p-4 space-y-3">
        {(onlineUsers as OnlineUser[]).length === 0 ? (
          <p className="text-gray-400 text-center py-4">No users online</p>
        ) : (
          (onlineUsers as OnlineUser[]).map((user) => (
            <div key={user.user_id} className="flex items-center space-x-3">
              <div className="relative">
                <div className="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center">
                  <span className="text-white text-sm font-bold">
                    {user.user_id.charAt(0).toUpperCase()}
                  </span>
                </div>
                <div className="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-800"></div>
              </div>
              <div className="flex-1">
                <p className="text-white text-sm">User {user.user_id}</p>
                <p className="text-gray-400 text-xs capitalize">{user.status}</p>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
