'use client';

import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Info, Hash, Plus, MessageCircle } from 'lucide-react';
import { toast } from 'react-toastify';

export default function TopicsPage() {
    const [topics, setTopics] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchTopics = async () => {
            try {
                const res = await fetch('/api/topics');
                if (!res.ok) throw new Error('Failed to fetch topics');
                const data = await res.json();
                setTopics(data.topics || []);
            } catch (e: any) {
                toast.error(e.message || 'Error loading topics');
            } finally {
                setLoading(false);
            }
        };
        fetchTopics();
    }, []);

    return (
        <div className="container mx-auto p-4 md:p-8 max-w-5xl space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
                    <Hash className="w-8 h-8 text-primary" />
                    Conversational Topics
                </h1>
                <Button className="flex items-center gap-2">
                    <Plus className="w-4 h-4" /> Suggest Topic
                </Button>
            </div>

            <Card className="bg-white shadow-sm border border-gray-100 mb-8">
                <CardHeader>
                    <CardTitle className="text-xl flex items-center gap-2">
                        Trending Discussions
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Info className="w-4 h-4 text-gray-400 cursor-help" />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Select a topic to find matching local profiles and chat rooms discussing these subjects.</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </CardTitle>
                    <CardDescription>
                        Explore what the local community is talking about and jump into the conversation.
                    </CardDescription>
                </CardHeader>
            </Card>

            {loading ? (
                <div className="flex justify-center p-12"><div className="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full" /></div>
            ) : topics.length === 0 ? (
                <div className="text-center p-12 bg-gray-50 rounded-lg border border-gray-200">
                    <MessageCircle className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-gray-700">No active topics</h3>
                    <p className="text-gray-500 mb-4">Be the first to suggest a conversational topic to the community!</p>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    {topics.map((topic) => (
                        <Card key={topic.id} className="hover:shadow-md transition-shadow cursor-pointer border border-primary/10">
                            <CardContent className="p-4 flex flex-col justify-between h-full">
                                <div>
                                    <h3 className="font-bold text-lg text-primary flex items-center gap-1">
                                        <Hash className="w-4 h-4" /> {topic.name}
                                    </h3>
                                    <p className="text-gray-600 text-sm mt-2 mb-4 line-clamp-2">{topic.description}</p>
                                <div className="text-xs text-gray-500 font-medium">
                                    {topic.activeParticipants || 0} local people discussing this
                            </CardContent>
                    ))}
            )}
    );

'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import AppHeader from '@/components/AppHeader'
import ProtectedRoute from '@/components/ProtectedRoute'
import { TopicCard } from '@/components/TopicCard'
import { useTopics } from '@/lib/hooks/use-topics'
import { Compass, Search } from 'lucide-react'

  const [search, setSearch] = useState('')
  const [featuredOnly, setFeaturedOnly] = useState(false)
  const [followedOnly, setFollowedOnly] = useState(false)
  const { data: topics = [], isLoading } = useTopics({
    search: search.trim() || undefined,
    featured: featuredOnly || undefined,
    followed: followedOnly || undefined,
  })

  const categories = useMemo(
    () => Array.from(new Set((Array.isArray(topics) ? topics : []).map((topic) => topic.category).filter(Boolean))).sort(),
    [topics]
  )

    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <AppHeader title="Topic Hubs" />
        <main className="px-4 py-8 sm:px-6 lg:px-8">
          <div className="mx-auto max-w-7xl space-y-8">
            <section className="rounded-3xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
              <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div className="max-w-3xl">
                  <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-2xl bg-purple-100 p-3 text-purple-700">
                      <Compass className="h-6 w-6" />
                      <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Structured topic hubs</h1>
                      <p className="mt-2 text-sm text-gray-600">
                        Browse the scenes forming across interests, Local Pulse posts, public groups, and visible field notes.
                      </p>
                  <div className="flex flex-wrap gap-2 text-xs text-gray-500">
                    {categories.map((category) => (
                      <span key={category} className="rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 font-medium uppercase tracking-wide">
                        {category}
                      </span>

                <div className="w-full max-w-xl space-y-3">
                  <label className="relative block">
                    <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                      value={search}
                      onChange={(event) => setSearch(event.target.value)}
                      placeholder="Search music, wellness, nightlife..."
                      className="w-full rounded-2xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-3 pl-11 pr-4 text-sm outline-none transition focus:border-purple-500 focus:ring-2 focus:ring-purple-100"
                    />
                  </label>
                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() => setFeaturedOnly((value) => !value)}
                      className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                        featuredOnly ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'
                      }`}
                    >
                      Featured
                    </button>
                      onClick={() => setFollowedOnly((value) => !value)}
                        followedOnly ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700'
                      Following
                    <Link
                      href="/local-pulse"
                      className="rounded-full bg-pink-100 px-4 py-2 text-sm font-semibold text-pink-700 transition hover:bg-pink-200"
                      Open Local Pulse
                    </Link>
            </section>

            {isLoading ? (
              <div className="rounded-3xl border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-6 py-16 text-center text-sm text-gray-500">
                Loading topic hubs...
              <div className="rounded-3xl border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-6 py-16 text-center">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">No topics matched that filter.</h2>
                  Try a broader scene name or clear one of the pills above.
              <section className="grid gap-6 xl:grid-cols-2">
                  <TopicCard key={topic.slug} topic={topic} />
        </main>
    </ProtectedRoute>
}
