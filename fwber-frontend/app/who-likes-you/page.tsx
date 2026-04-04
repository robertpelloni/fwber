'use client'

import { useEffect, useState } from 'react'
import Image from 'next/image'
import Link from 'next/link'
import { Crown, Lock, Heart } from 'lucide-react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/lib/auth-context'

interface LikedUser {
  id: number
  display_name?: string
  name?: string
  age?: number
  photo_url?: string | null
  avatarUrl?: string | null
  liked_at?: string
  is_locked?: boolean
}

export default function WhoLikesYouPage() {
  const { token } = useAuth()
  const [likedUsers, setLikedUsers] = useState<LikedUser[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [isLocked, setIsLocked] = useState(false)
  const [lockedCount, setLockedCount] = useState(0)

  useEffect(() => {
    const fetchLikedUsers = async () => {
      if (!token) {
        return
      }

      try {
        const response = await fetch('/api/premium/who-likes-you', {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        })

        const payload = await response.json().catch(() => ({}))

        if (response.status === 403) {
          setIsLocked(true)
          setLockedCount(Number(payload.count || 0))
          setLikedUsers([])
          return
        }

        const users = Array.isArray(payload?.users)
          ? payload.users
          : Array.isArray(payload)
            ? payload
            : []

        setIsLocked(false)
        setLikedUsers(users)
      } catch (error) {
        console.error('Failed to fetch liked users', error)
        setLikedUsers([])
      } finally {
        setIsLoading(false)
      }
    }

    fetchLikedUsers()
  }, [token])

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Who Likes You" />
        <main className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
          <div className="mb-8 rounded-3xl border border-pink-200 bg-white p-8 shadow-sm dark:border-pink-900/40 dark:bg-gray-900">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div className="max-w-2xl">
                <h1 className="text-4xl font-black tracking-tight text-gray-900 dark:text-white">People who liked you</h1>
                <p className="mt-3 text-gray-600 dark:text-gray-300">
                  Gold Premium restores the fast path: instead of waiting to discover admirers one by one, open the list directly and message from signal instead of guesswork.
                </p>
              </div>
              <div className="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-pink-500 to-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-lg">
                <Heart className="h-4 w-4 fill-current" />
                Premium Visibility
              </div>
            </div>
          </div>

          {isLoading ? (
            <div className="flex justify-center py-16">
              <div className="h-12 w-12 animate-spin rounded-full border-b-2 border-pink-500" />
            </div>
          ) : isLocked ? (
            <div className="rounded-3xl border border-yellow-200 bg-white p-10 text-center shadow-sm dark:border-yellow-900/40 dark:bg-gray-900">
              <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                <Lock className="h-8 w-8 text-yellow-700 dark:text-yellow-300" />
              </div>
              <h2 className="text-2xl font-black text-gray-900 dark:text-white">Upgrade to unlock admirer visibility</h2>
              <p className="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-300">
                {lockedCount > 0
                  ? `${lockedCount} people have already liked you. Upgrade to Gold to reveal them instantly.`
                  : 'Gold Premium reveals incoming likes as soon as they happen.'}
              </p>
              <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <Link href="/premium">
                  <Button className="w-full bg-gradient-to-r from-yellow-500 to-yellow-700 text-white hover:from-yellow-600 hover:to-yellow-800 sm:w-auto">
                    <Crown className="mr-2 h-4 w-4" />
                    Upgrade to Gold
                  </Button>
                </Link>
                <Link href="/matches">
                  <Button variant="outline" className="w-full sm:w-auto">Keep swiping</Button>
                </Link>
              </div>
            </div>
          ) : likedUsers.length > 0 ? (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {likedUsers.map((user) => {
                const isUserLocked = Boolean(user.is_locked)
                const imageSrc = user.photo_url || user.avatarUrl || '/placeholder-user.jpg'
                const displayName = user.display_name || user.name || 'Someone nearby'

                return (
                  <div key={user.id} className="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div className="relative h-72 w-full">
                      <Image
                        src={imageSrc}
                        alt={displayName}
                        fill
                        className={`object-cover ${isUserLocked ? 'blur-lg scale-110' : ''}`}
                      />
                      {isUserLocked && (
                        <div className="absolute inset-0 flex items-center justify-center bg-black/35">
                          <span className="rounded-full bg-black/60 px-4 py-2 text-sm font-semibold text-white">Upgrade to reveal</span>
                        </div>
                      )}
                    </div>
                    <div className="p-5">
                      <h3 className="text-xl font-bold text-gray-900 dark:text-white">
                        {isUserLocked ? 'Someone' : displayName}
                        {!isUserLocked && user.age ? `, ${user.age}` : ''}
                      </h3>
                      <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        {isUserLocked ? 'This admirer is hidden behind Gold Premium.' : 'Liked you recently and is waiting for your move.'}
                      </p>
                    </div>
                  </div>
                )
              })}
            </div>
          ) : (
            <div className="rounded-3xl border border-dashed border-gray-300 bg-white p-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
              <p className="text-lg text-gray-600 dark:text-gray-300">No new likes yet. Keep your profile fresh and stay active nearby.</p>
            </div>
          )}
        </main>
      </div>
    </ProtectedRoute>
  )
}
