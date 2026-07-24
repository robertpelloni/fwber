'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';

type AvatarContextValue = {
  imageSrc?: string;
  imageAlt?: string;
  imageFailed: boolean;
  setImageFailed: (failed: boolean) => void;
};

const AvatarContext = React.createContext<AvatarContextValue | null>(null);

const useAvatarContext = () => {
  const context = React.useContext(AvatarContext);
  if (!context) {
    throw new Error('Avatar components must be used inside <Avatar>.');
  }

  return context;
};

export const Avatar = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, children, ...props }, ref) => {
    const [imageSrc, setImageSrc] = React.useState<string | undefined>();
    const [imageAlt, setImageAlt] = React.useState<string | undefined>();
    const [imageFailed, setImageFailed] = React.useState(false);

    const value = React.useMemo(
      () => ({ imageSrc, imageAlt, imageFailed, setImageFailed }),
      [imageAlt, imageFailed, imageSrc],
    );

    return (
      <AvatarContext.Provider value={value}>
        <div
          ref={ref}
          className={cn('relative flex shrink-0 overflow-hidden rounded-full', className)}
          {...props}
        >
          {React.Children.map(children, (child) => {
            if (!React.isValidElement(child)) {
              return child;
            }

            if ((child.type as any)?.displayName === 'AvatarImage') {
              return React.cloneElement(child as React.ReactElement<any>, {
                registerSource: (src?: string, alt?: string) => {
                  setImageSrc(src);
                  setImageAlt(alt);
                },
              });
            }

            return child;
          })}
        </div>
      </AvatarContext.Provider>
    );
  },
);
Avatar.displayName = 'Avatar';

export const AvatarImage = React.forwardRef<
  HTMLImageElement,
  React.ImgHTMLAttributes<HTMLImageElement> & { registerSource?: (src?: string, alt?: string) => void }
>(({ className, src, alt, registerSource, ...props }, ref) => {
  const { imageFailed, setImageFailed } = useAvatarContext();

  React.useEffect(() => {
    registerSource?.(typeof src === 'string' ? src : undefined, alt);
  }, [alt, registerSource, src]);

  if (!src || imageFailed) {
    return null;
  }

  return (
    <img
      ref={ref}
      src={typeof src === 'string' ? src : undefined}
      alt={alt}
      className={cn('aspect-square h-full w-full object-cover', className)}
      onError={() => setImageFailed(true)}
      {...props}
    />
  );
});
AvatarImage.displayName = 'AvatarImage';

export const AvatarFallback = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, children, ...props }, ref) => {
    const { imageSrc, imageFailed } = useAvatarContext();
    const showFallback = !imageSrc || imageFailed;

    if (!showFallback) {
      return null;
    }

    return (
      <div
        ref={ref}
        className={cn(
          'flex h-full w-full items-center justify-center rounded-full bg-muted text-muted-foreground',
          className,
        )}
        {...props}
      >
        {children}
      </div>
    );
  },
);
AvatarFallback.displayName = 'AvatarFallback';

export type EmotionState = 'neutral' | 'happy' | 'flirty' | 'mysterious' | 'intense' | 'thoughtful' | 'cynical' | 'melancholic';

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
    },
    thoughtful: {
        wrapper: 'border-blue-400 shadow-[0_0_15px_rgba(96,165,250,0.6)]',
        overlay: 'bg-blue-400/20 opacity-100 mix-blend-overlay'
    },
    cynical: {
        wrapper: 'border-purple-600 shadow-[0_0_15px_rgba(147,51,234,0.6)]',
        overlay: 'bg-purple-900/40 opacity-100 mix-blend-multiply'
    },
    melancholic: {
        wrapper: 'border-slate-500 grayscale shadow-[0_0_15px_rgba(100,116,139,0.4)]',
        overlay: 'bg-slate-700/30 opacity-100 mix-blend-multiply'
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
            <div className={cn("overflow-hidden rounded-full relative", dimensions)}>
                <img
                    src={src}
                    alt={alt}
                    className="object-cover w-full h-full"
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
