'use client';

import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import { useQuery } from '@tanstack/react-query';
import { Heart, Users, MessageSquare, TrendingUp, Clock, Zap, Award, Target } from 'lucide-react';
import axios from 'axios';
import Image from 'next/image';
import Link from 'next/link';
import ProfileCompletenessWidget from '@/components/ProfileCompletenessWidget';
import { ProximityPresenceCompact } from '@/components/realtime';
import AppHeader from '@/components/AppHeader';
import { ActivityFeed } from '@/components/ActivityFeed';
import BoostButton from '@/components/BoostButton';
import { ReferralModal } from '@/components/viral/ReferralModal';

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
  current_streak: number;
  last_login: string;
}

export default function DashboardPage() {
  // Get user for legacy cards section
  const { user } = useAuth();
  // Activity feed now uses its own component with real-time presence

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: async () => {
      const token = localStorage.getItem('fwber_token');
      const response = await axios.get<DashboardStats>(
        `${process.env.NEXT_PUBLIC_API_URL}/dashboard/stats`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      return response.data;
    },
  });

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        {/* Unified App Header with Navigation & Notifications */}
        <AppHeader />

        {/* Main Content */}
        <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
          <div className="px-4 py-6 sm:px-0">
            <div className="mb-8 flex justify-between items-end">
              <div>
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                  Dashboard
                </h2>
                <p className="text-gray-600 dark:text-gray-400">Here&apos;s what&apos;s happening with your matches.</p>
              </div>
              <div className="flex gap-2">
                <ReferralModal />
                <BoostButton />
              </div>
            </div>

            {/* Stats Grid */}
            {statsLoading ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                {[...Array(5)].map((_, i) => (
                  <div key={i} className="bg-white rounded-xl border border-gray-200 p-6 animate-pulse">
                    <div className="h-6 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div className="h-8 bg-gray-200 rounded w-1/2"></div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
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
                <StatCard
                  icon={<Zap className="w-6 h-6" />}
                  label="Daily Streak"
                  value={stats?.current_streak || 0}
                  subtext="Days active"
                  color="yellow"
                  link="#"
                />
              </div>
            )}

            {/* Two Column Layout */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
              {/* Main Content - 2 columns */}
              <div className="lg:col-span-2 space-y-6">
                {/* Real-time Activity Feed with Presence Indicators */}
                <ActivityFeed maxItems={8} showRefresh />

                {/* Quick Actions */}
                <div className="bg-white rounded-xl border border-gray-200 p-6">
                  <h3 className="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <Target className="w-5 h-5 text-purple-500" />
                    Quick Actions
                  </h3>
                  <div className="grid grid-cols-2 gap-4">
                    <ActionButton href="/discover" label="Discover Matches" icon="🔍" color="purple" />
                    <ActionButton href="/messages" label="View Messages" icon="💬" color="blue" />
                    <ActionButton href="/profile/edit" label="Edit Profile" icon="✏️" color="green" />
                    <ActionButton href="/settings" label="Settings" icon="⚙️" color="gray" />
                  </div>
                </div>
              </div>

              {/* Sidebar - 1 column */}
              <div className="space-y-6">
                {/* Profile Completeness Widget */}
                <ProfileCompletenessWidget />

                {/* Who's Nearby */}
                <ProximityPresenceCompact nearbyUsers={[]} />

                {/* Achievements */}
                <div className="bg-white rounded-xl border border-gray-200 p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <Award className="w-5 h-5 text-yellow-500" />
                    Achievements
                  </h3>
                  <div className="space-y-3">
                    <Achievement
                      title="Profile Complete"
                      description="Filled out all profile sections"
                      unlocked={stats?.profile_views ? stats.profile_views > 0 : false}
                    />
                    <Achievement
                      title="First Match"
                      description="Received your first match"
                      unlocked={(stats?.total_matches || 0) > 0}
                    />
                    <Achievement
                      title="Conversationalist"
                      description="Had 5 conversations"
                      unlocked={(stats?.conversations || 0) >= 5}
                    />
                    <Achievement
                      title="Popular"
                      description="Got 50+ profile views"
                      unlocked={(stats?.profile_views || 0) >= 50}
                    />
                  </div>
                </div>

                {/* Account Info */}
                <div className="bg-gradient-to-br from-purple-50 to-blue-50 rounded-xl border border-purple-200 p-6">
                  <div className="flex items-center gap-2 mb-3">
                    <Clock className="w-5 h-5 text-purple-600" />
                    <span className="text-sm font-medium text-purple-900">Account Status</span>
                  </div>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Member since:</span>
                      <span className="font-medium text-gray-900">{stats?.days_active || 0} days</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Last active:</span>
                      <span className="font-medium text-gray-900">
                        {stats?.last_login ? new Date(stats.last_login).toLocaleDateString() : 'Today'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Legacy Feature Cards - Collapsed */}
            <div className="mt-8">
              <button
                onClick={() => {
                  const el = document.getElementById('legacy-cards');
                  if (el) el.classList.toggle('hidden');
                }}
                className="text-sm text-gray-600 hover:text-gray-900 mb-4"
              >
                ▼ Show all features
              </button>
              <div id="legacy-cards" className="hidden">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                  {/* Profile Card */}
                  <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Profile</h3>
                    <p className="text-gray-600 mb-4">
                      {user?.profile?.bio 
                        ? 'Profile set up'
                        : 'Complete your profile to get started'
                      }
                    </p>
                    <Link
                      href="/profile"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-600 bg-blue-100 hover:bg-blue-200"
                    >
                      Manage Profile
                    </Link>
                  </div>

                  {/* Matches Card */}
                  <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Matches</h3>
                    <p className="text-gray-600 mb-4">
                      Discover and connect with compatible people
                    </p>
                    <Link
                      href="/matches"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-600 bg-green-100 hover:bg-green-200"
                    >
                      View Matches
                    </Link>
                  </div>

                  {/* Messages Card */}
                  <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Messages</h3>
                    <p className="text-gray-600 mb-4">
                      Chat with your matches
                    </p>
                    <Link
                      href="/messages"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-purple-600 bg-purple-100 hover:bg-purple-200"
                    >
                      View Messages
                    </Link>
                  </div>

                  {/* Bulletin Boards Card */}
                  <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">📍 Bulletin Boards</h3>
                    <p className="text-gray-600 mb-4">
                      Connect with your local community
                    </p>
                    <Link
                      href="/bulletin-boards"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-orange-600 bg-orange-100 hover:bg-orange-200"
                    >
                      Local Boards
                    </Link>
                  </div>

                  {/* AI Recommendations Card */}
                  <div className="bg-gradient-to-br from-purple-50 to-blue-50 p-6 rounded-lg shadow border border-purple-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">🤖 AI Recommendations</h3>
                    <p className="text-gray-600 mb-4">
                      Get personalized recommendations powered by AI
                    </p>
                    <Link
                      href="/recommendations"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700"
                    >
                      View Recommendations
                    </Link>
                  </div>

                  {/* WebSocket Real-time Card */}
                  <div className="bg-gradient-to-br from-green-50 to-teal-50 p-6 rounded-lg shadow border border-green-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">⚡ Real-time Communication</h3>
                    <p className="text-gray-600 mb-4">
                      WebSocket-powered real-time chat and notifications
                    </p>
                    <Link
                      href="/websocket"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700"
                    >
                      Test WebSocket
                    </Link>
                  </div>

                  {/* AI Content Generation Card */}
                  <div className="bg-gradient-to-br from-indigo-50 to-purple-50 p-6 rounded-lg shadow border border-indigo-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">🎨 AI Content Generation</h3>
                    <p className="text-gray-600 mb-4">
                      Create engaging content with AI-powered assistance
                    </p>
                    <Link
                      href="/content-generation"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700"
                    >
                      Generate Content
                    </Link>
                  </div>

                  {/* Chatrooms Card */}
                  <div className="bg-gradient-to-br from-pink-50 to-rose-50 p-6 rounded-lg shadow border border-pink-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">💬 Real-time Chatrooms</h3>
                    <p className="text-gray-600 mb-4">
                      Join location-based chatrooms and connect with people in your area
                    </p>
                    <Link
                      href="/chatrooms"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700"
                    >
                      Join Chatrooms
                    </Link>
                  </div>

                  {/* Proximity Chatrooms Card */}
                  <div className="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 rounded-lg shadow border border-blue-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">🌐 Proximity Chatrooms</h3>
                    <p className="text-gray-600 mb-4">
                      Connect with people nearby for networking, social interaction, and professional opportunities
                    </p>
                    <Link
                      href="/proximity-chatrooms"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700"
                    >
                      Find Nearby Chatrooms
                    </Link>
                  </div>
                </div>

                {/* User Info */}
                <div className="mt-8 bg-white p-6 rounded-lg shadow">
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="font-medium text-gray-700">Email:</span>
                      <span className="ml-2 text-gray-600">{user?.email}</span>
                    </div>
                    <div>
                      <span className="font-medium text-gray-700">Member since:</span>
                      <span className="ml-2 text-gray-600">
                        {user?.createdAt ? new Date(user.createdAt).toLocaleDateString() : 'N/A'}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium text-gray-700">Email verified:</span>
                      <span className="ml-2 text-gray-600">
                        {user?.emailVerifiedAt ? 'Yes' : 'No'}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium text-gray-700">Last updated:</span>
                      <span className="ml-2 text-gray-600">
                        {user?.updatedAt ? new Date(user.updatedAt).toLocaleString() : 'N/A'}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Quick Actions */}
                <div className="mt-8">
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                  <div className="flex flex-wrap justify-center gap-4">
                    <Link
                      href="/test-auth"
                      className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                      API Test Page
                    </Link>
                    <Link
                      href="/profile/edit"
                      className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                      Edit Profile
                    </Link>
                    <Link
                      href="/settings"
                      className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                      Settings
                    </Link>
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

// Helper Components
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
    purple: 'bg-purple-100 text-purple-600',
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    orange: 'bg-orange-100 text-orange-600',
    yellow: 'bg-yellow-100 text-yellow-600',
  };

  return (
    <Link href={link} className="block group">
      <div className="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-all hover:border-purple-300">
        <div className={`w-12 h-12 rounded-lg ${colorClasses[color]} flex items-center justify-center mb-4`}>
          {icon}
        </div>
        <div className="text-3xl font-bold text-gray-900 mb-1">{value}</div>
        <div className="text-sm font-medium text-gray-700 mb-1">{label}</div>
        <div className="text-xs text-gray-500">{subtext}</div>
      </div>
    </Link>
  );
}

function ActionButton({ href, label, icon, color }: { href: string; label: string; icon: string; color: string }) {
  const colorClasses = {
    purple: 'hover:bg-purple-50 hover:border-purple-300',
    blue: 'hover:bg-blue-50 hover:border-blue-300',
    green: 'hover:bg-green-50 hover:border-green-300',
    gray: 'hover:bg-gray-50 hover:border-gray-300',
  };

  return (
    <Link
      href={href}
      className={`flex items-center gap-3 p-4 border-2 border-gray-200 rounded-lg transition-all ${colorClasses[color as keyof typeof colorClasses]}`}
    >
      <span className="text-2xl">{icon}</span>
      <span className="text-sm font-medium text-gray-700">{label}</span>
    </Link>
  );
}

function Achievement({ title, description, unlocked }: { title: string; description: string; unlocked: boolean }) {
  return (
    <div className={`flex items-start gap-3 p-3 rounded-lg ${unlocked ? 'bg-yellow-50' : 'bg-gray-50'}`}>
      <div className={`text-2xl ${unlocked ? 'filter-none' : 'grayscale opacity-40'}`}>
        🏆
      </div>
      <div className="flex-1">
        <p className={`text-sm font-medium ${unlocked ? 'text-gray-900' : 'text-gray-500'}`}>{title}</p>
        <p className="text-xs text-gray-600">{description}</p>
      </div>
      {unlocked && <span className="text-xs text-yellow-600 font-medium">✓</span>}
    </div>
  );
}