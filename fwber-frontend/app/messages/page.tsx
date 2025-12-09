'use client'

import { useState, useEffect, useRef, useCallback } from 'react'
import Image from 'next/image'
import { useAuth } from '@/lib/auth-context'
import ProtectedRoute from '@/components/ProtectedRoute'
import { getConversations, getMessages, sendMessage, markMessagesAsRead, type Conversation, type Message } from '@/lib/api/messages'
import ReportModal from '@/components/ReportModal'
import ProfileViewModal from '@/components/ProfileViewModal'
import { blockUser, reportUser } from '@/lib/api/safety'
import { PresenceIndicator, ConnectionStatusBadge, TypingIndicator } from '@/components/realtime'
import AudioRecorder from '@/components/AudioRecorder'

export default function MessagesPage() {
  const { token, isAuthenticated, user } = useAuth()
  const [conversations, setConversations] = useState<Conversation[]>([])
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null)
  const [messages, setMessages] = useState<Message[]>([])
  const [newMessage, setNewMessage] = useState('')
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [isSending, setIsSending] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [isReportModalOpen, setIsReportModalOpen] = useState(false)
  const [isProfileModalOpen, setIsProfileModalOpen] = useState(false)
  const [showSafetyMenu, setShowSafetyMenu] = useState(false)
  const messagesEndRef = useRef<HTMLDivElement>(null)
  
  const BACKEND_URL = process.env.NEXT_PUBLIC_API_URL?.replace('/api', '') || 'http://localhost:8000'

  const loadConversations = useCallback(async () => {
    if (!token) return

    try {
      setIsLoading(true)
      setError(null)
      const conversationsData = await getConversations(token)
      setConversations(conversationsData)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load conversations')
    } finally {
      setIsLoading(false)
    }
  }, [token])

  const loadMessages = useCallback(async (conversationId: number) => {
    if (!token || !selectedConversation?.other_user?.id) return

    try {
      setError(null)
      // Use other_user.id instead of conversationId (which is match ID)
      const messagesData = await getMessages(token, selectedConversation.other_user.id)
      setMessages(messagesData)
      
      // Mark messages as read
      await markMessagesAsRead(token, selectedConversation.other_user.id)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load messages')
    }
  }, [token, selectedConversation])

  useEffect(() => {
    if (isAuthenticated && token) {
      loadConversations()
    }
  }, [isAuthenticated, token, loadConversations])

  useEffect(() => {
    if (selectedConversation && token) {
      loadMessages(selectedConversation.id)
    }
  }, [selectedConversation, token, loadMessages])

  useEffect(() => {
    scrollToBottom()
  }, [messages])

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setSelectedFile(e.target.files[0])
    }
  }

  const handleVoiceMessage = async (audioFile: File, duration: number) => {
    if (!token || !selectedConversation?.other_user?.id) return

    try {
      setIsSending(true)
      setError(null)

      const message = await sendMessage(
        token, 
        selectedConversation.other_user.id, 
        '', 
        audioFile,
        'audio'
      )
      setMessages(prev => [...prev, message])
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to send voice message')
    } finally {
      setIsSending(false)
    }
  }

  const handleSendMessage = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!token || !selectedConversation?.other_user?.id || (!newMessage.trim() && !selectedFile)) return

    try {
      setIsSending(true)
      setError(null)

      const message = await sendMessage(
        token, 
        selectedConversation.other_user.id, 
        newMessage.trim(),
        selectedFile
      )
      setMessages(prev => [...prev, message])
      setNewMessage('')
      setSelectedFile(null)
      if (fileInputRef.current) fileInputRef.current.value = ''
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to send message')
    } finally {
      setIsSending(false)
    }
  }

  const handleBlock = async () => {
    const otherUser = selectedConversation?.other_user
    if (!token || !otherUser || !confirm('Are you sure you want to block this user? You will no longer see their messages or profile.')) return
    
    try {
      await blockUser(token, otherUser.id)
      setConversations(prev => prev.filter(c => c.id !== selectedConversation!.id))
      setSelectedConversation(null)
      setShowSafetyMenu(false)
    } catch (err) {
      alert('Failed to block user')
    }
  }

  const handleReport = async (reason: string, details: string) => {
    const otherUser = selectedConversation?.other_user
    if (!token || !otherUser) return
    await reportUser(token, otherUser.id, reason, details)
    
    if (confirm('Report submitted. Do you want to block this user as well?')) {
      try {
        await blockUser(token, otherUser.id)
        setConversations(prev => prev.filter(c => c.id !== selectedConversation!.id))
        setSelectedConversation(null)
      } catch (err) {
        console.error('Failed to block after report', err)
      }
    }
    setShowSafetyMenu(false)
  }

  const formatMessageTime = (timestamp: string) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)

    if (diffInHours < 24) {
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } else if (diffInHours < 168) { // 7 days
      return date.toLocaleDateString([], { weekday: 'short', hour: '2-digit', minute: '2-digit' })
    } else {
      return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
    }
  }

  if (isLoading) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen flex items-center justify-center">
          <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-500"></div>
        </div>
      </ProtectedRoute>
    )
  }

  if (error) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-center">
            <div className="text-red-500 text-xl mb-4">Error: {error}</div>
            <button
              onClick={loadConversations}
              className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
              Try Again
            </button>
          </div>
        </div>
      </ProtectedRoute>
    )
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <header className="bg-white shadow">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex justify-between items-center py-6">
              <div>
                <h1 className="text-3xl font-bold text-gray-900">Messages</h1>
                <p className="text-gray-600">
                  {conversations.length} conversation{conversations.length !== 1 ? 's' : ''}
                </p>
              </div>
              <div className="flex items-center space-x-4">
                <button
                  onClick={loadConversations}
                  className="bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-700"
                >
                  Refresh
                </button>
                <a
                  href="/friends"
                  className="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700"
                >
                  Friends
                </a>
                <a
                  href="/dashboard"
                  className="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700"
                >
                  Dashboard
                </a>
              </div>
            </div>
          </div>
        </header>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {conversations.length === 0 ? (
            <div className="text-center">
              <h2 className="text-2xl font-bold text-gray-900 mb-4">No Conversations Yet</h2>
              <p className="text-gray-600 mb-8">
                Start matching with people to begin conversations!
              </p>
              <a
                href="/matches"
                className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700"
              >
                Find Matches
              </a>
            </div>
          ) : (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[calc(100vh-200px)]">
              {/* Conversations List */}
              <div className="lg:col-span-1 bg-white rounded-lg shadow">
                <div className="p-4 border-b">
                  <h2 className="text-lg font-semibold text-gray-900">Conversations</h2>
                </div>
                <div className="overflow-y-auto h-[calc(100%-80px)]">
                  {conversations.map((conversation) => (
                    <div
                      key={conversation.id}
                      onClick={() => setSelectedConversation(conversation)}
                      className={`p-4 border-b cursor-pointer hover:bg-gray-50 ${
                        selectedConversation?.id === conversation.id ? 'bg-blue-50 border-blue-200' : ''
                      }`}
                    >
                      <div className="flex items-center space-x-3">
                        <div className="relative">
                          <div className="w-12 h-12 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold">
                            {conversation.other_user?.profile?.display_name?.[0] || '?'}
                          </div>
                          <div className="absolute -bottom-0.5 -right-0.5">
                            <PresenceIndicator userId={String(conversation.other_user?.id)} size="sm" />
                          </div>
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium text-gray-900 truncate">
                            {conversation.other_user?.profile?.display_name || 'Anonymous'}
                          </p>
                          <p className="text-sm text-gray-500 truncate">
                            {conversation.last_message?.content || 'No messages yet'}
                          </p>
                        </div>
                        {conversation.last_message && (
                          <div className="text-xs text-gray-400">
                            {formatMessageTime(conversation.last_message.created_at)}
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Messages Area */}
              <div className="lg:col-span-2 bg-white rounded-lg shadow flex flex-col">
                {selectedConversation ? (
                  <>
                    {/* Chat Header */}
                    <div className="p-4 border-b flex justify-between items-center">
                      <div className="flex items-center space-x-3">
                        <div className="relative">
                          <div className="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold">
                            {selectedConversation.other_user?.profile?.display_name?.[0] || '?'}
                          </div>
                          <div className="absolute -bottom-0.5 -right-0.5">
                            <PresenceIndicator userId={String(selectedConversation.other_user?.id)} size="sm" />
                          </div>
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <h3 className="text-lg font-semibold text-gray-900">
                              {selectedConversation.other_user?.profile?.display_name || 'Anonymous'}
                            </h3>
                            <ConnectionStatusBadge />
                          </div>
                          <div className="flex items-center gap-2">
                            <PresenceIndicator userId={String(selectedConversation.other_user?.id)} showLabel size="sm" />
                            <span className="text-gray-300">•</span>
                            <p className="text-sm text-gray-500">
                              {selectedConversation.other_user?.profile?.age && 
                                `${selectedConversation.other_user.profile.age} years old`
                              }
                            </p>
                            <button
                              onClick={() => setIsProfileModalOpen(true)}
                              className="text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline"
                            >
                              View Profile
                            </button>
                          </div>
                        </div>
                      </div>
                      
                      <div className="relative">
                        <button
                          onClick={() => setShowSafetyMenu(!showSafetyMenu)}
                          className="p-2 text-gray-500 hover:text-gray-700 rounded-full hover:bg-gray-100"
                          aria-label="Chat options"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                          </svg>
                        </button>
                        
                        {showSafetyMenu && (
                          <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                            <div className="py-1">
                              <button
                                onClick={() => {
                                  setIsReportModalOpen(true)
                                  setShowSafetyMenu(false)
                                }}
                                className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                              >
                                Report User
                              </button>
                              <button
                                onClick={handleBlock}
                                className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                              >
                                Block User
                              </button>
                            </div>
                          </div>
                        )}
                      </div>
                    </div>

                    {/* Messages */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-4">
                      {messages.map((message) => (
                        <div
                          key={message.id}
                          className={`flex ${message.sender_id === user?.id ? 'justify-end' : 'justify-start'}`}
                        >
                          <div
                            className={`max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${
                              message.sender_id === user?.id
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 text-gray-900'
                            }`}
                          >
                            {message.media_url && (
                              <div className="mb-2">
                                {message.message_type === 'image' ? (
                                  <Image 
                                    src={message.media_url.startsWith('http') ? message.media_url : `${BACKEND_URL}${message.media_url}`} 
                                    alt="Attachment" 
                                    width={0}
                                    height={0}
                                    sizes="100vw"
                                    className="w-full h-auto rounded-lg"
                                    loading="lazy"
                                  />
                                ) : message.message_type === 'video' ? (
                                  <video 
                                    src={message.media_url.startsWith('http') ? message.media_url : `${BACKEND_URL}${message.media_url}`} 
                                    controls 
                                    className="max-w-full rounded-lg"
                                  />
                                ) : message.message_type === 'audio' ? (
                                  <audio 
                                    src={message.media_url.startsWith('http') ? message.media_url : `${BACKEND_URL}${message.media_url}`} 
                                    controls 
                                    className="w-full"
                                  />
                                ) : (
                                  <a 
                                    href={message.media_url.startsWith('http') ? message.media_url : `${BACKEND_URL}${message.media_url}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className={`flex items-center gap-2 underline ${message.sender_id === user?.id ? 'text-blue-100' : 'text-blue-600'}`}
                                  >
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Download File
                                  </a>
                                )}
                              </div>
                            )}
                            
                            {message.content && <p className="text-sm">{message.content}</p>}
                            <p className={`text-xs mt-1 ${
                              message.sender_id === user?.id ? 'text-blue-100' : 'text-gray-500'
                            }`}>
                              {formatMessageTime(message.created_at)}
                            </p>
                          </div>
                        </div>
                      ))}
                      <div ref={messagesEndRef} />
                    </div>

                    {/* Message Input */}
                    <div className="p-4 border-t">
                      {/* Typing Indicator */}
                      <TypingIndicator 
                        contextId={String(selectedConversation.other_user?.id)} 
                        contextType="user" 
                        className="mb-2"
                      />
                      {selectedFile && (
                        <div className="mb-2 px-3 py-1 bg-gray-100 rounded flex justify-between items-center">
                          <span className="text-sm text-gray-600 truncate max-w-xs">{selectedFile.name}</span>
                          <button 
                            onClick={() => {
                              setSelectedFile(null)
                              if (fileInputRef.current) fileInputRef.current.value = ''
                            }}
                            className="text-gray-500 hover:text-red-500"
                          >
                            ×
                          </button>
                        </div>
                      )}
                      <form onSubmit={handleSendMessage} className="flex space-x-2 items-center">
                        <button
                          type="button"
                          onClick={() => fileInputRef.current?.click()}
                          className="p-2 text-gray-500 hover:text-blue-600 hover:bg-gray-100 rounded-full transition-colors"
                          title="Attach file"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                          </svg>
                        </button>
                        <AudioRecorder onRecordingComplete={handleVoiceMessage} isSending={isSending} />
                        <input
                          type="file"
                          ref={fileInputRef}
                          onChange={handleFileSelect}
                          className="hidden"
                          accept="image/*,video/*,audio/*"
                          title="Attach file"
                        />
                        <input
                          type="text"
                          value={newMessage}
                          onChange={(e) => setNewMessage(e.target.value)}
                          placeholder="Type a message..."
                          className="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          disabled={isSending}
                        />
                        <button
                          type="submit"
                          disabled={isSending || (!newMessage.trim() && !selectedFile)}
                          className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          {isSending ? 'Sending...' : 'Send'}
                        </button>
                      </form>
                    </div>
                  </>
                ) : (
                  <div className="flex-1 flex items-center justify-center">
                    <div className="text-center">
                      <h3 className="text-lg font-medium text-gray-900 mb-2">Select a Conversation</h3>
                      <p className="text-gray-500">Choose a conversation from the list to start messaging</p>
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
      
      {selectedConversation && (
        <ReportModal
          isOpen={isReportModalOpen}
          onClose={() => setIsReportModalOpen(false)}
          onSubmit={handleReport}
          userName={selectedConversation.other_user?.profile?.display_name || 'User'}
        />
      )}

      {selectedConversation && selectedConversation.other_user && (
        <ProfileViewModal
          isOpen={isProfileModalOpen}
          onClose={() => setIsProfileModalOpen(false)}
          user={selectedConversation.other_user}
          messagesExchanged={messages.length}
          matchId={selectedConversation.id}
        />
      )}
    </ProtectedRoute>
  )
}
