'use client';

import { useState, useEffect, useCallback } from 'react';
import { useToast } from '@/components/ui/use-toast';

export function useNfc() {
    const { toast } = useToast();
    const [isNative, setIsNative] = useState(false);
    const [lastScan, setLastScan] = useState<any>(null);

    useEffect(() => {
        // Detect if we are running in the ReactNative WebView
        // @ts-ignore
        if (window.ReactNativeWebView) {
            setIsNative(true);
        }

        // Setup the global callback for the native bridge
        (window as any).handleNativeNFC = (data: string) => {
            // NFC: Received from Native Bridge
            try {
                const parsed = JSON.parse(data);
                setLastScan(parsed);
                toast({ title: "Tag Detected", description: "NFC Data received via native bridge." });
            } catch (e) {
                setLastScan(data);
            }
        };

        return () => {
            delete (window as any).handleNativeNFC;
        };
    }, [toast]);

    const startScan = useCallback(() => {
        if (isNative) {
            // @ts-ignore
            window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'START_NFC_SCAN' }));
        } else if ('NDEFReader' in window) {
            // Web NFC logic would go here or stay in component
        }
    }, [isNative]);

    const writeTag = useCallback((payload: string) => {
        if (isNative) {
            // @ts-ignore
            window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'NFC_WRITE', payload }));
        }
    }, [isNative]);

    return { isNative, lastScan, startScan, writeTag, setLastScan };
}
