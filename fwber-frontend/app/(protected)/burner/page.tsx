'use client';
import { useState, useEffect, useRef } from 'react';
import QRCode from 'react-qr-code';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Copy, RefreshCw, SmartphoneNfc } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import { apiClient } from '@/lib/api/client';

interface BurnerLinkData {
    token: string;
    expires_at: string;
    url: string;
}

export default function BurnerGeneratorPage() {
    const [burnerData, setBurnerData] = useState<BurnerLinkData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const { toast } = useToast();
    const pollingRef = useRef<NodeJS.Timeout | null>(null);

    const generateLink = async () => {
        setLoading(true);
        setError(null);
        try {
            // Create new burner token
            const response = await apiClient.post<BurnerLinkData>('/burner-links');
            setBurnerData(response.data);
            toast({
                title: 'Burner Bridge Active',
                description: 'Temporary access code generated. Scan to connect anonymously.',
            });
            // Poll for chatroom join status
            startPolling(response.data.token);
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to generate burner bridge.');
        } finally {
            setLoading(false);
        }
    };

    const startPolling = (token: string) => {
        if (pollingRef.current) clearInterval(pollingRef.current);

        // In a real app we would rely on Echo WebSockets for `BurnerLinkJoined` event, 
        // but a lightweight poll checking chatroom availability works as a fallback.
        // For now, the user scanning the QR immediately gets redirected to the chatroom.
        // The generator can just tap the "Refresh" or we rely on a global nav badge for new chats.
    };

    useEffect(() => {
        generateLink();

        const currentPollingRef = pollingRef.current;
        return () => {
            if (currentPollingRef) {
                clearInterval(currentPollingRef);
            }
        };
    }, [generateLink]);

    const copyLink = () => {
        if (burnerData) {
            navigator.clipboard.writeText(burnerData.url);
            toast({ title: 'Link copied to clipboard!' });
        }
    };

    return (
        <div className="container max-w-lg mx-auto py-8 px-4">
            <Card className="border-border">
                <CardHeader className="text-center">
                    <SmartphoneNfc className="w-12 h-12 text-primary mx-auto mb-2" />
                    <CardTitle className="text-2xl font-bold">Burner Bridge</CardTitle>
                    <CardDescription>
                        Show this QR code to instantly spin up a temporary 24-hour anonymous chatroom with someone IRL.
                    </CardDescription>
                </CardHeader>
                <CardContent className="flex flex-col items-center space-y-6">
                    {loading ? (
                        <Skeleton className="w-64 h-64 rounded-xl" />
                    ) : error ? (
                        <div className="text-destructive text-center p-4 bg-destructive/10 rounded-lg">
                            {error}
                        </div>
                    ) : burnerData ? (
                        <div className="flex flex-col items-center space-y-6">
                            <div className="p-4 bg-white rounded-xl shadow-inner">
                                <QRCode
                                    value={burnerData.url}
                                    size={256}
                                    level="H"
                                    className="rounded-lg"
                                />
                            </div>
                            <div className="text-sm text-muted-foreground text-center">
                                Valid until {new Date(burnerData.expires_at).toLocaleString()}
                            </div>

                            <div className="flex w-full space-x-2">
                                <Button variant="outline" className="flex-1 gap-2" onClick={copyLink}>
                                    <Copy className="w-4 h-4" /> Copy Link
                                </Button>
                                <Button variant="secondary" className="flex-1 gap-2" onClick={generateLink}>
                                    <RefreshCw className="w-4 h-4" /> Regenerate
                                </Button>
                            </div>
                        </div>
                    ) : null}
                </CardContent>
            </Card>
        </div>
    );
}
