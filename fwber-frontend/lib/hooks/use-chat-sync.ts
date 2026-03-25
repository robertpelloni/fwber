'use client';

import { useEffect, useCallback } from 'react';
import { getPendingMessages, removePendingMessage, incrementRetryCount } from '@/lib/offline-store';
import { api } from '@/lib/api/client';
import { useToast } from '@/components/ui/use-toast';

export function useChatSync(token: string | null) {
    const { toast } = useToast();

    const syncMessages = useCallback(async () => {
        if (!token || !navigator.onLine) return;

        const pending = await getPendingMessages();
        if (pending.length === 0) return;

        console.log(`Syncing ${pending.length} offline messages...`);

        for (const msg of pending) {
            try {
                await api.post('/messages/sync', {
                    uuid: msg.uuid,
                    recipient_id: msg.recipient_id,
                    content: msg.content,
                    message_type: msg.type,
                    is_encrypted: msg.is_encrypted,
                    created_at: msg.created_at
                }, {
                    headers: { Authorization: `Bearer ${token}` }
                });

                await removePendingMessage(msg.id!);
            } catch (error) {
                console.error(`Failed to sync message ${msg.uuid}`, error);
                await incrementRetryCount(msg.id!);
                
                if (msg.retry_count > 5) {
                    // Fail permanently after 5 attempts
                    await removePendingMessage(msg.id!);
                    toast({
                        title: 'Sync Failed',
                        description: 'Some messages could not be sent and were removed from the queue.',
                        variant: 'destructive'
                    });
                }
            }
        }
    }, [token, toast]);

    useEffect(() => {
        // Sync on mount
        syncMessages();

        // Sync when coming back online
        const handleOnline = () => {
            toast({ title: 'Back Online', description: 'Syncing your offline messages...' });
            syncMessages();
        };

        window.addEventListener('online', handleOnline);
        
        // Periodic sync attempt every 30 seconds if online
        const interval = setInterval(syncMessages, 30000);

        return () => {
            window.removeEventListener('online', handleOnline);
            clearInterval(interval);
        };
    }, [syncMessages, toast]);

    return { syncMessages };
}
