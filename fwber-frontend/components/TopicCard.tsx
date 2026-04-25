'use client'

import Link from 'next/link'
import { Hash, Users, BookHeart, Radio } from 'lucide-react'
import { useFollowTopic, useUnfollowTopic } from '@/lib/hooks/use-topics'
import type { Topic } from '@/lib/api/topics'

interface TopicCardProps {
  topic: Topic
}

export function TopicCard({ topic }: TopicCardProps) {
  const followTopic = useFollowTopic()
  const unfollowTopic = useUnfollowTopic()

  const handleToggleFollow = (event: React.MouseEvent<HTMLButtonElement>) => {
    event.preventDefault()
    event.stopPropagation()

    if (topic.is_followed) {
      unfollowTopic.mutate(topic.slug)
      return
    }

    followTopic.mutate(topic.slug)
  }

  const isPending = followTopic.isPending || unfollowTopic.isPending

  return (
    <Link
      href={`/topics/${topic.slug}`}
      className="group block rounded-3xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    >
      <div className="flex items-start justify-between gap-4">
        <div>
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-100 text-2xl">
              {topic.emoji ?? '#'}
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">{topic.label}</h3>
                {topic.is_featured && (
                  <span className="rounded-full bg-pink-100 px-2 py-0.5 text-xs font-semibold text-pink-700">
                    Featured
                  </span>
                )}
              </div>
              <p className="mt-1 text-xs font-medium uppercase tracking-[0.2em] text-gray-500">
                {topic.category}
              </p>
            </div>
          </div>
          <p className="mt-4 max-w-xl text-sm text-gray-600">
            {topic.description || 'Browse the people, groups, field notes, and pulse around this scene.'}
          </p>
        </div>

        <button
          type="button"
          onClick={handleToggleFollow}
          disabled={isPending}
          className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
            topic.is_followed
              ? 'bg-gray-900 text-white hover:bg-gray-800'
              : 'bg-purple-100 text-purple-700 hover:bg-purple-200'
          } disabled:cursor-not-allowed disabled:opacity-60`}
        >
          {topic.is_followed ? 'Following' : 'Follow'}
        </button>
      </div>

      <div className="mt-5 flex flex-wrap gap-2 text-xs text-gray-500">
        {topic.aliases.slice(0, 4).map((alias) => (
          <span key={alias} className="rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 font-medium">
            #{alias}
          </span>
        ))}
      </div>

      <div className="mt-6 grid grid-cols-2 gap-3 text-sm text-gray-600 sm:grid-cols-4">
        <div className="rounded-2xl bg-gray-50 dark:bg-gray-900 px-3 py-3">
          <div className="flex items-center gap-2 text-gray-500">
            <Users className="h-4 w-4" />
            <span>Followers</span>
          </div>
          <div className="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{topic.follower_count}</div>
        </div>
        <div className="rounded-2xl bg-gray-50 dark:bg-gray-900 px-3 py-3">
          <div className="flex items-center gap-2 text-gray-500">
            <Hash className="h-4 w-4" />
            <span>Groups</span>
          </div>
          <div className="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{topic.group_count}</div>
        </div>
        <div className="rounded-2xl bg-gray-50 dark:bg-gray-900 px-3 py-3">
          <div className="flex items-center gap-2 text-gray-500">
            <BookHeart className="h-4 w-4" />
            <span>Journals</span>
          </div>
          <div className="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{topic.journal_count}</div>
        </div>
        <div className="rounded-2xl bg-gray-50 dark:bg-gray-900 px-3 py-3">
          <div className="flex items-center gap-2 text-gray-500">
            <Radio className="h-4 w-4" />
            <span>Pulse</span>
          </div>
          <div className="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{topic.artifact_count}</div>
        </div>
      </div>
    </Link>
  )
}
