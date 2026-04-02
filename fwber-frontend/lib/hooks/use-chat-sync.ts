'use client';

import { useEffect, useCallback } from 'react';
import { getPendingMessages, removePendingMessage, incrementRetryCount, getLastSyncAt, setLastSyncAt } from '@/lib/offline-store';
import { api } from '@/lib/api/client';
import { useToast } from '@/components/ui/use-toast';
import { useWebSocket } from '@/lib/hooks/use-websocket';

export function useChatSync(token: string | null) {
    const { toast } = useToast();
    const { injectMissedMessages } = useWebSocket();

    const syncMessages = useCallback(async () => {
        if (!token || !navigator.onLine) return;

        const pending = await getPendingMessages();
        const lastSync = getLastSyncAt();
        
        if (pending.length === 0 && !lastSync) {
            // Nothing to send, and no base sync timestamp yet. Just set it.
            setLastSyncAt(new Date().toISOString());
            return;
        }

        console.debug(`CRDT Sync: Syncing ${pending.length} offline messages and fetching missed ones since ${lastSync}...`);

        try {
            // Send batch to the new CRDT batch endpoint
            const res = await api.post('/messages/sync-batch', {
                last_sync_at: lastSync,
                messages: pending.map(msg => ({
                    uuid: msg.uuid,
                    recipient_id: msg.recipient_id,
                    content: msg.content,
                    type: msg.type,
                    is_encrypted: msg.is_encrypted,
                    created_at: msg.created_at
                }))
            }, {
                headers: { Authorization: `Bearer ${token}` }
            }) as any;

            // Remove successfully synced messages
            const syncedUuids = res.synced_uuids || [];
            for (const msg of pending) {
                if (syncedUuids.includes(msg.uuid)) {
                    await removePendingMessage(msg.id!);
                } else {
                    await incrementRetryCount(msg.id!);
                    if (msg.retry_count > 4) {
                        await removePendingMessage(msg.id!);
                    }
                }
            }

            // Inject missed messages directly into the UI state
            if (res.missed_messages && res.missed_messages.length > 0) {
                console.debug(`CRDT Sync: Received ${res.missed_messages.length} missed messages from server.`);
                injectMissedMessages(res.missed_messages);
            }

            // Update our logical sync clock
            if (res.server_time) {
                setLastSyncAt(res.server_time);
            }

            if (pending.length > 0) {
                toast({ title: 'Offline Sync Complete', description: `Synced ${syncedUuids.length} messages.` });
            }

        } catch (error) {
            console.error('Batch sync failed', error);
        }
    }, [token, toast, injectMissedMessages]);

    useEffect(() => {
        syncMessages();

        const handleOnline = () => {
            toast({ title: 'Back Online', description: 'Reconciling offline messages...' });
            syncMessages();
        };

        window.addEventListener('online', handleOnline);
        const interval = setInterval(syncMessages, 45000);

        return () => {
            window.removeEventListener('online', handleOnline);
            clearInterval(interval);
        };
    }, [syncMessages, toast]);

    return { syncMessages };
}
