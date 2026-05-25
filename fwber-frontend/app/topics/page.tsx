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
                </div>
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
                                </div>
                                <div className="text-xs text-gray-500 font-medium">
                                    {topic.activeParticipants || 0} local people discussing this
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    );
}
