'use client';

import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import { api } from '@/lib/api/client';
import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import {
  Heart, MessageSquare, Users, TrendingUp, Clock,
  Crown, Sparkles, MapPin, Shield, Compass, Radio,
  Award, Wallet, Store, Wand2, Calendar, UserPlus,
  Eye, Star, Rocket, Gift, Lock, Phone, Share2,
  Settings, Bell, Plane, Search, Gavel, Flame,
  CheckCircle2, User
} from 'lucide-react';
import Link from 'next/link';
import ProfileCompletenessWidget from '@/components/ProfileCompletenessWidget';
import AppHeader from '@/components/AppHeader';
import { ActivityFeed } from '@/components/ActivityFeed';
import { DailyStreakModal } from '@/components/gamification/DailyStreakModal';

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
  current_streak: number;
  streak_just_updated: boolean;
  reverb_healthy?: boolean;
}

export default function DashboardPage() {
  const { user, token, isAuthenticated } = useAuth();
  const [isStreakModalOpen, setIsStreakModalOpen] = useState(false);

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    enabled: isAuthenticated && !!token,
    queryFn: async () => {
      try {
        const data = await api.get<DashboardStats>('/dashboard/stats');
        if (data?.streak_just_updated) {
          setIsStreakModalOpen(true);
        }
        return data || null;
      } catch (error) {
        console.error('Dashboard stats fetch failed:', error);
        return null;
      }
    },
  });

  const dailyStreak = stats?.current_streak || 0;

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader />
        <DailyStreakModal
          isOpen={isStreakModalOpen}
          currentStreak={dailyStreak}
          onClose={() => setIsStreakModalOpen(false)}
        />

        <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
          <div className="px-4 py-6 sm:px-0">

            {/* ── Greeting ── */}
            <div className="mb-6">
              <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
                Hey, {(user as any)?.display_name || (user as any)?.name || 'there'} 👋
              </h2>
              <p className="text-gray-500 dark:text-gray-400 mt-1">
                Your matches, messages, and everything else — one glance.
              </p>
            </div>

            {/* ── Key Metrics Row ── */}
            {statsLoading ? (
              <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
                {[...Array(5)].map((_, i) => (
                  <div key={i} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 animate-pulse">
                    <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3 mb-3" />
                    <div className="h-7 bg-gray-200 dark:bg-gray-700 rounded w-1/2" />
                  </div>
                ))}
              </div>
            ) : (
              <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
                <MetricCard
                  icon={<Heart className="w-5 h-5" />}
                  label="Matches"
                  value={stats?.total_matches || 0}
                  detail={`${stats?.pending_matches || 0} pending`}
                  accent="pink"
                  href="/matches"
                />
                <MetricCard
                  icon={<MessageSquare className="w-5 h-5" />}
                  label="Chats"
                  value={stats?.conversations || 0}
                  detail={`${stats?.response_rate || 0}% response`}
                  accent="blue"
                  href="/messages"
                />
                <MetricCard
                  icon={<Eye className="w-5 h-5" />}
                  label="Views"
                  value={stats?.profile_views || 0}
                  detail={`${stats?.today_views || 0} today`}
                  accent="green"
                  href="/profile-views"
                />
                <MetricCard
                  icon={<TrendingUp className="w-5 h-5" />}
                  label="Compatibility"
                  value={`${stats?.match_score_avg || 0}%`}
                  detail="avg score"
                  accent="purple"
                  href="/matching"
                />
                <MetricCard
                  icon={<Flame className="w-5 h-5" />}
                  label="Streak"
                  value={dailyStreak}
                  detail={dailyStreak > 0 ? 'days active' : 'Start today!'}
                  accent="orange"
                  href="/daily-checkin"
                />
              </div>
            )}

            {/* ── Main Grid: Activity + Sidebar ── */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

              {/* Left: Activity Feed */}
              <div className="lg:col-span-2 space-y-6">
                <ActivityFeed maxItems={6} showRefresh />

                {/* Quick Actions */}
                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                  <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
                    Quick Actions
                  </h3>
                  <div className="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-2">
                    <QuickAction href="/matching" label="Answer Questions" icon={<Heart className="w-4 h-4" />} />
                    <QuickAction href="/recommendations" label="Discover" icon={<Compass className="w-4 h-4" />} />
                    <QuickAction href="/messages" label="Messages" icon={<MessageSquare className="w-4 h-4" />} />
                    <QuickAction href="/profile/edit" label="Edit Profile" icon={<User className="w-4 h-4" />} accent="green" />
                    <QuickAction href="/premium" label="Go Premium" icon={<Crown className="w-4 h-4" />} accent="amber" />
                    <QuickAction href="/studio" label="AI Wingman" icon={<Sparkles className="w-4 h-4" />} accent="purple" />
                  </div>
                </div>
              </div>

              {/* Right Sidebar */}
              <div className="space-y-4">
                <ProfileCompletenessWidget />

                {/* Streak & Account Card */}
                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                  <div className="flex items-center justify-between mb-3">
                    <div className="flex items-center gap-2">
                      <Clock className="w-4 h-4 text-gray-400" />
                      <span className="text-sm font-medium text-gray-900 dark:text-white">Account</span>
                    </div>
                    <span className="text-xs text-gray-500 dark:text-gray-400">
                      {stats?.days_active || 0} days
                    </span>
                  </div>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-500 dark:text-gray-400">Last active</span>
                      <span className="font-medium text-gray-900 dark:text-white">
                        {stats?.last_login ? new Date(stats.last_login).toLocaleDateString() : 'Today'}
                      </span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-500 dark:text-gray-400">Network</span>
                      <span className={`font-medium ${stats?.reverb_healthy ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'}`}>
                        {stats?.reverb_healthy ? '● Live' : '○ Syncing'}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Who Liked You CTA */}
                <Link href="/premium/who-likes-you" prefetch={false} className="block">
                  <div className="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-5 hover:shadow-md transition">
                    <div className="flex items-center gap-2 mb-2">
                      <Crown className="w-4 h-4 text-amber-600 dark:text-amber-400" />
                      <span className="text-sm font-semibold text-amber-900 dark:text-amber-100">Who Liked You</span>
                    </div>
                    <p className="text-xs text-amber-700 dark:text-amber-300">See who&apos;s already interested in you</p>
                  </div>
                </Link>
              </div>
            </div>

            {/* ── Feature Navigation ── */}
            <div className="mb-4">
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Explore</h3>
            </div>

            {/* Discover & Connect */}
            <SectionHeader icon={<Heart className="w-4 h-4" />} title="Discover & Connect" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/matching" title="Matching" subtitle="95 personality questions" icon={<Heart className="w-5 h-5" />} accent="pink" />
              <FeatureTile href="/recommendations" title="Discover" subtitle="Browse nearby people" icon={<Compass className="w-5 h-5" />} accent="purple" />
              <FeatureTile href="/matches" title="Matches" subtitle="Your mutual likes" icon={<Star className="w-5 h-5" />} accent="amber" />
              <FeatureTile href="/who-likes-you" title="Admirers" subtitle="Who likes you" icon={<Crown className="w-5 h-5" />} accent="amber" />
              <FeatureTile href="/profile-views" title="Profile Views" subtitle="Who checked you out" icon={<Eye className="w-5 h-5" />} accent="green" />
              <FeatureTile href="/search" title="Search" subtitle="Find anyone" icon={<Search className="w-5 h-5" />} accent="slate" />
            </div>

            {/* Chat & Social */}
            <SectionHeader icon={<MessageSquare className="w-4 h-4" />} title="Chat & Social" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/messages" title="Messages" subtitle="Private chats" icon={<MessageSquare className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/friends" title="Friends" subtitle="Your connections" icon={<UserPlus className="w-5 h-5" />} accent="green" />
              <FeatureTile href="/activity" title="Activity" subtitle="Recent interactions" icon={<Rocket className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/notifications" title="Notifications" subtitle="Alerts & updates" icon={<Bell className="w-5 h-5" />} accent="red" />
              <FeatureTile href="/groups" title="Groups" subtitle="Interest communities" icon={<Users className="w-5 h-5" />} accent="purple" />
              <FeatureTile href="/topics" title="Topics" subtitle="Discussion threads" icon={<MessageSquare className="w-5 h-5" />} accent="slate" />
            </div>

            {/* Local & Events */}
            <SectionHeader icon={<MapPin className="w-4 h-4" />} title="Local & Events" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/local-pulse" title="Local Pulse" subtitle="What&apos;s nearby" icon={<MapPin className="w-5 h-5" />} accent="green" />
              <FeatureTile href="/events" title="Events" subtitle="Meetups & gatherings" icon={<Calendar className="w-5 h-5" />} accent="purple" />
              <FeatureTile href="/venues" title="Venues" subtitle="Bars, cafés, spots" icon={<MapPin className="w-5 h-5" />} accent="green" />
              <FeatureTile href="/deals" title="Deals" subtitle="Local offers" icon={<Store className="w-5 h-5" />} accent="orange" />
              <FeatureTile href="/date-planner" title="Date Planner" subtitle="Plan your outing" icon={<Calendar className="w-5 h-5" />} accent="pink" />
              <FeatureTile href="/nearby" title="Nearby" subtitle="People around you" icon={<MapPin className="w-5 h-5" />} accent="green" />
            </div>

            {/* Live Spaces */}
            <SectionHeader icon={<Radio className="w-4 h-4" />} title="Live Spaces" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/spaces" title="Chat Rooms" subtitle="Group conversations" icon={<MessageSquare className="w-5 h-5" />} accent="purple" />
              <FeatureTile href="/audio-rooms" title="Audio Rooms" subtitle="Live voice chat" icon={<Radio className="w-5 h-5" />} accent="purple" />
              <FeatureTile href="/bulletin-boards" title="Bulletins" subtitle="Community posts" icon={<MessageSquare className="w-5 h-5" />} accent="slate" />
              <FeatureTile href="/conference-pulse" title="Conference" subtitle="Event channels" icon={<Radio className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/burner" title="Burner Links" subtitle="Temporary share" icon={<Lock className="w-5 h-5" />} accent="red" />
              <FeatureTile href="/proximity-chatrooms" title="Proximity Chat" subtitle="Location-based" icon={<MapPin className="w-5 h-5" />} accent="green" />
            </div>

            {/* AI Wingman */}
            <SectionHeader icon={<Sparkles className="w-4 h-4" />} title="AI Wingman" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/studio" title="Studio" subtitle="All AI tools" icon={<Wand2 className="w-5 h-5" />} accent="purple" />
              <FeatureTile href="/wingman/roast" title="Roast Me" subtitle="Profile critique" icon={<Sparkles className="w-5 h-5" />} accent="pink" />
              <FeatureTile href="/wingman/cosmic" title="Cosmic Match" subtitle="AI compatibility" icon={<Star className="w-5 h-5" />} accent="amber" />
              <FeatureTile href="/wingman/date-ideas" title="Date Ideas" subtitle="AI suggestions" icon={<Calendar className="w-5 h-5" />} accent="purple" />
              <FeatureTile href="/wingman/vibe" title="Vibe Check" subtitle="Read the room" icon={<Sparkles className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/content-generation" title="Content Gen" subtitle="AI writing help" icon={<Wand2 className="w-5 h-5" />} accent="purple" />
            </div>

            {/* Economy & Premium */}
            <SectionHeader icon={<Wallet className="w-4 h-4" />} title="Economy & Premium" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/wallet" title="Wallet" subtitle="FWB tokens" icon={<Wallet className="w-5 h-5" />} accent="green" />
              <FeatureTile href="/premium" title="Premium" subtitle="Upgrade your plan" icon={<Crown className="w-5 h-5" />} accent="amber" />
              <FeatureTile href="/boosts" title="Boosts" subtitle="Get more visible" icon={<Rocket className="w-5 h-5" />} accent="orange" />
              <FeatureTile href="/gifts" title="Gifts" subtitle="Send tokens" icon={<Gift className="w-5 h-5" />} accent="pink" />
              <FeatureTile href="/referrals" title="Referrals" subtitle="Invite & earn" icon={<Share2 className="w-5 h-5" />} accent="green" />
              <FeatureTile href="/unlocks" title="Unlocks" subtitle="Premium features" icon={<Lock className="w-5 h-5" />} accent="yellow" />
            </div>

            {/* Profile & Identity */}
            <SectionHeader icon={<Users className="w-4 h-4" />} title="Profile & Identity" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/profile" title="My Profile" subtitle="View your page" icon={<Users className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/profile/edit" title="Edit Profile" subtitle="Update your info" icon={<Users className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/photos" title="Photos" subtitle="Manage gallery" icon={<Users className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/identity" title="Identity Hub" subtitle="Verification & more" icon={<Shield className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/reputation" title="Reputation" subtitle="Achievements & trust" icon={<Award className="w-5 h-5" />} accent="amber" />
              <FeatureTile href="/vouch" title="Vouch" subtitle="Vouch for friends" icon={<Users className="w-5 h-5" />} accent="green" />
            </div>

            {/* Safety & Settings */}
            <SectionHeader icon={<Shield className="w-4 h-4" />} title="Safety & Settings" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/safety" title="Safety" subtitle="Tools & resources" icon={<Shield className="w-5 h-5" />} accent="red" />
              <FeatureTile href="/settings" title="Settings" subtitle="All preferences" icon={<Settings className="w-5 h-5" />} accent="slate" />
              <FeatureTile href="/settings/privacy" title="Privacy" subtitle="Location & visibility" icon={<Lock className="w-5 h-5" />} accent="red" />
              <FeatureTile href="/settings/notifications" title="Notification Prefs" subtitle="Alerts & quiet hours" icon={<Bell className="w-5 h-5" />} accent="slate" />
              <FeatureTile href="/settings/security" title="Security" subtitle="Password & 2FA" icon={<Shield className="w-5 h-5" />} accent="red" />
              <FeatureTile href="/settings/travel" title="Travel Mode" subtitle="Change your location" icon={<Plane className="w-5 h-5" />} accent="blue" />
            </div>

            {/* Commerce & Merchant */}
            <SectionHeader icon={<Store className="w-4 h-4" />} title="Commerce & Merchant" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/marketplace" title="Marketplace" subtitle="Browse local deals" icon={<Store className="w-5 h-5" />} accent="orange" />
              <FeatureTile href="/merchant/register" title="Merchant Sign Up" subtitle="List your business" icon={<Store className="w-5 h-5" />} accent="orange" />
              <FeatureTile href="/merchant/dashboard" title="Merchant Dashboard" subtitle="Manage storefront" icon={<Store className="w-5 h-5" />} accent="orange" />
              <FeatureTile href="/merchant/analytics" title="Merchant Analytics" subtitle="Sales & insights" icon={<TrendingUp className="w-5 h-5" />} accent="amber" />
              <FeatureTile href="/subscription" title="Subscription" subtitle="Manage your plan" icon={<Crown className="w-5 h-5" />} accent="amber" />
              <FeatureTile href="/leaderboard" title="Leaderboard" subtitle="Token rankings" icon={<Award className="w-5 h-5" />} accent="amber" />
            </div>

            {/* More */}
            <SectionHeader icon={<CheckCircle2 className="w-4 h-4" />} title="More" />
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-8">
              <FeatureTile href="/video" title="Video Calls" subtitle="Call history" icon={<Phone className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/scrapbook" title="Scrapbook" subtitle="Saved moments" icon={<Star className="w-5 h-5" />} accent="pink" />
              <FeatureTile href="/ice-breakers" title="Ice Breakers" subtitle="Conversation starters" icon={<MessageSquare className="w-5 h-5" />} accent="blue" />
              <FeatureTile href="/share-unlock" title="Share Unlocks" subtitle="Viral features" icon={<Share2 className="w-5 h-5" />} accent="purple" />
              <FeatureTile href="/feedback" title="Feedback" subtitle="Tell us what you think" icon={<MessageSquare className="w-5 h-5" />} accent="slate" />
              <FeatureTile href="/help" title="Help Center" subtitle="FAQs & guides" icon={<CheckCircle2 className="w-5 h-5" />} accent="slate" />
              {(user as any)?.is_moderator && (
                <FeatureTile href="/admin/monitoring" title="Admin Monitor" subtitle="Autonomous system" icon={<Rocket className="w-5 h-5" />} accent="purple" />
              )}
              {(user as any)?.is_moderator && (
                <FeatureTile href="/moderation" title="Moderation" subtitle="Reports & reviews" icon={<Gavel className="w-5 h-5" />} accent="red" />
              )}
            </div>

          </div>
        </main>
      </div>
    </ProtectedRoute>
  );
}

/* ────────────────────── Sub-Components ────────────────────── */

function MetricCard({
  icon, label, value, detail, accent, href,
}: {
  icon: React.ReactNode; label: string; value: number | string;
  detail: string; accent: string; href: string;
}) {
  const accents: Record<string, string> = {
    pink: 'bg-pink-100 text-pink-600 dark:bg-pink-900/30 dark:text-pink-400',
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    amber: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
    red: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
  };

  return (
    <Link href={href} prefetch={false} className="block group">
      <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-lg transition-all hover:border-purple-300 dark:hover:border-purple-700">
        <div className={`w-10 h-10 rounded-lg ${accents[accent] || accents.purple} flex items-center justify-center mb-3`}>
          {icon}
        </div>
        <div className="text-2xl font-bold text-gray-900 dark:text-white leading-none mb-1">{value}</div>
        <div className="text-xs font-medium text-gray-600 dark:text-gray-400">{label}</div>
        <div className="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">{detail}</div>
      </div>
    </Link>
  );
}

function QuickAction({
  href, label, icon, accent,
}: {
  href: string; label: string; icon: React.ReactNode; accent?: string;
}) {
  const bg = accent === 'green' ? 'hover:bg-green-50 dark:hover:bg-green-900/20'
    : accent === 'amber' ? 'hover:bg-amber-50 dark:hover:bg-amber-900/20'
    : accent === 'purple' ? 'hover:bg-purple-50 dark:hover:bg-purple-900/20'
    : 'hover:bg-gray-50 dark:hover:bg-gray-700';

  return (
    <Link href={href} prefetch={false} className={`flex flex-col items-center gap-1.5 p-3 rounded-lg border border-gray-100 dark:border-gray-700 transition-colors ${bg}`}>
      <div className="text-gray-600 dark:text-gray-400">{icon}</div>
      <span className="text-[11px] font-medium text-gray-700 dark:text-gray-300 text-center leading-tight">{label}</span>
    </Link>
  );
}

function SectionHeader({ icon, title }: { icon: React.ReactNode; title: string }) {
  return (
    <div className="flex items-center gap-2 mb-3">
      <div className="text-gray-400 dark:text-gray-500">{icon}</div>
      <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{title}</h3>
    </div>
  );
}

function FeatureTile({
  href, title, subtitle, icon, accent,
}: {
  href: string; title: string; subtitle: string;
  icon: React.ReactNode; accent: string;
}) {
  const accents: Record<string, { border: string; bg: string; iconBg: string }> = {
    pink:   { border: 'border-pink-200 dark:border-pink-800', bg: 'bg-pink-50 dark:bg-pink-900/10', iconBg: 'bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400' },
    blue:   { border: 'border-blue-200 dark:border-blue-800', bg: 'bg-blue-50 dark:bg-blue-900/10', iconBg: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' },
    green:  { border: 'border-green-200 dark:border-green-800', bg: 'bg-green-50 dark:bg-green-900/10', iconBg: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' },
    purple: { border: 'border-purple-200 dark:border-purple-800', bg: 'bg-purple-50 dark:bg-purple-900/10', iconBg: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400' },
    orange: { border: 'border-orange-200 dark:border-orange-800', bg: 'bg-orange-50 dark:bg-orange-900/10', iconBg: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400' },
    amber:  { border: 'border-amber-200 dark:border-amber-800', bg: 'bg-amber-50 dark:bg-amber-900/10', iconBg: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400' },
    red:    { border: 'border-red-200 dark:border-red-800', bg: 'bg-red-50 dark:bg-red-900/10', iconBg: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' },
    yellow: { border: 'border-yellow-200 dark:border-yellow-800', bg: 'bg-yellow-50 dark:bg-yellow-900/10', iconBg: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' },
    slate:  { border: 'border-slate-200 dark:border-slate-700', bg: 'bg-slate-50 dark:bg-slate-900/10', iconBg: 'bg-slate-100 dark:bg-slate-900/30 text-slate-600 dark:text-slate-400' },
  };

  const a = accents[accent] || accents.slate;

  return (
    <Link href={href} prefetch={false} className="block group">
      <div className={`h-full rounded-xl border p-4 transition-all hover:shadow-md ${a.border} ${a.bg}`}>
        <div className={`inline-flex rounded-lg p-2 mb-3 ${a.iconBg}`}>
          {icon}
        </div>
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white leading-tight">{title}</h4>
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">{subtitle}</p>
      </div>
    </Link>
  );
}
