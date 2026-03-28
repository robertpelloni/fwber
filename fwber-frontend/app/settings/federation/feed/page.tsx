'use client'

import { useEffect, useState } from 'react'
import { useAuth } from '@/lib/auth-context'
import { apiClient } from '@/lib/api/client'
import AppHeader from '@/components/AppHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Loader2, Globe, Users, MessageSquare, Repeat, Heart } from 'lucide-react'
import { formatDistanceToNow } from 'date-fns'
import Image from 'next/image'

interface FederatedPost {
  id: number
  actor_id: string
  content: string
  published_at: string
  url: string
  author_name?: string
  author_avatar?: string
}

export default function GlobalFeedPage() {
  const { token } = useAuth()
  const [posts, setPosts] = useState<FederatedPost[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const fetchPosts = async () => {
      if (!token) return
      try {
        const response = await apiClient.get<FederatedPost[]>('/federation/posts')
        setPosts(response.data)
      } catch (error) {
        console.error('Failed to fetch federated posts:', error)
      } finally {
        setIsLoading(false)
      }
    }

    fetchPosts()
  }, [token])

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <AppHeader />
      <main className="max-w-4xl mx-auto px-4 py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Globe className="w-8 h-8 text-blue-500" />
              Global Feed
            </h1>
            <p className="text-gray-500 dark:text-gray-400 mt-1">
              Explore posts from the decentralized social web via ActivityPub.
            </p>
          </div>
          <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">
            Federated
          </Badge>
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
                Follow users from other servers to see their posts here.
              </p>
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-6">
            {posts.map((post) => (
              <Card key={post.id} className="overflow-hidden hover:shadow-md transition-shadow">
                <CardHeader className="flex flex-row items-center gap-4 pb-3">
                  <div className="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 overflow-hidden relative">
                    {post.author_avatar ? (
                      <Image src={post.author_avatar} alt="" fill sizes="40px" className="object-cover" />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 font-bold uppercase">
                        {(post.author_name || post.actor_id).charAt(0)}
                      </div>
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between">
                      <p className="font-bold text-gray-900 dark:text-white truncate">
                        {post.author_name || 'Anonymous'}
                      </p>
                      <time className="text-xs text-gray-500">
                        {formatDistanceToNow(new Date(post.published_at), { addSuffix: true })}
                      </time>
                    </div>
                    <p className="text-xs text-gray-500 truncate">{post.actor_id}</p>
                  </div>
                </CardHeader>
                <CardContent>
                  <div 
                    className="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200"
                    dangerouslySetInnerHTML={{ __html: post.content }}
                  />
                  <div className="flex items-center gap-6 mt-6 pt-4 border-t dark:border-gray-700 text-gray-500">
                    <button className="flex items-center gap-1.5 hover:text-blue-500 transition-colors">
                      <MessageSquare className="w-4 h-4" />
                      <span className="text-xs">Reply</span>
                    </button>
                    <button className="flex items-center gap-1.5 hover:text-green-500 transition-colors">
                      <Repeat className="w-4 h-4" />
                      <span className="text-xs">Boost</span>
                    </button>
                    <button className="flex items-center gap-1.5 hover:text-red-500 transition-colors">
                      <Heart className="w-4 h-4" />
                      <span className="text-xs">Like</span>
                    </button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </main>
    </div>
  )
}
