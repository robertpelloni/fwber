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
