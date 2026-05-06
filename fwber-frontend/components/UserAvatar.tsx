"use client";

import Image from "next/image";
import { getAvatarUrl } from "@/lib/utils/avatar";

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
 * Falls back to a DiceBear-generated avatar when no photo is uploaded.
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
  const avatarSrc = getAvatarUrl(src, name || userId || "user");

  if (fill) {
    return (
      <Image
        src={avatarSrc}
        alt={name || "User"}
        fill
        className={`object-cover ${className}`}
        priority={priority}
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
      priority={priority}
    />
  );
}
