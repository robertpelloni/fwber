'use client';
import { useEffect, useRef } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Loader2, Zap } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import { apiClient } from '@/lib/api/client';

export default function BurnerJoinPage() {
    const { token } = useParams();
    const router = useRouter();
    const { toast } = useToast();
    const joiningRef = useRef(false);

    useEffect(() => {
        if (!token || joiningRef.current) return;

        const joinBurnerBridge = async () => {
            joiningRef.current = true;
            try {
                const response = await apiClient.post<{ chatroom_id: string }>(`/burner-links/${token}/join`);
                toast({
                    title: 'Burner Bridge Secured',
                    description: 'Connection established. This chat will self-destruct in 24 hours.',
                });

                // Redirect to chatroom
                router.replace(`/chatrooms/${response.data.chatroom_id}`);
            } catch (err: any) {
                toast({
                    variant: 'destructive',
                    title: 'Connection Failed',
                    description: err.response?.data?.message || 'Invalid or expired burner bridge link.',
                });
                router.replace('/home');
            }
        };

        joinBurnerBridge();
    }, [token, router, toast]);

    return (
        <div className="min-h-screen flex items-center justify-center bg-background p-4">
            <Card className="max-w-md w-full border-border animate-pulse">
                <CardHeader className="text-center">
                    <Zap className="w-12 h-12 text-primary mx-auto mb-2 animate-bounce" />
                    <CardTitle className="text-2xl font-bold">Securing Burner Bridge</CardTitle>
                    <CardDescription>
                        Establishing an encrypted, anonymous 24-hour connection...
                    </CardDescription>
                </CardHeader>
                <CardContent className="flex justify-center p-8">
                    <Loader2 className="w-8 h-8 animate-spin text-muted-foreground" />
                </CardContent>
            </Card>
        </div>
    );
}
