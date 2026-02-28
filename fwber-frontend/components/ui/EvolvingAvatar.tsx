import React from 'react';
import { cn } from '@/lib/utils';
import Image from 'next/image';

export type EmotionState = 'neutral' | 'happy' | 'flirty' | 'mysterious' | 'intense';

interface EvolvingAvatarProps {
    src: string;
    alt: string;
    size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    emotion?: EmotionState;
    className?: string;
    disableAnimation?: boolean;
}

const sizeConfig = {
    sm: 'w-8 h-8',
    md: 'w-12 h-12',
    lg: 'w-16 h-16',
    xl: 'w-24 h-24',
    '2xl': 'w-32 h-32',
};

const emotionStyles: Record<EmotionState, { wrapper: string, overlay: string }> = {
    neutral: {
        wrapper: 'border-transparent',
        overlay: 'opacity-0'
    },
    happy: {
        wrapper: 'border-yellow-400 ring-2 ring-yellow-400/50',
        overlay: 'bg-yellow-400/10 opacity-100 mix-blend-overlay'
    },
    flirty: {
        wrapper: 'border-pink-500 animate-pulse-slow shadow-[0_0_15px_rgba(236,72,153,0.6)]',
        overlay: 'bg-gradient-to-t from-pink-500/20 to-transparent opacity-100 mix-blend-overlay'
    },
    mysterious: {
        wrapper: 'border-indigo-500 grayscale-[30%] shadow-[0_0_20px_rgba(99,102,241,0.4)]',
        overlay: 'bg-indigo-900/30 opacity-100 mix-blend-multiply backdrop-blur-[1px]'
    },
    intense: {
        wrapper: 'border-red-600 animate-pulse ring-4 ring-red-600/30',
        overlay: 'bg-red-600/20 opacity-100 mix-blend-color-burn'
    }
};

export const EvolvingAvatar: React.FC<EvolvingAvatarProps> = ({
    src,
    alt,
    size = 'md',
    emotion = 'neutral',
    className,
    disableAnimation = false
}) => {
    const currentStyle = emotionStyles[emotion] || emotionStyles.neutral;
    const dimensions = sizeConfig[size];

    return (
        <div className={cn("relative rounded-full inline-block transition-all duration-700 ease-in-out border-2", currentStyle.wrapper, className)}>
            <div className={cn("overflow-hidden rounded-full", dimensions)}>
                <Image
                    src={src}
                    alt={alt}
                    fill
                    className="object-cover"
                />
                {/* The Emotion Overlay Wrapper */}
                <div className={cn(
                    "absolute inset-0 rounded-full transition-opacity duration-1000",
                    currentStyle.overlay,
                    disableAnimation && "animate-none transition-none"
                )} />
            </div>
        </div>
    );
};
