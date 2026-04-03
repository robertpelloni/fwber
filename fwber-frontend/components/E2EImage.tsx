'use client';

import { useState } from 'react';
import Image from 'next/image';
import { useDecryptedMedia } from '@/lib/hooks/use-decrypted-media';
import { Loader2, ImageOff } from 'lucide-react';

interface E2EImageProps {
    src: string;
    alt: string;
    isEncrypted: boolean;
    peerId: number;
    className?: string;
    fill?: boolean;
    width?: number;
    height?: number;
}

export function E2EImage({ src, alt, isEncrypted, peerId, className = '', fill = false, width, height }: E2EImageProps) {
    const { decryptedUrl, isLoading, error } = useDecryptedMedia(src, isEncrypted, peerId);
    
    if (isLoading) {
        return (
            <div className={`flex items-center justify-center bg-gray-200 dark:bg-gray-800 ${className} ${fill ? 'absolute inset-0' : ''}`}>
                <Loader2 className="w-6 h-6 animate-spin text-gray-400" />
            </div>
        );
    }

    if (error || !decryptedUrl) {
        return (
            <div className={`flex flex-col items-center justify-center bg-gray-200 dark:bg-gray-800 ${className} ${fill ? 'absolute inset-0' : ''}`}>
                <ImageOff className="w-6 h-6 text-gray-400 mb-2" />
                <span className="text-xs text-gray-500">Failed to decrypt</span>
            </div>
        );
    }

    return (
        <Image
            src={decryptedUrl}
            alt={alt}
            fill={fill}
            width={!fill ? width : undefined}
            height={!fill ? height : undefined}
            className={className}
        />
    );
}
