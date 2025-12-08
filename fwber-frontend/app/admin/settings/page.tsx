'use client';

import { useState } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import { useBackendFeatureFlags, useUpdateFeatureFlags, useSystemHealth } from '@/lib/hooks/use-config';
import type { FeatureFlags } from '@/lib/api/config';
import {
  Settings,
  Shield,
  Activity,
  Database,
  Wifi,
  WifiOff,
  Check,
  X,
  RefreshCw,
  AlertTriangle,
} from 'lucide-react';

const FEATURE_DESCRIPTIONS: Record<keyof FeatureFlags, { label: string; description: string; category: string }> = {
  groups: {
    label: 'Groups',
    description: 'Core group functionality and group messaging',
    category: 'Core',
  },
  photos: {
    label: 'Photos',
    description: 'Photo upload and management',
    category: 'Core',
  },
  proximity_artifacts: {
    label: 'Proximity Artifacts',
    description: 'Ephemeral location-based content feed (Local Pulse)',
    category: 'Core',
  },
  chatrooms: {
    label: 'Chatrooms',
    description: 'Real-time location-based chatrooms',
    category: 'Social',
  },
  proximity_chatrooms: {
    label: 'Proximity Chatrooms',
    description: 'Proximity-based networking chatrooms with location tracking',
    category: 'Social',
  },
  face_reveal: {
    label: 'Face Reveal',
    description: 'Progressive photo reveal mechanics based on relationship tiers',
    category: 'Privacy',
  },
  local_media_vault: {
    label: 'Local Media Vault',
    description: 'Zero-knowledge encrypted media storage (Beta)',
    category: 'Privacy',
  },
  moderation: {
    label: 'Moderation',
    description: 'Advanced moderation tools including shadow throttling and geo-spoof detection',
    category: 'Safety',
  },
  recommendations: {
    label: 'Recommendations',
    description: 'AI-powered personalized recommendations',
    category: 'AI',
  },
  websocket: {
    label: 'WebSocket',
    description: 'Real-time bidirectional communication helpers',
    category: 'Infrastructure',
  },
  content_generation: {
    label: 'Content Generation',
    description: 'AI-powered profile bio and conversation starter generation',
    category: 'AI',
  },
  rate_limits: {
    label: 'Rate Limiting',
    description: 'Advanced rate limiting admin tools',
    category: 'Infrastructure',
  },
  analytics: {
    label: 'Analytics',
    description: 'Admin analytics dashboards and telemetry',
    category: 'Infrastructure',
  },
};

const CATEGORIES = ['Core', 'Social', 'Privacy', 'Safety', 'AI', 'Infrastructure'];

function HealthStatusBadge({ status }: { status: string }) {
  const getStatusColor = (s: string) => {
    switch (s) {
      case 'healthy':
      case 'up':
      case 'configured':
        return 'bg-green-100 text-green-800';
      case 'degraded':
        return 'bg-yellow-100 text-yellow-800';
      case 'unhealthy':
      case 'down':
      case 'not_configured':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(status)}`}>
      {status === 'healthy' || status === 'up' || status === 'configured' ? (
        <Check className="w-3 h-3 mr-1" />
      ) : status === 'unhealthy' || status === 'down' || status === 'not_configured' ? (
        <X className="w-3 h-3 mr-1" />
      ) : (
        <AlertTriangle className="w-3 h-3 mr-1" />
      )}
      {status.replace('_', ' ')}
    </span>
  );
}

function FeatureToggle({
  flag,
  enabled,
  isPending,
  onToggle,
}: {
  flag: keyof FeatureFlags;
  enabled: boolean;
  isPending: boolean;
  onToggle: (flag: keyof FeatureFlags, value: boolean) => void;
}) {
  const info = FEATURE_DESCRIPTIONS[flag];

  return (
    <div className="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
      <div className="flex-1">
        <div className="flex items-center gap-2">
          <h4 className="font-medium text-gray-900">{info.label}</h4>
          <span className="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded">{info.category}</span>
        </div>
        <p className="text-sm text-gray-500 mt-1">{info.description}</p>
      </div>
      <button
        onClick={() => onToggle(flag, !enabled)}
        disabled={isPending}
        className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed ${
          enabled ? 'bg-blue-600' : 'bg-gray-200'
        }`}
        role="switch"
        aria-checked={enabled}
        aria-label={`Toggle ${info.label}`}
      >
        <span
          className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
            enabled ? 'translate-x-5' : 'translate-x-0'
          }`}
        />
      </button>
    </div>
  );
}

export default function AdminSettingsPage() {
  const [pendingChanges, setPendingChanges] = useState<Partial<FeatureFlags>>({});

  const featuresQuery = useBackendFeatureFlags();
  const healthQuery = useSystemHealth();
  const updateMutation = useUpdateFeatureFlags();

  const features = featuresQuery.data?.features;
  const health = healthQuery.data;

  const handleToggle = (flag: keyof FeatureFlags, value: boolean) => {
    setPendingChanges((prev) => ({ ...prev, [flag]: value }));

    updateMutation.mutate(
      { [flag]: value },
      {
        onSuccess: () => {
          setPendingChanges((prev) => {
            const next = { ...prev };
            delete next[flag];
            return next;
          });
        },
        onError: () => {
          setPendingChanges((prev) => {
            const next = { ...prev };
            delete next[flag];
            return next;
          });
        },
      }
    );
  };

  const getEffectiveValue = (flag: keyof FeatureFlags): boolean => {
    if (flag in pendingChanges) {
      return pendingChanges[flag]!;
    }
    return features?.[flag] ?? false;
  };

  if (featuresQuery.error) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen bg-gray-50 p-8">
          <div className="max-w-4xl mx-auto">
            <div className="rounded-lg border border-red-200 bg-red-50 p-6">
              <h1 className="text-xl font-semibold text-red-800">Unable to load settings</h1>
              <p className="mt-2 text-sm text-red-700">
                {(featuresQuery.error as Error)?.message ?? 'An error occurred'}
              </p>
              <button
                onClick={() => featuresQuery.refetch()}
                className="mt-4 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500"
              >
                Retry
              </button>
            </div>
          </div>
        </div>
      </ProtectedRoute>
    );
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <header className="bg-white shadow">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <Settings className="h-8 w-8 text-gray-600" />
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">Admin Settings</h1>
                  <p className="text-sm text-gray-500">Manage feature flags and system configuration</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <button
                  onClick={() => {
                    featuresQuery.refetch();
                    healthQuery.refetch();
                  }}
                  disabled={featuresQuery.isFetching || healthQuery.isFetching}
                  className="flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                >
                  <RefreshCw className={`h-4 w-4 ${featuresQuery.isFetching ? 'animate-spin' : ''}`} />
                  Refresh
                </button>
                <a
                  href="/analytics"
                  className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500"
                >
                  View Analytics
                </a>
              </div>
            </div>
          </div>
        </header>

        <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* System Health Section */}
          <section className="mb-8">
            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <Activity className="h-5 w-5" />
              System Health
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {/* Mercure Status */}
              <div className="bg-white rounded-lg border border-gray-200 p-4">
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    {health?.services.mercure === 'up' ? (
                      <Wifi className="h-5 w-5 text-green-600" />
                    ) : (
                      <WifiOff className="h-5 w-5 text-gray-400" />
                    )}
                    <span className="font-medium text-gray-900">Mercure Hub</span>
                  </div>
                  <HealthStatusBadge status={health?.services.mercure ?? 'unknown'} />
                </div>
                <p className="text-sm text-gray-500 truncate">
                  {health?.details.mercure_url || 'Not configured'}
                </p>
              </div>

              {/* Cache Status */}
              <div className="bg-white rounded-lg border border-gray-200 p-4">
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    <Database className="h-5 w-5 text-blue-600" />
                    <span className="font-medium text-gray-900">Cache</span>
                  </div>
                  <HealthStatusBadge status={health?.services.cache ?? 'unknown'} />
                </div>
                <p className="text-sm text-gray-500">Driver: {health?.details.cache_driver ?? 'unknown'}</p>
              </div>

              {/* Queue Status */}
              <div className="bg-white rounded-lg border border-gray-200 p-4">
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    <Shield className="h-5 w-5 text-purple-600" />
                    <span className="font-medium text-gray-900">Queue</span>
                  </div>
                  <HealthStatusBadge status={health?.services.queue ?? 'unknown'} />
                </div>
                <p className="text-sm text-gray-500">Driver: {health?.details.queue_driver ?? 'unknown'}</p>
              </div>
            </div>
          </section>

          {/* Feature Flags Section */}
          <section>
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-gray-900">Feature Flags</h2>
              {featuresQuery.data?.timestamp && (
                <span className="text-xs text-gray-500">
                  Last updated: {new Date(featuresQuery.data.timestamp).toLocaleString()}
                </span>
              )}
            </div>

            {featuresQuery.isLoading ? (
              <div className="space-y-4">
                {Array.from({ length: 6 }).map((_, i) => (
                  <div key={i} className="h-20 bg-gray-200 rounded-lg animate-pulse" />
                ))}
              </div>
            ) : features ? (
              <div className="space-y-6">
                {CATEGORIES.map((category) => {
                  const categoryFlags = (Object.keys(FEATURE_DESCRIPTIONS) as Array<keyof FeatureFlags>).filter(
                    (flag) => FEATURE_DESCRIPTIONS[flag].category === category
                  );

                  if (categoryFlags.length === 0) return null;

                  return (
                    <div key={category}>
                      <h3 className="text-sm font-medium text-gray-700 uppercase tracking-wider mb-3">
                        {category}
                      </h3>
                      <div className="space-y-3">
                        {categoryFlags.map((flag) => (
                          <FeatureToggle
                            key={flag}
                            flag={flag}
                            enabled={getEffectiveValue(flag)}
                            isPending={flag in pendingChanges}
                            onToggle={handleToggle}
                          />
                        ))}
                      </div>
                    </div>
                  );
                })}
              </div>
            ) : null}

            {/* Warning Notice */}
            <div className="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
              <div className="flex items-start gap-3">
                <AlertTriangle className="h-5 w-5 text-yellow-600 mt-0.5" />
                <div>
                  <h4 className="font-medium text-yellow-800">Runtime Changes</h4>
                  <p className="text-sm text-yellow-700 mt-1">
                    Changes made here are applied at runtime and stored in cache. They will reset
                    after cache is cleared. For permanent changes, update the <code className="bg-yellow-100 px-1 rounded">.env</code> file
                    and run <code className="bg-yellow-100 px-1 rounded">php artisan config:cache</code>.
                  </p>
                </div>
              </div>
            </div>
          </section>
        </main>
      </div>
    </ProtectedRoute>
  );
}
