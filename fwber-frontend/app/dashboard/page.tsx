'use client';

import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import { useQuery } from '@tanstack/react-query';
import {
  Heart,
  Users,
  User,
  MessageSquare,
  TrendingUp,
  Clock,
  Target,
  Crown,
  Flame,
  Store,
  Gavel,
  Wallet,
  Bell,
  Calendar,
  Plane,
  Sparkles,
  Rocket,
  Gift,
  Phone,
  Lock,
  Shield,
  CircleHelp,
  Share2,
  Radio,
  Map,
  Award,
  Compass,
  Wand2,
  HeartHandshake,
} from 'lucide-react';
import Link from 'next/link';
import ProfileCompletenessWidget from '@/components/ProfileCompletenessWidget';
import { ProximityPresenceCompact } from '@/components/realtime/ProximityPresenceView';
import AppHeader from '@/components/AppHeader';
import { ActivityFeed } from '@/components/ActivityFeed';
import { api } from '@/lib/api/client';

interface DashboardStats {
  total_matches: number;
  pending_matches: number;
  accepted_matches: number;
  conversations: number;
  profile_views: number;
  today_views: number;
  match_score_avg: number;
  response_rate: number;
  days_active: number;
  last_login: string;
}

export default function DashboardPage() {
  const { user, token, isAuthenticated } = useAuth();

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    enabled: isAuthenticated && !!token,
    queryFn: () => api.get<DashboardStats>('/dashboard/stats'),
  });

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader />

        <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
          <div className="px-4 py-6 sm:px-0">
            <div className="mb-8 flex justify-between items-end">
              <div>
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Dashboard</h2>
                <p className="text-gray-600 dark:text-gray-400">
                  Here&apos;s what&apos;s happening with your matches and the restored surfaces that now belong in the signed-in app.
                </p>
              </div>
            </div>

            {statsLoading ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {[...Array(4)].map((_, i) => (
                  <div key={i} className="bg-white rounded-xl border border-gray-200 p-6 animate-pulse dark:bg-gray-800 dark:border-gray-700">
                    <div className="h-6 bg-gray-200 rounded w-3/4 mb-4 dark:bg-gray-700"></div>
                    <div className="h-8 bg-gray-200 rounded w-1/2 dark:bg-gray-700"></div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <StatCard
                  icon={<Heart className="w-6 h-6" />}
                  label="Total Matches"
                  value={stats?.total_matches || 0}
                  subtext={`${stats?.pending_matches || 0} pending`}
                  color="purple"
                  link="/matches"
                />
                <StatCard
                  icon={<MessageSquare className="w-6 h-6" />}
                  label="Active Chats"
                  value={stats?.conversations || 0}
                  subtext={`${stats?.response_rate || 0}% response rate`}
                  color="blue"
                  link="/messages"
                />
                <StatCard
                  icon={<Users className="w-6 h-6" />}
                  label="Profile Views"
                  value={stats?.profile_views || 0}
                  subtext={`${stats?.today_views || 0} today`}
                  color="green"
                  link="/profile"
                />
                <StatCard
                  icon={<TrendingUp className="w-6 h-6" />}
                  label="Match Score"
                  value={`${stats?.match_score_avg || 0}%`}
                  subtext="Average compatibility"
                  color="orange"
                  link="/matches"
                />
              </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
              <div className="lg:col-span-2 space-y-6">
                <ActivityFeed maxItems={8} showRefresh />

                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                  <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <Target className="w-5 h-5 text-purple-500" />
                    Quick Actions
                  </h3>
                  <div className="grid grid-cols-2 gap-4 lg:grid-cols-3">
                    <ActionButton href="/nearby" label="Discover Nearby" icon="📍" color="purple" />
                    <ActionButton href="/messages" label="View Messages" icon="💬" color="blue" />
                    <ActionButton href="/friends" label="Open Friends" icon="🤝" color="green" />
                    <ActionButton href="/wallet" label="Open Wallet" icon="💸" color="orange" />
                    <ActionButton href="/notifications" label="Open Notifications" icon="🔔" color="purple" />
                    <ActionButton href="/profile" label="Manage Profile" icon="✏️" color="gray" />
                  </div>
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                  <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">Restored sections</h3>
                  <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    These are the user-approved restored surfaces that should feel first-class again in the rewind branch, without re-elevating excluded federation, governance, or journal-era branches.
                  </p>
                  <div className="space-y-6">
                    <div>
                      <div className="mb-3 flex items-center gap-2">
                        <Heart className="h-4 w-4 text-pink-500" />
                        <h4 className="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Core dating loop</h4>
                      </div>
                      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <FeatureSurfaceCard
                          href="/matching"
                          title="Matching & Attraction"
                          description="Open recommendations, matches, admirers, profile-view intent, and nearby dating signals from one restored hub."
                          icon={<Heart className="h-5 w-5" />}
                          accent="pink"
                        />
                        <FeatureSurfaceCard
                          href="/connections"
                          title="Connections"
                          description="Open messages, friends, activity, notifications, and adjacent direct-social flows from one restored hub."
                          icon={<HeartHandshake className="h-5 w-5" />}
                          accent="red"
                        />
                        <FeatureSurfaceCard
                          href="/scenes"
                          title="Scenes & Discovery"
                          description="Open recommendations, groups, topics, matches, and broader social-discovery surfaces from one restored hub."
                          icon={<Compass className="h-5 w-5" />}
                          accent="purple"
                        />
                        <FeatureSurfaceCard
                          href="/places"
                          title="Places & Nearby"
                          description="Open nearby people, venues, deals, date planning, and location-aware surfaces from one local-discovery hub."
                          icon={<Map className="h-5 w-5" />}
                          accent="green"
                        />
                        <FeatureSurfaceCard
                          href="/plans"
                          title="Plans & Meetups"
                          description="Open events, date planning, nearby discovery, venues, and deals from one restored local outing hub."
                          icon={<Calendar className="h-5 w-5" />}
                          accent="purple"
                        />
                      </div>
                    </div>

                    <div>
                      <div className="mb-3 flex items-center gap-2">
                        <User className="h-4 w-4 text-blue-500" />
                        <h4 className="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Identity, trust & support</h4>
                      </div>
                      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <FeatureSurfaceCard
                          href="/identity"
                          title="Identity & Profile"
                          description="Open profile, photos, verification, and identity-focused settings from one restored hub."
                          icon={<User className="h-5 w-5" />}
                          accent="blue"
                        />
                        <FeatureSurfaceCard
                          href="/reputation"
                          title="Reputation & Trust"
                          description="Open achievements, verification, profile views, and social-proof surfaces from one trust-focused hub."
                          icon={<Award className="h-5 w-5" />}
                          accent="yellow"
                        />
                        <FeatureSurfaceCard
                          href="/operations"
                          title="Trust & Operations"
                          description="Open safety, settings, merchant, moderation, and operational control surfaces from one restored hub."
                          icon={<Shield className="h-5 w-5" />}
                          accent="red"
                        />
                        <FeatureSurfaceCard
                          href="/support"
                          title="Support & Policies"
                          description="Open help, support contact, privacy, terms, safety resources, and user-protection references from one restored hub."
                          icon={<CircleHelp className="h-5 w-5" />}
                          accent="blue"
                        />
                        <FeatureSurfaceCard
                          href="/settings/travel"
                          title="Travel Mode"
                          description="Open the restored travel-mode controls directly from the dashboard."
                          icon={<Plane className="h-5 w-5" />}
                          accent="slate"
                        />
                      </div>
                    </div>

                    <div>
                      <div className="mb-3 flex items-center gap-2">
                        <Wallet className="h-4 w-4 text-green-500" />
                        <h4 className="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Premium, growth & playful surfaces</h4>
                      </div>
                      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <FeatureSurfaceCard
                          href="/economy"
                          title="Premium & Economy"
                          description="Open premium, wallet, referrals, boosts, gifts, and unlock-related monetization flows from one restored hub."
                          icon={<Wallet className="h-5 w-5" />}
                          accent="green"
                        />
                        <FeatureSurfaceCard
                          href="/unlocks"
                          title="Unlock Center"
                          description="Jump into token-gated perks, share unlocks, and premium reveal surfaces from one recovery hub."
                          icon={<Lock className="h-5 w-5" />}
                          accent="yellow"
                        />
                        <FeatureSurfaceCard
                          href="/studio"
                          title="Studio & AI"
                          description="Open roast tools, wingman, content generation, bounties, and adjacent viral/creative surfaces from one restored hub."
                          icon={<Wand2 className="h-5 w-5" />}
                          accent="pink"
                        />
                        <FeatureSurfaceCard
                          href="/video"
                          title="Video Calls"
                          description="Open call history and start restored video calls from a top-level page."
                          icon={<Phone className="h-5 w-5" />}
                          accent="blue"
                        />
                        <FeatureSurfaceCard
                          href="/share-unlock"
                          title="Share Unlocks"
                          description="Open the viral unlock route directly instead of hunting through hidden CTA chains."
                          icon={<Share2 className="h-5 w-5" />}
                          accent="purple"
                        />
                      </div>
                    </div>

                    <div>
                      <div className="mb-3 flex items-center gap-2">
                        <Store className="h-4 w-4 text-orange-500" />
                        <h4 className="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Community, live & local business</h4>
                      </div>
                      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <FeatureSurfaceCard
                          href="/spaces"
                          title="Live Spaces"
                          description="Open chatrooms, audio rooms, local pulse, boards, burner links, and conference pulse from one top-level hub."
                          icon={<Radio className="h-5 w-5" />}
                          accent="purple"
                        />
                        <FeatureSurfaceCard
                          href="/commerce"
                          title="Merchants & Commerce"
                          description="Open merchant onboarding, business operations, analytics, promotions, and local broadcast tooling from one restored hub."
                          icon={<Store className="h-5 w-5" />}
                          accent="orange"
                        />
                        <FeatureSurfaceCard
                          href={user?.role === 'merchant' ? '/merchant/dashboard' : '/merchant/register'}
                          title={user?.role === 'merchant' ? 'Merchant Portal' : 'Become a Merchant'}
                          description={user?.role === 'merchant'
                            ? 'Run storefront inventory, redemptions, analytics, and trust status.'
                            : 'Open a storefront, list redeemable items, and show up in nearby marketplace results.'}
                          icon={<Store className="h-5 w-5" />}
                          accent="pink"
                        />
                        {(user as { is_moderator?: boolean } | null)?.is_moderator ? (
                          <FeatureSurfaceCard
                            href="/moderation"
                            title="Moderation"
                            description="Open the moderation dashboard for reports, merchant review, and trust tooling."
                            icon={<Gavel className="h-5 w-5" />}
                            accent="slate"
                          />
                        ) : null}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-6">
                <ProfileCompletenessWidget />
                <ProximityPresenceCompact nearbyUsers={[]} />

                <div className="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-6">
                  <div className="flex items-center gap-2 mb-3">
                    <Clock className="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    <span className="text-sm font-medium text-purple-900 dark:text-purple-100">Account Status</span>
                  </div>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-600 dark:text-gray-400">Member since:</span>
                      <span className="font-medium text-gray-900 dark:text-white">{stats?.days_active || 0} days</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600 dark:text-gray-400">Last active:</span>
                      <span className="font-medium text-gray-900 dark:text-white">
                        {stats?.last_login ? new Date(stats.last_login).toLocaleDateString() : 'Today'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  );
}

interface StatCardProps {
  icon: React.ReactNode;
  label: string;
  value: number | string;
  subtext: string;
  color: 'purple' | 'blue' | 'green' | 'orange' | 'yellow';
  link: string;
}

function StatCard({ icon, label, value, subtext, color, link }: StatCardProps) {
  const colorClasses = {
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
  };

  return (
    <Link href={link} prefetch={false} className="block group">
      <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-all hover:border-purple-300 dark:hover:border-purple-700">
        <div className={`w-12 h-12 rounded-lg ${colorClasses[color]} flex items-center justify-center mb-4`}>
          {icon}
        </div>
        <div className="text-3xl font-bold text-gray-900 dark:text-white mb-1">{value}</div>
        <div className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{label}</div>
        <div className="text-xs text-gray-500 dark:text-gray-400">{subtext}</div>
      </div>
    </Link>
  );
}

function ActionButton({ href, label, icon, color }: { href: string; label: string; icon: string; color: string }) {
  const colorClasses = {
    purple: 'hover:bg-purple-50 hover:border-purple-300 dark:hover:bg-purple-900/20 dark:hover:border-purple-700',
    blue: 'hover:bg-blue-50 hover:border-blue-300 dark:hover:bg-blue-900/20 dark:hover:border-blue-700',
    green: 'hover:bg-green-50 hover:border-green-300 dark:hover:bg-green-900/20 dark:hover:border-green-700',
    orange: 'hover:bg-orange-50 hover:border-orange-300 dark:hover:bg-orange-900/20 dark:hover:border-orange-700',
    gray: 'hover:bg-gray-50 hover:border-gray-300 dark:hover:bg-gray-800 dark:hover:border-gray-600',
  };

  return (
    <Link
      href={href}
      prefetch={false}
      className={`flex items-center gap-3 p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg transition-all ${colorClasses[color as keyof typeof colorClasses]}`}
    >
      <span className="text-2xl">{icon}</span>
      <span className="text-sm font-medium text-gray-700 dark:text-gray-300">{label}</span>
    </Link>
  );
}

function FeatureSurfaceCard({
  href,
  title,
  description,
  icon,
  accent,
}: {
  href: string;
  title: string;
  description: string;
  icon: React.ReactNode;
  accent: 'yellow' | 'green' | 'orange' | 'purple' | 'pink' | 'blue' | 'slate';
}) {
  const accentClasses = {
    yellow: 'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
    green: 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300',
    orange: 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-800 dark:bg-orange-900/20 dark:text-orange-300',
    purple: 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-800 dark:bg-purple-900/20 dark:text-purple-300',
    pink: 'border-pink-200 bg-pink-50 text-pink-700 dark:border-pink-800 dark:bg-pink-900/20 dark:text-pink-300',
    blue: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
    slate: 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200',
  };

  return (
    <Link href={href} prefetch={false} className="block">
      <div className={`h-full rounded-xl border p-4 transition hover:shadow-md ${accentClasses[accent]}`}>
        <div className="mb-3 inline-flex rounded-lg bg-white/70 p-2 dark:bg-black/20">{icon}</div>
        <h4 className="text-sm font-semibold">{title}</h4>
        <p className="mt-2 text-sm leading-6 text-current/80">{description}</p>
      </div>
    </Link>
  );
}
