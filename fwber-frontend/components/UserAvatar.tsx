"use client";

import Image from "next/image";
import { useState } from "react";
import { getAvatarUrl, getInitials } from "@/lib/utils/avatar";

interface UserAvatarProps {
  src?: string | null;
  name?: string | null;
  userId?: string | number | null;
  size?: number;
  className?: string;
  fill?: boolean;
  priority?: boolean;
}

/**
 * Consistent avatar display across the app.
 * Falls back to DiceBear on load error, then to initials.
 */
export default function UserAvatar({
  src,
  name,
  userId,
  size = 40,
  className = "",
  fill = false,
  priority = false,
}: UserAvatarProps) {
  const [imgError, setImgError] = useState(false);
  const seed = name || userId || "user";

  // If src is provided and not errored, try loading it
  if (src && !imgError) {
    const avatarSrc = getAvatarUrl(src, seed);

    if (fill) {
      return (
        <Image
          src={avatarSrc}
          alt={name || "User"}
          fill
          className={`object-cover ${className}`}
          priority={priority}
          onError={() => setImgError(true)}
        />
      );
    }

    return (
      <Image
        src={avatarSrc}
        alt={name || "User"}
        width={size}
        height={size}
        className={`rounded-full object-cover ${className}`}
        style={{ width: size, height: size }}
        priority={priority}
        onError={() => setImgError(true)}
      />
    );
  }

  // Fallback: DiceBear generated avatar
  const dicebearUrl = `https://api.dicebear.com/7.x/avataaars/svg?seed=${encodeURIComponent(String(seed))}&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf`;

  if (fill) {
    return (
      <Image
        src={dicebearUrl}
        alt={name || "User"}
        fill
        className={`object-cover ${className}`}
        priority={priority}
      />
    );
  }

  return (
    <Image
      src={dicebearUrl}
      alt={name || "User"}
      width={size}
      height={size}
      className={`rounded-full object-cover ${className}`}
      style={{ width: size, height: size }}
      priority={priority}
    />
  );
}
