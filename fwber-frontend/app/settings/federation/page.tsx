'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
import { Globe, Search, UserPlus, Shield, Info, ArrowLeft, Share2, MessageSquare } from 'lucide-react';
import Link from 'next/link';

interface RemoteActor {
    id: string;
    type: string;
    preferredUsername: string;
    name?: string;
    summary?: string;
    icon?: { url: string };
    server: string;
}

export default function FederationSettingsPage() {
    const { token, user } = useAuth();
    const { toast } = useToast();
    const [handle, setHandle] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [results, setResults] = useState<RemoteActor[]>([]);
    const [following, setFollowing] = useState<any[]>([]);
    const [followers, setFollowers] = useState<any[]>([]);
    const [activeTab, setActiveTab] = useState<'search' | 'following' | 'followers'>('search');

    useEffect(() => {
        fetchConnections();
    }, [token]);

    const fetchConnections = async () => {
        if (!token) return;
        try {
            const followingsRes = await api.get('/federation/following', {
                headers: { Authorization: `Bearer ${token}` }
            }) as any;
            setFollowing(followingsRes.data.following || []);

            const followersRes = await api.get('/federation/followers', {
                headers: { Authorization: `Bearer ${token}` }
            }) as any;
            setFollowers(followersRes.data.followers || []);
        } catch (error) {
            console.error('Failed to fetch connections:', error);
        }
    };

    const handleFollow = async (actorId: string) => {
        if (!token) return;
        try {
            await api.post('/federation/follow', { actor_id: actorId }, {
                headers: { Authorization: `Bearer ${token}` }
            });
            toast({
                title: "Follow Request Sent",
                description: "You are now following this federated actor.",
            });
            fetchConnections();
        } catch (error) {
            toast({
                variant: "destructive",
                title: "Follow Failed",
                description: "Could not send follow request to the remote server.",
            });
        }
    };

    const handleSearch = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!handle.includes('@')) {
            toast({
                variant: "destructive",
                title: "Invalid Handle",
                description: "Handles must be in the format @user@domain.com",
            });
            return;
        }

        try {
            setIsSearching(true);
            const response = await api.get(`/federation/search?q=${encodeURIComponent(handle)}`, {
                headers: { Authorization: `Bearer ${token}` }
            }) as any;
            
            setResults(response.data.actors);
            setActiveTab('search');
        } catch (error) {
            console.error('Federated search failed:', error);
            toast({
                variant: "destructive",
                title: "Search Failed",
                description: "Could not locate that actor on the Fediverse.",
            });
        } finally {
            setIsSearching(false);
        }
    };

    return (
        <div className="max-w-4xl mx-auto space-y-8 pb-24 px-4">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                    <Link href="/settings">
                        <Button variant="ghost" size="icon" className="rounded-full">
                            <ArrowLeft className="w-5 h-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-black italic uppercase tracking-tighter text-zinc-900 dark:text-white">
                            Global Federation
                        </h1>
                        <p className="text-xs font-bold text-blue-600 uppercase tracking-widest flex items-center gap-1">
                            <Globe className="w-3 h-3" /> ActivityPub / WebFinger Gateway
                        </p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Link href="/settings/federation/feed">
                        <Button variant="outline" size="sm" className="border-blue-200 text-blue-600 hover:bg-blue-50">
                            <MessageSquare className="w-4 h-4 mr-2" /> View Global Feed
                        </Button>
                    </Link>
                    <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200 self-start sm:self-auto">BETA</Badge>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Main Column */}
                <div className="lg:col-span-2 space-y-6">
                    <div className="flex border-b border-zinc-200 dark:border-zinc-800">
                        <button 
                            onClick={() => setActiveTab('search')}
                            className={`pb-2 px-4 text-sm font-bold uppercase tracking-widest transition-colors ${activeTab === 'search' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-zinc-400 hover:text-zinc-600'}`}
                        >
                            Search
                        </button>
                        <button 
                            onClick={() => setActiveTab('following')}
                            className={`pb-2 px-4 text-sm font-bold uppercase tracking-widest transition-colors ${activeTab === 'following' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-zinc-400 hover:text-zinc-600'}`}
                        >
                            Following ({following.length})
                        </button>
                        <button 
                            onClick={() => setActiveTab('followers')}
                            className={`pb-2 px-4 text-sm font-bold uppercase tracking-widest transition-colors ${activeTab === 'followers' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-zinc-400 hover:text-zinc-600'}`}
                        >
                            Followers ({followers.length})
                        </button>
                    </div>

                    {activeTab === 'search' && (
                        <Card className="border-blue-100 dark:border-blue-900/30">
                            <CardHeader>
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <Search className="w-4 h-4" /> Discover External Actors
                                </CardTitle>
                                <CardDescription>Search for users on Mastodon, Threads, or other fwber nodes.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSearch} className="flex gap-2">
                                    <Input 
                                        placeholder="@username@mastodon.social"
                                        value={handle}
                                        onChange={(e) => setHandle(e.target.value)}
                                        className="bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800"
                                    />
                                    <Button type="submit" disabled={isSearching} className="bg-blue-600 hover:bg-blue-700 text-white">
                                        {isSearching ? '...' : 'Search'}
                                    </Button>
                                </form>

                                {results.length > 0 && (
                                    <div className="mt-8 space-y-4">
                                        {results.map((actor) => {
                                            const isFollowing = following.some(f => f.actor_uri === actor.id);
                                            return (
                                                <div key={actor.id} className="p-4 border rounded-xl flex items-center justify-between bg-white dark:bg-zinc-900 shadow-sm transition-all hover:shadow-md">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden border border-zinc-200 dark:border-zinc-700">
                                                            {actor.icon && <img src={actor.icon.url} alt="" className="w-full h-full object-cover" />}
                                                        </div>
                                                        <div>
                                                            <p className="font-bold text-sm">@{actor.preferredUsername}@{actor.server}</p>
                                                            <p className="text-xs text-zinc-500">{actor.name || 'External Actor'}</p>
                                                        </div>
                                                    </div>
                                                    <Button 
                                                        size="sm" 
                                                        variant={isFollowing ? "secondary" : "outline"}
                                                        className={isFollowing ? "" : "border-blue-200 text-blue-600 hover:bg-blue-50"}
                                                        onClick={() => handleFollow(actor.id)}
                                                        disabled={isFollowing}
                                                    >
                                                        <UserPlus className="w-4 h-4 mr-2" /> 
                                                        {isFollowing ? 'Following' : 'Follow'}
                                                    </Button>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {activeTab === 'following' && (
                        <div className="space-y-4">
                            {following.length === 0 ? (
                                <p className="text-center py-12 text-zinc-500 italic">You aren't following anyone yet.</p>
                            ) : (
                                following.map((f) => (
                                    <div key={f.id} className="p-4 border rounded-xl flex items-center justify-between bg-white dark:bg-zinc-900 shadow-sm">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                                {f.username?.[0].toUpperCase()}
                                            </div>
                                            <div>
                                                <p className="font-bold text-sm">@{f.username}@{f.domain}</p>
                                                <p className="text-[10px] text-zinc-400 uppercase tracking-widest font-bold">Status: {f.status}</p>
                                            </div>
                                        </div>
                                        <Badge variant="secondary">{f.status}</Badge>
                                    </div>
                                ))
                            )}
                        </div>
                    )}

                    {activeTab === 'followers' && (
                        <div className="space-y-4">
                            {followers.length === 0 ? (
                                <p className="text-center py-12 text-zinc-500 italic">No one is following you yet.</p>
                            ) : (
                                followers.map((f) => (
                                    <div key={f.id} className="p-4 border rounded-xl flex items-center justify-between bg-white dark:bg-zinc-900 shadow-sm">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold">
                                                {f.username?.[0].toUpperCase()}
                                            </div>
                                            <div>
                                                <p className="font-bold text-sm">@{f.username}@{f.domain}</p>
                                                <p className="text-[10px] text-zinc-400 uppercase tracking-widest font-bold">Remote Follower</p>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    )}

                    <Card className="bg-zinc-50 dark:bg-zinc-900/50 border-zinc-200 dark:border-zinc-800">
                        <CardHeader>
                            <CardTitle className="text-sm uppercase tracking-widest flex items-center gap-2">
                                <Shield className="w-4 h-4" /> Privacy Isolation
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-xs text-zinc-500 space-y-2 leading-relaxed">
                            <p>Federated interactions are isolated from your local proximity data. External servers will never receive your exact coordinates.</p>
                            <p>Only your public profile and ActivityPub-compatible posts are shared with the global Fediverse.</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Info Column */}
                <div className="space-y-6">
                    <Card className="border-dashed border-2">
                        <CardHeader>
                            <CardTitle className="text-sm uppercase font-black tracking-tighter italic">Your Federated ID</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="p-3 bg-zinc-100 dark:bg-zinc-800 rounded-lg font-mono text-[10px] break-all border border-zinc-200 dark:border-zinc-700">
                                {user?.email ? `@${user.email.split('@')[0]}@api.fwber.me` : 'Loading...'}
                            </div>
                            <Button variant="ghost" size="sm" className="w-full text-[10px] font-bold uppercase tracking-widest text-zinc-400">
                                <Share2 className="w-3 h-3 mr-2" /> Copy Federated Handle
                            </Button>
                        </CardContent>
                    </Card>

                    <div className="p-4 rounded-xl bg-blue-500/5 border border-blue-500/10 space-y-3">
                        <h4 className="text-[10px] font-black uppercase tracking-widest text-blue-600 flex items-center gap-2">
                            <Info className="w-3 h-3" /> Federation FAQ
                        </h4>
                        <ul className="text-[10px] text-zinc-500 space-y-2 font-medium">
                            <li>â€¢ Can I match with users on Mastodon? <br/><span className="text-zinc-400 font-normal">Matching is limited to local fwber nodes for safety.</span></li>
                            <li>â€¢ Who can see my face reveals? <br/><span className="text-zinc-400 font-normal">Reveals are only visible within the fwber E2E encrypted layer.</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    );
}
