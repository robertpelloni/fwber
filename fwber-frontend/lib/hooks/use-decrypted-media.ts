import { useState, useEffect, useRef } from 'react';
import { useAuth } from '@/lib/auth-context';
import { useE2EEncryption } from './use-e2e-encryption';

interface DecryptedMediaCache {
    [url: string]: string; // objectUrl
}

const mediaCache: DecryptedMediaCache = {};
let cryptoWorker: Worker | null = null;

export function useDecryptedMedia(url: string | null, isEncrypted: boolean, peerId: number | null) {
    const [decryptedUrl, setDecryptedUrl] = useState<string | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const { getSharedKeyRaw } = useE2EEncryption();

    useEffect(() => {
        if (!url) return;
        
        // Return clear media immediately
        if (!isEncrypted) {
            setDecryptedUrl(url);
            return;
        }

        // Return from memory cache if already decrypted
        if (mediaCache[url]) {
            setDecryptedUrl(mediaCache[url]);
            return;
        }

        if (!peerId) return;

        let isMounted = true;
        
        const decryptMedia = async () => {
            setIsLoading(true);
            try {
                // Initialize the Web Worker lazily
                if (!cryptoWorker && typeof window !== 'undefined') {
                    cryptoWorker = new Worker('/crypto-worker.js');
                }

                // Fetch encrypted file
                const response = await fetch(url);
                if (!response.ok) throw new Error('Failed to fetch media');
                
                const buffer = await response.arrayBuffer();

                // Get raw shared key for the worker
                const sharedKeyRaw = await getSharedKeyRaw(peerId);

                if (!sharedKeyRaw) throw new Error('E2E Key not established');

                // Read IV from the first 12 bytes of the file, then ciphertext
                const ivBuffer = buffer.slice(0, 12);
                const cipherBuffer = buffer.slice(12);

                const ivBase64 = btoa(String.fromCharCode(...new Uint8Array(ivBuffer)));

                // Wrap worker message in a Promise
                const decryptedBuffer = await new Promise<ArrayBuffer>((resolve, reject) => {
                    const messageId = Math.random().toString(36).substring(7);
                    
                    const handler = (e: MessageEvent) => {
                        if (e.data.id === messageId) {
                            cryptoWorker?.removeEventListener('message', handler);
                            if (e.data.success) {
                                resolve(e.data.decryptedBuffer);
                            } else {
                                reject(new Error(e.data.error));
                            }
                        }
                    };

                    cryptoWorker?.addEventListener('message', handler);
                    
                    cryptoWorker?.postMessage({
                        id: messageId,
                        type: 'DECRYPT_MEDIA',
                        encryptedBuffer: cipherBuffer,
                        keyJwk: sharedKeyRaw,
                        ivBase64
                    }, [cipherBuffer]); // Transfer ciphertext buffer ownership
                });

                // Reconstruct Blob and ObjectURL
                if (isMounted) {
                    // We assume JPEG for now, could read mime from the model
                    const blob = new Blob([decryptedBuffer], { type: 'image/jpeg' });
                    const objectUrl = URL.createObjectURL(blob);
                    
                    mediaCache[url] = objectUrl;
                    setDecryptedUrl(objectUrl);
                }

            } catch (err: any) {
                console.error('Media decryption failed:', err);
                if (isMounted) setError(err.message);
            } finally {
                if (isMounted) setIsLoading(false);
            }
        };

        decryptMedia();

        return () => {
            isMounted = false;
        };
    }, [url, isEncrypted, peerId, getSharedKeyRaw]);

    return { decryptedUrl, isLoading, error };
}
