'use client';

import { useMemo } from 'react';
import Link from 'next/link';
import { MapPin, Users, Wifi, WifiOff } from 'lucide-react';
import { OnlineUsersList, PresenceIndicator, ConnectionStatusBadge } from './PresenceComponents';

interface NearbyUser {
  id: string;
  displayName: string;
  distance?: number;
  lastActive?: string;
}

interface ProximityPresenceViewProps {
  /** List of nearby user IDs and their info */
  nearbyUsers: NearbyUser[];
  /** Current user's location if available */
  currentLocation?: { lat: number; lng: number };
  /** Max display count */
  maxDisplay?: number;
  /** Class name for styling */
  className?: string;
}

/**
 * Shows real-time presence of nearby users
 * Displays who's online in proximity for spontaneous meetups
 */
export function ProximityPresenceView({
  nearbyUsers,
  currentLocation,
  maxDisplay = 8,
  className = '',
}: ProximityPresenceViewProps) {
  const userIds = useMemo(() => nearbyUsers.map(u => u.id), [nearbyUsers]);
  const displayUsers = nearbyUsers.slice(0, maxDisplay);
  const remainingCount = nearbyUsers.length - maxDisplay;

  if (!currentLocation) {
    return (
      <div className={`rounded-xl border border-gray-200 bg-white p-6 shadow-sm ${className}`}>
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <MapPin className="h-5 w-5 text-gray-400" />
            <h3 className="font-semibold text-gray-900">Who&apos;s Nearby</h3>
          </div>
        </div>
        <div className="text-center py-8">
          <WifiOff className="mx-auto h-10 w-10 text-gray-300 mb-3" />
          <p className="text-gray-500 text-sm">Enable location to see who&apos;s nearby</p>
          <button className="mt-3 text-blue-600 text-sm font-medium hover:text-blue-700">
            Enable Location
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className={`rounded-xl border border-gray-200 bg-white p-6 shadow-sm ${className}`}>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-2">
          <div className="relative">
            <MapPin className="h-5 w-5 text-orange-500" />
            <span className="absolute -top-1 -right-1 flex h-2.5 w-2.5">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
            </span>
          </div>
          <h3 className="font-semibold text-gray-900">Who&apos;s Nearby Now</h3>
        </div>
        <ConnectionStatusBadge />
      </div>

      {/* Online Count */}
      <OnlineUsersList userIds={userIds} maxDisplay={0} showCount className="mb-4" />

      {/* User List */}
      {displayUsers.length === 0 ? (
        <div className="text-center py-6">
          <Users className="mx-auto h-10 w-10 text-gray-300 mb-3" />
          <p className="text-gray-500 text-sm">No one online nearby right now</p>
          <p className="text-gray-400 text-xs mt-1">Check back soon!</p>
        </div>
      ) : (
        <div className="space-y-3">
          {displayUsers.map((user) => (
            <Link
              key={user.id}
              href={`/profile/${user.id}`}
              className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div className="flex items-center gap-3">
                <div className="relative">
                  <div className="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-pink-500 flex items-center justify-center text-white font-medium">
                    {user.displayName.charAt(0).toUpperCase()}
                  </div>
                  <div className="absolute -bottom-0.5 -right-0.5">
                    <PresenceIndicator userId={user.id} size="sm" />
                  </div>
                </div>
                <div>
                  <p className="font-medium text-gray-900 text-sm">{user.displayName}</p>
                  {user.distance !== undefined && (
                    <p className="text-xs text-gray-500">
                      {user.distance < 1
                        ? `${Math.round(user.distance * 1000)}m away`
                        : `${user.distance.toFixed(1)} km away`}
                    </p>
                  )}
                </div>
              </div>
              <div className="flex items-center gap-2">
                <PresenceIndicator userId={user.id} showLabel size="sm" />
              </div>
            </Link>
          ))}

          {remainingCount > 0 && (
            <div className="text-center pt-2">
              <Link
                href="/proximity"
                className="text-sm text-blue-600 font-medium hover:text-blue-700"
              >
                View all {nearbyUsers.length} nearby →
              </Link>
            </div>
          )}
        </div>
      )}

      {/* Footer */}
      <div className="mt-4 pt-4 border-t border-gray-100">
        <Link
          href="/proximity"
          className="flex items-center justify-center gap-2 w-full py-2.5 bg-gradient-to-r from-orange-500 to-pink-500 text-white rounded-lg font-medium text-sm hover:from-orange-600 hover:to-pink-600 transition-colors"
        >
          <MapPin className="h-4 w-4" />
          Explore Nearby
        </Link>
      </div>
    </div>
  );
}

/**
 * Compact version for sidebars or cards
 */
export function ProximityPresenceCompact({
  nearbyUsers,
  className = '',
}: {
  nearbyUsers: NearbyUser[];
  className?: string;
}) {
  const onlineCount = nearbyUsers.length;

  return (
    <Link
      href="/proximity"
      className={`flex items-center justify-between p-4 rounded-lg bg-gradient-to-r from-orange-50 to-pink-50 hover:from-orange-100 hover:to-pink-100 transition-colors ${className}`}
    >
      <div className="flex items-center gap-3">
        <div className="relative">
          <div className="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-pink-500 flex items-center justify-center">
            <MapPin className="h-5 w-5 text-white" />
          </div>
          {onlineCount > 0 && (
            <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-500 text-xs font-bold text-white ring-2 ring-white">
              {onlineCount > 9 ? '9+' : onlineCount}
            </span>
          )}
        </div>
        <div>
          <p className="font-semibold text-gray-900">
            {onlineCount > 0 ? `${onlineCount} Online Nearby` : 'Explore Nearby'}
          </p>
          <p className="text-xs text-gray-600">
            {onlineCount > 0 ? 'See who\'s around' : 'Discover people near you'}
          </p>
        </div>
      </div>
      <svg
        className="h-5 w-5 text-gray-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
      </svg>
    </Link>
  );
}
