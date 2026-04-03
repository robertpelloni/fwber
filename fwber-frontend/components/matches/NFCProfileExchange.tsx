'use client';

import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Smartphone, Zap, Loader2, CheckCircle2, AlertTriangle } from 'lucide-react';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { useToast } from '@/components/ui/use-toast';
import { motion, AnimatePresence } from 'framer-motion';

export function NFCProfileExchange() {
    const { user, token } = useAuth();
    const { toast } = useToast();
    const [isScanning, setIsScanning] = useState(false);
    const [status, setStatus] = useState<'idle' | 'scanning' | 'writing' | 'success' | 'error'>('idle');
    const [error, setError] = useState<string | null>(null);

    const startNFCExchange = async () => {
        if (!('NDEFReader' in window)) {
            setStatus('error');
            setError('NFC is not supported on this browser or device.');
            return;
        }

        try {
            setStatus('scanning');
            setIsScanning(true);
            
            // @ts-ignore
            const ndef = new NDEFReader();
            await ndef.scan();
            
            toast({
                title: "NFC Ready",
                description: "Hold your phone near another fwber user's device.",
            });

            ndef.addEventListener("reading", async ({ message, serialNumber }: any) => {
                console.log(`NFC: Read from ${serialNumber}`);
                const record = message.records[0];
                const textDecoder = new TextDecoder();
                const peerData = JSON.parse(textDecoder.decode(record.data));
                
                if (peerData.type === 'fwber_profile' && peerData.userId) {
                    handleMatch(peerData.userId);
                }
            });

            // Also write our own data to be ready for the other user
            const myData = JSON.stringify({
                type: 'fwber_profile',
                userId: user?.id,
                name: user?.name,
                timestamp: Date.now()
            });

            await ndef.write(myData);
            setStatus('writing');

        } catch (err: any) {
            console.error('NFC Error:', err);
            setStatus('error');
            setError(err.message || 'Failed to initialize NFC');
        }
    };

    const handleMatch = async (peerId: string | number) => {
        try {
            await api.post(`/matches/nfc-exchange`, { peer_id: peerId }, {
                headers: { Authorization: `Bearer ${token}` }
            });
            setStatus('success');
            toast({
                title: "Physical Match Verified!",
                description: "You've successfully exchanged profiles via NFC.",
            });
            setTimeout(() => setStatus('idle'), 5000);
        } catch (err) {
            setStatus('error');
            setError('Failed to record NFC exchange');
        } finally {
            setIsScanning(false);
        }
    };

    return (
        <Card className="border-amber-500/30 bg-gradient-to-br from-zinc-900 to-amber-950/20">
            <CardHeader className="pb-2">
                <CardTitle className="text-amber-500 flex items-center gap-2 italic uppercase tracking-tighter">
                    <Smartphone className="w-5 h-5" />
                    NFC Flash Match
                </CardTitle>
                <CardDescription className="text-zinc-400 text-xs">
                    Tap phones to instantly verify a physical meetup and exchange profiles.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <AnimatePresence mode="wait">
                    {status === 'idle' && (
                        <motion.div key="idle" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}>
                            <Button 
                                onClick={startNFCExchange}
                                className="w-full bg-amber-600 hover:bg-amber-700 text-white font-black uppercase italic"
                            >
                                <Zap className="w-4 h-4 mr-2 fill-current" />
                                Start Exchange
                            </Button>
                        </motion.div>
                    )}

                    {(status === 'scanning' || status === 'writing') && (
                        <motion.div key="active" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="flex flex-col items-center py-4 space-y-3">
                            <div className="relative">
                                <div className="absolute inset-0 bg-amber-500/20 rounded-full animate-ping" />
                                <div className="p-4 bg-amber-500/10 rounded-full border border-amber-500/50">
                                    <Smartphone className="w-8 h-8 text-amber-500" />
                                </div>
                            </div>
                            <p className="text-sm font-bold text-amber-200 animate-pulse uppercase tracking-widest">
                                {status === 'scanning' ? 'Ready to Scan...' : 'Broadcasting Profile...'}
                            </p>
                            <Button variant="ghost" size="sm" onClick={() => setStatus('idle')} className="text-zinc-500 text-[10px]">Cancel</Button>
                        </motion.div>
                    )}

                    {status === 'success' && (
                        <motion.div key="success" initial={{ scale: 0.9, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} className="flex flex-col items-center py-4 text-green-500">
                            <CheckCircle2 className="w-12 h-12 mb-2" />
                            <p className="font-black uppercase italic">Match Confirmed</p>
                        </motion.div>
                    )}

                    {status === 'error' && (
                        <motion.div key="error" initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="flex flex-col items-center py-4 space-y-2">
                            <AlertTriangle className="w-8 h-8 text-red-500" />
                            <p className="text-xs text-red-400 text-center">{error}</p>
                            <Button variant="outline" size="sm" onClick={() => setStatus('idle')}>Try Again</Button>
                        </motion.div>
                    )}
                </AnimatePresence>
            </CardContent>
        </Card>
    );
}
