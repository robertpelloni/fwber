'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Globe, MessageSquare, Heart, Share2, User } from 'lucide-react';
import Link from 'next/link';

interface FederatedPost {
    id: number;
    guid: string;
    actor_uri: string;
    actor_username: string;
    actor_domain: string;
    actor_avatar: string;
    content: string;
    url: string;
    published_at: string;
}

export default function FederatedFeedPage() {
    const { token } = useAuth();
    const [posts, setPosts] = useState<FederatedPost[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        if (token) {
            fetchPosts();
        }
    }, [token]);

    const fetchPosts = async () => {
        try {
            setIsLoading(true);
            const response = await api.get('/federation/posts', {
                headers: { Authorization: `Bearer ${token}` }
            }) as any;
            setPosts(response.data.posts || []);
        } catch (error) {
            console.error('Failed to fetch federated posts:', error);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="max-w-2xl mx-auto space-y-8 pb-24 px-4">
            <div className="flex items-center gap-4">
                <Link href="/settings/federation">
                    <Button variant="ghost" size="icon" className="rounded-full">
                        <ArrowLeft className="w-5 h-5" />
                    </Button>
                </Link>
                <div>
                    <h1 className="text-2xl font-black italic uppercase tracking-tighter text-zinc-900 dark:text-white">
                        Global Feed
                    </h1>
                    <p className="text-xs font-bold text-blue-600 uppercase tracking-widest flex items-center gap-1">
                        <Globe className="w-3 h-3" /> Live from the Fediverse
                    </p>
                </div>
            </div>

            {isLoading ? (
                <div className="flex justify-center py-12">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            ) : posts.length === 0 ? (
                <div className="text-center py-20 bg-zinc-50 dark:bg-zinc-900 rounded-3xl border-2 border-dashed border-zinc-200 dark:border-zinc-800">
                    <div className="bg-white dark:bg-zinc-800 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                        <MessageSquare className="w-8 h-8 text-zinc-300" />
                    </div>
                    <h3 className="text-lg font-bold text-zinc-900 dark:text-white">Your Feed is Quiet</h3>
                    <p className="text-zinc-500 max-w-xs mx-auto mt-2">Follow people on Mastodon or other fwber nodes to see their posts here!</p>
                    <Link href="/settings/federation">
                        <Button className="mt-6 bg-blue-600 hover:bg-blue-700 text-white rounded-full px-8">
                            Find People
                        </Button>
                    </Link>
                </div>
            ) : (
                <div className="space-y-4">
                    {posts.map((post) => (
                        <Card key={post.id} className="overflow-hidden border-zinc-200 dark:border-zinc-800">
                            <CardHeader className="p-4 pb-2">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden border border-zinc-200 dark:border-zinc-700">
                                            {post.actor_avatar ? (
                                                <img src={post.actor_avatar} alt="" className="w-full h-full object-cover" />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-zinc-400">
                                                    <User className="w-6 h-6" />
                                                </div>
                                            )}
                                        </div>
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-bold text-sm">@{post.actor_username}</span>
                                                <Badge variant="outline" className="text-[10px] py-0 px-1.5 h-4 border-zinc-200 dark:border-zinc-700">{post.actor_domain}</Badge>
                                            </div>
                                            <p className="text-[10px] text-zinc-400 uppercase tracking-widest font-bold">
                                                {new Date(post.published_at).toLocaleDateString()} • {new Date(post.published_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                            </p>
                                        </div>
                                    </div>
                                    <a href={post.url} target="_blank" rel="noopener noreferrer">
                                        <Button variant="ghost" size="icon" className="text-zinc-400">
                                            <Share2 className="w-4 h-4" />
                                        </Button>
                                    </a>
                                </div>
                            </CardHeader>
                            <CardContent className="p-4 pt-0">
                                <div 
                                    className="text-sm prose dark:prose-invert max-w-none break-words"
                                    dangerouslySetInnerHTML={{ __html: post.content }}
                                />
                                <div className="flex gap-4 mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                    <Button variant="ghost" size="sm" className="text-zinc-500 hover:text-pink-500 gap-2">
                                        <Heart className="w-4 h-4" />
                                        <span className="text-xs">Like</span>
                                    </Button>
                                    <Button variant="ghost" size="sm" className="text-zinc-500 hover:text-blue-500 gap-2">
                                        <MessageSquare className="w-4 h-4" />
                                        <span className="text-xs">Reply</span>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    );
}
