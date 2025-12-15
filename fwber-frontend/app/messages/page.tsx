'use client'

import { useState, useEffect, useCallback } from 'react'
import { useAuth } from '@/lib/auth-context'
import ProtectedRoute from '@/components/ProtectedRoute'
import { getConversations, type Conversation } from '@/lib/api/messages'
import ReportModal from '@/components/ReportModal'
import ProfileViewModal from '@/components/ProfileViewModal'
import { blockUser, reportUser } from '@/lib/api/safety'
import { PresenceIndicator } from '@/components/realtime'
import { useToast } from '@/components/ToastProvider'
import { VideoCallModal } from '@/components/VideoCall/VideoCallModal'
import { useMercure } from '@/lib/contexts/MercureContext'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { CallHistory } from '@/components/VideoCall/CallHistory'
import RealTimeChat from '@/components/RealTimeChat'

export default function MessagesPage() {
  const { token, isAuthenticated } = useAuth()
  const { showError } = useToast()
  const { videoSignals } = useMercure()
  const [conversations, setConversations] = useState<Conversation[]>([])
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isReportModalOpen, setIsReportModalOpen] = useState(false)
  const [isProfileModalOpen, setIsProfileModalOpen] = useState(false)
  const [isVideoCallOpen, setIsVideoCallOpen] = useState(false)
  const [isHistoryOpen, setIsHistoryOpen] = useState(false)
  const [incomingCall, setIncomingCall] = useState<{ callerId: string } | null>(null)
  
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

  useEffect(() => {
    if (isAuthenticated && token) {
      loadConversations()
    }
  }, [isAuthenticated, token, loadConversations])

  useEffect(() => {
    const lastSignal = videoSignals[videoSignals.length - 1];
    if (lastSignal && lastSignal.signal.type === 'offer') {
       setIncomingCall({ callerId: lastSignal.from_user_id });
       setIsVideoCallOpen(true);
    }
  }, [videoSignals]);

  const handleBlock = async () => {
    const otherUser = selectedConversation?.other_user
    if (!token || !otherUser || !confirm('Are you sure you want to block this user? You will no longer see their messages or profile.')) return
    
    try {
      await blockUser(token, otherUser.id)
      setConversations(prev => prev.filter(c => c.id !== selectedConversation!.id))
      setSelectedConversation(null)
    } catch (err) {
      showError('Failed to block user')
    }
  }

  const handleReport = async (reason: string, details: string) => {
    const otherUser = selectedConversation?.other_user
    if (!token || !otherUser) return
    
    try {
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
    } catch (err) {
      showError('Failed to submit report', err instanceof Error ? err.message : 'Unknown error')
    }
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
                  onClick={() => setIsHistoryOpen(true)}
                  className="bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-700"
                >
                  Call History
                </button>
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
              <div className="lg:col-span-2 bg-white rounded-lg shadow flex flex-col overflow-hidden">
                {selectedConversation ? (
                  <RealTimeChat
                    recipientId={String(selectedConversation.other_user?.id)}
                    recipientName={selectedConversation.other_user?.profile?.display_name || 'User'}
                    className="h-full"
                    onVideoCall={() => setIsVideoCallOpen(true)}
                    onProfileView={() => setIsProfileModalOpen(true)}
                    onReport={() => setIsReportModalOpen(true)}
                    onBlock={handleBlock}
                  />
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
          messagesExchanged={0} // RealTimeChat handles message history now
          matchId={selectedConversation.id}
        />
      )}

      {(selectedConversation?.other_user || incomingCall) && (
        <VideoCallModal
          recipientId={incomingCall ? incomingCall.callerId : String(selectedConversation!.other_user!.id)}
          isOpen={isVideoCallOpen}
          onClose={() => {
              setIsVideoCallOpen(false);
              setIncomingCall(null);
          }}
          isIncoming={!!incomingCall}
        />
      )}

      <Dialog open={isHistoryOpen} onOpenChange={setIsHistoryOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <DialogHeader>
            <DialogTitle>Call History</DialogTitle>
          </DialogHeader>
          <CallHistory />
        </DialogContent>
      </Dialog>
    </ProtectedRoute>
  )
}
