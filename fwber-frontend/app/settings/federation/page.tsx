'use client';

import { useState } from 'react';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
import { Globe, Search, UserPlus, Shield, Info, ArrowLeft, Share2 } from 'lucide-react';
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
    const [following, setFollowing] = useState<string[]>([]);

    const handleFollow = async (actorId: string) => {
        if (!token) return;
        try {
            await api.post('/federation/follow', { actor_id: actorId }, {
                headers: { Authorization: `Bearer ${token}` }
            });
            setFollowing([...following, actorId]);
            toast({
                title: "Follow Request Sent",
                description: "You are now following this federated actor.",
            });
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
            // In a real implementation, this hits our local WebFinger proxy
            const response = await api.get(`/federation/search?q=${encodeURIComponent(handle)}`, {
                headers: { Authorization: `Bearer ${token}` }
            }) as any;
            
            setResults(response.data.actors);
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
        <div className="max-w-4xl mx-auto space-y-8 pb-24">
            {/* Header */}
            <div className="flex items-center justify-between">
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
                <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">BETA</Badge>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                {/* Search Column */}
                <div className="md:col-span-2 space-y-6">
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
                                <Button type="submit" disabled={isSearching} className="bg-blue-600 hover:bg-blue-700">
                                    {isSearching ? '...' : 'Search'}
                                </Button>
                            </form>

                            {results.length > 0 && (
                                <div className="mt-8 space-y-4">
                                    {results.map((actor) => (
                                        <div key={actor.id} className="p-4 border rounded-xl flex items-center justify-between bg-white dark:bg-zinc-900 shadow-sm">
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
                                                variant={following.includes(actor.id) ? "secondary" : "outline"}
                                                className={following.includes(actor.id) ? "" : "border-blue-200 text-blue-600 hover:bg-blue-50"}
                                                onClick={() => handleFollow(actor.id)}
                                                disabled={following.includes(actor.id)}
                                            >
                                                <UserPlus className="w-4 h-4 mr-2" /> 
                                                {following.includes(actor.id) ? 'Following' : 'Follow'}
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

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
