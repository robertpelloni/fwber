'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import ProtectedRoute from '@/components/ProtectedRoute'
import { useAuth } from '@/lib/auth-context'
import { api } from '@/lib/api/client'
import AppHeader from '@/components/AppHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Loader2, Globe, Users, MessageSquare, Repeat, Heart, ArrowLeft, Radio } from 'lucide-react'
import { formatDistanceToNow } from 'date-fns'
import Image from 'next/image'

interface FederatedPost {
  id: number
  actor_uri: string
  actor_username?: string
  actor_domain?: string
  actor_avatar?: string
  content: string
  published_at: string
  url?: string | null
  metadata?: {
    name?: string
    preferredUsername?: string
    summary?: string
  }
}

interface FederationPostsResponse {
  posts?: FederatedPost[]
}

export default function GlobalFeedPage() {
  const { token } = useAuth()
  const [posts, setPosts] = useState<FederatedPost[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const fetchPosts = async () => {
      if (!token) {
        setIsLoading(false)
        return
      }

      try {
        const response = await api.get<FederationPostsResponse>('/federation/posts')
        setPosts(Array.isArray(response.posts) ? response.posts : [])
      } catch (error) {
        console.error('Failed to fetch federated posts:', error)
        setPosts([])
      } finally {
        setIsLoading(false)
      }
    }

    fetchPosts()
  }, [token])

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <AppHeader />
        <main className="max-w-4xl mx-auto px-4 py-8">
          <div className="flex flex-col gap-4 mb-8">
            <div className="flex items-center justify-between gap-4 flex-wrap">
              <div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <Globe className="w-8 h-8 text-blue-500" />
                  Global Feed
                </h1>
                <p className="text-gray-500 dark:text-gray-400 mt-1">
                  Explore recent posts from the decentralized social web via ActivityPub.
                </p>
              </div>
              <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">
                Federated
              </Badge>
            </div>

            <div className="flex flex-wrap gap-3">
              <Button asChild variant="outline" className="border-blue-200 text-blue-700 hover:bg-blue-50">
                <Link href="/settings/federation">
                  <ArrowLeft className="w-4 h-4 mr-2" />
                  Federation Settings
                </Link>
              </Button>
              <Button asChild variant="outline" className="border-purple-200 text-purple-700 hover:bg-purple-50">
                <Link href="/settings/federation/activity">
                  <Radio className="w-4 h-4 mr-2" />
                  Activity Center
                </Link>
              </Button>
            </div>
          </div>

          {isLoading ? (
            <div className="flex items-center justify-center py-20">
              <Loader2 className="w-8 h-8 animate-spin text-blue-500" />
            </div>
          ) : posts.length === 0 ? (
            <Card className="bg-white dark:bg-gray-800 border-dashed border-2">
              <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                <Users className="w-12 h-12 text-gray-300 mb-4" />
                <CardTitle className="text-xl">Your feed is empty</CardTitle>
                <p className="text-gray-500 mt-2">
                  Follow users from other servers to see their posts collected here.
                </p>
              </CardContent>
            </Card>
          ) : (
            <div className="space-y-6">
              {posts.map((post) => {
                const authorName = post.metadata?.name || post.actor_username || 'Federated User'
                const actorHandle = post.actor_domain && post.actor_username
                  ? `@${post.actor_username}@${post.actor_domain}`
                  : post.actor_uri

                return (
                  <Card key={post.id} className="overflow-hidden hover:shadow-md transition-shadow">
                    <CardHeader className="flex flex-row items-center gap-4 pb-3">
                      <div className="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 overflow-hidden relative">
                        {post.actor_avatar ? (
                          <Image src={post.actor_avatar} alt="" fill sizes="40px" className="object-cover" />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 font-bold uppercase">
                            {authorName.charAt(0)}
                          </div>
                        )}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between gap-3">
                          <p className="font-bold text-gray-900 dark:text-white truncate">
                            {authorName}
                          </p>
                          <time className="text-xs text-gray-500 whitespace-nowrap">
                            {formatDistanceToNow(new Date(post.published_at), { addSuffix: true })}
                          </time>
                        </div>
                        <p className="text-xs text-gray-500 truncate">{actorHandle}</p>
                      </div>
                    </CardHeader>
                    <CardContent>
                      <div
                        className="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200"
                        dangerouslySetInnerHTML={{ __html: post.content }}
                      />
                      <div className="flex items-center gap-6 mt-6 pt-4 border-t dark:border-gray-700 text-gray-500">
                        <button type="button" className="flex items-center gap-1.5 hover:text-blue-500 transition-colors">
                          <MessageSquare className="w-4 h-4" />
                          <span className="text-xs">Reply Soon</span>
                        </button>
                        <button type="button" className="flex items-center gap-1.5 hover:text-green-500 transition-colors">
                          <Repeat className="w-4 h-4" />
                          <span className="text-xs">Boost Soon</span>
                        </button>
                        <button type="button" className="flex items-center gap-1.5 hover:text-red-500 transition-colors">
                          <Heart className="w-4 h-4" />
                          <span className="text-xs">Like Soon</span>
                        </button>
                      </div>
                    </CardContent>
                  </Card>
                )
              })}
            </div>
          )}
        </main>
      </div>
    </ProtectedRoute>
  )
}
