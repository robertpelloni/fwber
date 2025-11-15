"use client";

import { useState } from 'react';
import { useAuth } from '@/lib/auth-context';
import {
  useModerationDashboard,
  useFlaggedContent,
  useSpoofDetections,
  useThrottles,
  useActions,
  useReviewFlagMutation,
  useReviewSpoofMutation,
  useRemoveThrottleMutation,
} from '@/lib/hooks/use-moderation';
import { ShieldAlert, Flag, Gauge, Ban, CheckCircle2, Trash2, Zap } from 'lucide-react';

export default function ModerationDashboard() {
  const { token } = useAuth();
  const [flaggedPage, setFlaggedPage] = useState(1);
  const [spoofsPage, setSpoofsPage] = useState(1);
  const [throttlesPage, setThrottlesPage] = useState(1);
  const [actionsPage, setActionsPage] = useState(1);

  const dashboard = useModerationDashboard(token);
  const flagged = useFlaggedContent(flaggedPage, token);
  const spoofs = useSpoofDetections(spoofsPage, token);
  const throttles = useThrottles(throttlesPage, token);
  const actions = useActions(actionsPage, token);

  const reviewFlag = useReviewFlagMutation();
  const reviewSpoof = useReviewSpoofMutation();
  const removeThrottle = useRemoveThrottleMutation();

  if (!token) {
    return (
      <div className="p-8 text-center">
        <ShieldAlert className="h-10 w-10 text-gray-400 mx-auto mb-3" />
        <h2 className="text-lg font-semibold">Moderator access required</h2>
        <p className="text-gray-600">Log in with a moderator account to view the dashboard.</p>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-3xl font-bold mb-6 flex items-center gap-2">
        <ShieldAlert className="h-8 w-8 text-purple-600" /> Moderation Dashboard
      </h1>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <StatCard label="Flagged Posts" value={dashboard.data?.stats.flagged_artifacts ?? '—'} icon={<Flag className="h-5 w-5" />} />
        <StatCard label="Active Throttles" value={dashboard.data?.stats.active_throttles ?? '—'} icon={<Gauge className="h-5 w-5" />} />
        <StatCard label="High-Risk Spoofs" value={dashboard.data?.stats.pending_spoof_detections ?? '—'} icon={<Zap className="h-5 w-5" />} />
        <StatCard label="Actions Today" value={dashboard.data?.stats.moderation_actions_today ?? '—'} icon={<CheckCircle2 className="h-5 w-5" />} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Flagged Content */}
        <section className="bg-white border rounded-lg p-4">
          <h2 className="text-xl font-semibold mb-3 flex items-center gap-2"><Flag className="h-5 w-5" /> Flagged Content</h2>
          {flagged.data?.data?.length ? (
            <ul className="divide-y">
              {flagged.data.data.map((item: any) => (
                <li key={item.id} className="py-3">
                  <div className="flex justify-between items-center">
                    <div>
                      <div className="font-medium">#{item.id} · {item.type}</div>
                      <div className="text-gray-600 text-sm line-clamp-2">{item.content}</div>
                      <div className="text-xs text-gray-500">Flags: {item.flag_count} · By User #{item.user_id}</div>
                    </div>
                    <div className="flex gap-2">
                      <button aria-label="Approve" className="px-3 py-1 text-sm bg-green-600 text-white rounded" onClick={() => reviewFlag.mutate({ artifactId: item.id, payload: { action: 'approve', reason: 'Allowed' }, token })}>Approve</button>
                      <button aria-label="Remove" className="px-3 py-1 text-sm bg-red-600 text-white rounded" onClick={() => reviewFlag.mutate({ artifactId: item.id, payload: { action: 'remove', reason: 'Policy violation' }, token })}>Remove</button>
                      <button aria-label="Throttle user" className="px-3 py-1 text-sm bg-yellow-600 text-white rounded" onClick={() => reviewFlag.mutate({ artifactId: item.id, payload: { action: 'throttle_user', reason: 'Abuse', throttle_severity: 3, throttle_duration_hours: 24 }, token })}>Throttle</button>
                      <button aria-label="Ban user" className="px-3 py-1 text-sm bg-gray-800 text-white rounded" onClick={() => reviewFlag.mutate({ artifactId: item.id, payload: { action: 'ban_user', reason: 'Extreme abuse' }, token })}>Ban</button>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-gray-600">No flagged items.</p>
          )}
        </section>

        {/* Spoof Detections */}
        <section className="bg-white border rounded-lg p-4">
          <h2 className="text-xl font-semibold mb-3 flex items-center gap-2"><Zap className="h-5 w-5" /> Geo-Spoof Detections</h2>
          {spoofs.data?.data?.length ? (
            <ul className="divide-y">
              {spoofs.data.data.map((d: any) => (
                <li key={d.id} className="py-3">
                  <div className="flex justify-between items-center">
                    <div>
                      <div className="font-medium">User #{d.user_id} · Score {d.suspicion_score}</div>
                      <div className="text-xs text-gray-500">Detected {d.detected_at}</div>
                    </div>
                    <div className="flex gap-2">
                      <button aria-label="Confirm spoof" className="px-3 py-1 text-sm bg-red-600 text-white rounded" onClick={() => reviewSpoof.mutate({ detectionId: d.id, payload: { action: 'confirm', reason: 'Impossible velocity', apply_throttle: true }, token })}>Confirm</button>
                      <button aria-label="Dismiss spoof" className="px-3 py-1 text-sm bg-gray-600 text-white rounded" onClick={() => reviewSpoof.mutate({ detectionId: d.id, payload: { action: 'dismiss', reason: 'Benign' }, token })}>Dismiss</button>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-gray-600">No pending detections.</p>
          )}
        </section>

        {/* Throttles */}
        <section className="bg-white border rounded-lg p-4">
          <h2 className="text-xl font-semibold mb-3 flex items-center gap-2"><Gauge className="h-5 w-5" /> Active Throttles</h2>
          {throttles.data?.data?.length ? (
            <ul className="divide-y">
              {throttles.data.data.map((t: any) => (
                <li key={t.id} className="py-3">
                  <div className="flex justify-between items-center">
                    <div>
                      <div className="font-medium">User #{t.user_id} · Sev {t.severity} · Reason {t.reason}</div>
                      <div className="text-xs text-gray-500">Started {t.started_at} · Expires {t.expires_at ?? 'never'}</div>
                    </div>
                    <button aria-label="Remove throttle" className="px-3 py-1 text-sm bg-gray-800 text-white rounded flex items-center gap-2" onClick={() => removeThrottle.mutate({ throttleId: t.id, token })}>
                      <Trash2 className="h-4 w-4" /> Remove
                    </button>
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-gray-600">No active throttles.</p>
          )}
        </section>

        {/* Recent Actions */}
        <section className="bg-white border rounded-lg p-4">
          <h2 className="text-xl font-semibold mb-3 flex items-center gap-2"><CheckCircle2 className="h-5 w-5" /> Recent Actions</h2>
          {dashboard.data?.recent_actions?.length ? (
            <ul className="divide-y">
              {dashboard.data.recent_actions.map((a: any) => (
                <li key={a.id} className="py-3">
                  <div className="flex justify-between items-center">
                    <div>
                      <div className="font-medium">{a.action_type}</div>
                      <div className="text-xs text-gray-500">By #{a.moderator_id} · Target User #{a.target_user_id ?? '—'} · Artifact #{a.target_artifact_id ?? '—'}</div>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-gray-600">No actions yet.</p>
          )}
        </section>
      </div>
    </div>
  );
}

function StatCard({ label, value, icon }: { label: string; value: number | string; icon: React.ReactNode }) {
  return (
    <div className="bg-white border rounded-lg p-4">
      <div className="text-gray-500 text-sm mb-1">{label}</div>
      <div className="flex items-center gap-2">
        {icon}
        <div className="text-2xl font-bold">{value}</div>
      </div>
    </div>
  );
}
