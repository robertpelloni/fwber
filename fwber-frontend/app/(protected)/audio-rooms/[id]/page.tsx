'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Mic, MicOff, LogOut, Loader2, User as UserIcon } from 'lucide-react';
import { apiClient } from '@/lib/api/client';
import { useToast } from '@/components/ui/use-toast';
import { useWebRTC } from '@/lib/hooks/useWebRTC';
import { useAuth } from '@/lib/auth-context';

interface RoomParticipant {
    id: number;
    user_id: number;
    role: 'speaker' | 'listener';
    is_muted: boolean;
    user: {
        id: number;
        name: string;
    }
}

interface RoomDetails {
    id: number;
    topic: string;
    host_id: number;
    participants: RoomParticipant[];
}

export default function ActiveAudioRoom() {
    const { id } = useParams() as { id: string };
    const router = useRouter();
    const { toast } = useToast();
    const { user } = useAuth();

    const [room, setRoom] = useState<RoomDetails | null>(null);
    const [isMuted, setIsMuted] = useState(false);
    const [loading, setLoading] = useState(true);

    const { startLocalAudio, stopLocalAudio, toggleMute } = useWebRTC({
        roomId: id,
        userId: 0 // Target peer ID would be set dynamically per-peer in a real scalable setup
    });

    useEffect(() => {
        const joinAndInit = async () => {
            try {
                // Join room via API
                const resp = await apiClient.post<{ room: RoomDetails, participants: RoomParticipant[] }>(`/audio-rooms/${id}/join`);
                setRoom(resp.data.room);

                // Initialize local mic
                await startLocalAudio();

            } catch (error: any) {
                toast({ variant: 'destructive', title: 'Cannot join room', description: error.response?.data?.message || 'Room might be ended.' });
                router.push('/audio-rooms');
            } finally {
                setLoading(false);
            }
        };

        joinAndInit();

        return () => {
            // Leave room when unmounting
            stopLocalAudio();
            apiClient.post(`/audio-rooms/${id}/leave`).catch(() => { });
        };
    }, [id]);

    const handleToggleMute = () => {
        toggleMute();
        setIsMuted(!isMuted);
    };

    const handleLeave = async () => {
        await apiClient.post(`/audio-rooms/${id}/leave`);
        router.push('/audio-rooms');
    };

    if (loading || !room) {
        return (
            <div className="min-h-[60vh] flex flex-col items-center justify-center">
                <Loader2 className="w-10 h-10 animate-spin text-primary mb-4" />
                <p className="text-muted-foreground animate-pulse">Connecting to audio relays...</p>
            </div>
        );
    }

    const speakers = room.participants.filter(p => p.role === 'speaker' || p.user_id === room.host_id);
    const listeners = room.participants.filter(p => p.role === 'listener' && p.user_id !== room.host_id);
    const isHost = user?.id === room.host_id;

    return (
        <div className="container max-w-3xl mx-auto py-8 px-4 h-full flex flex-col">
            <Card className="flex-1 flex flex-col border-border bg-card shadow-lg overflow-hidden">
                <CardHeader className="border-b bg-muted/20 pb-4">
                    <div className="flex justify-between items-start">
                        <div>
                            <div className="flex items-center gap-2 mb-1">
                                <span className="bg-red-500 w-2 h-2 rounded-full animate-pulse"></span>
                                <span className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Live Audio Stage</span>
                            </div>
                            <CardTitle className="text-2xl">{room.topic}</CardTitle>
                        </div>
                        <Button variant="ghost" size="sm" onClick={handleLeave} className="text-destructive hover:bg-destructive/10 hover:text-destructive">
                            <LogOut className="w-4 h-4 mr-2" /> Leave Quietly
                        </Button>
                    </div>
                </CardHeader>

                <CardContent className="flex-1 overflow-y-auto p-6 space-y-8">
                    {/* Speakers Grid */}
                    <div>
                        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Speakers</h3>
                        <div className="flex flex-wrap gap-4">
                            {speakers.map(speaker => (
                                <div key={speaker.id} className="flex flex-col items-center gap-2 p-4 bg-muted/30 rounded-2xl w-24 text-center relative border border-secondary">
                                    <div className="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center text-primary border-2 border-primary border-dashed">
                                        <UserIcon className="w-8 h-8" />
                                    </div>
                                    <span className="text-sm font-medium truncate w-full">{speaker.user.name}</span>
                                    {speaker.user_id === room.host_id && (
                                        <span className="absolute -top-2 -right-2 bg-primary text-primary-foreground text-[10px] px-2 py-0.5 rounded-full font-bold">HOST</span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Listeners Grid */}
                    {listeners.length > 0 && (
                        <div>
                            <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Listening In</h3>
                            <div className="flex flex-wrap gap-3">
                                {listeners.map(listener => (
                                    <div key={listener.id} className="flex items-center gap-2 bg-muted/40 px-3 py-1.5 rounded-full">
                                        <div className="w-6 h-6 rounded-full bg-secondary flex items-center justify-center text-secondary-foreground">
                                            <UserIcon className="w-3 h-3" />
                                        </div>
                                        <span className="text-xs font-medium">{listener.user.name}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </CardContent>

                {/* Bottom Controls */}
                <div className="p-4 border-t bg-muted/20 flex justify-center gap-4">
                    <Button
                        size="lg"
                        variant={isMuted ? "outline" : "default"}
                        className={`rounded-full w-16 h-16 p-0 ${isMuted ? 'border-destructive text-destructive' : ''}`}
                        onClick={handleToggleMute}
                    >
                        {isMuted ? <MicOff className="w-6 h-6" /> : <Mic className="w-6 h-6" />}
                    </Button>
                </div>
            </Card>
        </div>
    );
}
