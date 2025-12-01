'use client';

import Link from 'next/link';
import ProtectedRoute from '@/components/ProtectedRoute';
import { useFeatureFlag } from '@/lib/hooks/use-feature-flags';
import { useAuth } from '@/lib/auth-context';
import {
  Settings,
  Shield,
  Lock,
  Bell,
  MapPin,
  User,
  Eye,
  Palette,
  HelpCircle,
  ChevronRight,
  LogOut,
  CreditCard,
} from 'lucide-react';

interface SettingsLinkProps {
  href: string;
  icon: React.ReactNode;
  title: string;
  description: string;
  badge?: string;
  disabled?: boolean;
}

function SettingsLink({ href, icon, title, description, badge, disabled }: SettingsLinkProps) {
  if (disabled) {
    return (
      <div className="flex items-center gap-4 p-4 bg-gray-50 rounded-lg opacity-50 cursor-not-allowed">
        <div className="flex-shrink-0 w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400">
          {icon}
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <h3 className="text-sm font-medium text-gray-500">{title}</h3>
            {badge && (
              <span className="px-2 py-0.5 text-xs font-medium bg-gray-200 text-gray-500 rounded-full">
                {badge}
              </span>
            )}
          </div>
          <p className="text-sm text-gray-400">{description}</p>
        </div>
      </div>
    );
  }

  return (
    <Link
      href={href}
      className="flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200 hover:border-purple-300 hover:shadow-sm transition-all group"
    >
      <div className="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 group-hover:bg-purple-200 transition-colors">
        {icon}
      </div>
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <h3 className="text-sm font-medium text-gray-900">{title}</h3>
          {badge && (
            <span className="px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 rounded-full">
              {badge}
            </span>
          )}
        </div>
        <p className="text-sm text-gray-500">{description}</p>
      </div>
      <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-purple-600 transition-colors" />
    </Link>
  );
}

export default function SettingsPage() {
  const { user, logout } = useAuth();
  const { isEnabled: vaultEnabled } = useFeatureFlag('local_media_vault');
  const { isEnabled: faceRevealEnabled } = useFeatureFlag('face_reveal');

  const handleLogout = async () => {
    await logout();
    window.location.href = '/login';
  };

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <header className="bg-white shadow-sm">
          <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div className="flex items-center gap-3">
              <Settings className="h-8 w-8 text-gray-600" />
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
                <p className="text-sm text-gray-500">Manage your account and preferences</p>
              </div>
            </div>
          </div>
        </header>

        <main className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Account Section */}
          <section className="mb-8">
            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
              Account
            </h2>
            <div className="space-y-3">
              <SettingsLink
                href="/profile"
                icon={<User className="w-5 h-5" />}
                title="Profile"
                description="Edit your profile information and photos"
              />
              <SettingsLink
                href="/location-settings"
                icon={<MapPin className="w-5 h-5" />}
                title="Location Settings"
                description="Manage your location privacy and visibility"
              />
            </div>
          </section>

          {/* Subscription Section */}
          <section className="mb-8">
            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
              Subscription & Billing
            </h2>
            <div className="space-y-3">
              <SettingsLink
                href="/settings/subscription"
                icon={<CreditCard className="w-5 h-5" />}
                title="Manage Subscription"
                description="View your plan, billing history, and upgrade options"
              />
            </div>
          </section>

          {/* Privacy & Security Section */}
          <section className="mb-8">
            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
              Privacy & Security
            </h2>
            <div className="space-y-3">
              <SettingsLink
                href="/settings/vault"
                icon={<Lock className="w-5 h-5" />}
                title="Local Media Vault"
                description="Securely store sensitive photos with encryption"
                badge={vaultEnabled ? 'Enabled' : 'Disabled'}
                disabled={!vaultEnabled}
              />
              <SettingsLink
                href="/photos"
                icon={<Eye className="w-5 h-5" />}
                title="Photo Privacy"
                description={faceRevealEnabled ? 'Manage face reveal and photo visibility' : 'Manage your photo visibility settings'}
                badge={faceRevealEnabled ? 'Face Reveal' : undefined}
              />
              <SettingsLink
                href="/friends"
                icon={<Shield className="w-5 h-5" />}
                title="Blocked Users"
                description="Manage blocked users and privacy settings"
              />
            </div>
          </section>

          {/* Notifications Section */}
          <section className="mb-8">
            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
              Notifications
            </h2>
            <div className="space-y-3">
              <SettingsLink
                href="/notifications"
                icon={<Bell className="w-5 h-5" />}
                title="Notification Preferences"
                description="Choose what notifications you receive"
              />
            </div>
          </section>

          {/* Appearance Section */}
          <section className="mb-8">
            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
              Appearance
            </h2>
            <div className="space-y-3">
              <div className="flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200">
                <div className="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                  <Palette className="w-5 h-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <h3 className="text-sm font-medium text-gray-900">Theme</h3>
                  <p className="text-sm text-gray-500">Choose your preferred color scheme</p>
                </div>
                <select 
                  aria-label="Theme selection"
                  className="text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                  <option value="system">System</option>
                  <option value="light">Light</option>
                  <option value="dark">Dark</option>
                </select>
              </div>
            </div>
          </section>

          {/* Support Section */}
          <section className="mb-8">
            <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
              Support
            </h2>
            <div className="space-y-3">
              <SettingsLink
                href="/help"
                icon={<HelpCircle className="w-5 h-5" />}
                title="Help & Support"
                description="Get help with your account"
              />
            </div>
          </section>

          {/* Account Info */}
          <section className="mb-8">
            <div className="bg-white rounded-lg border border-gray-200 p-6">
              <h3 className="text-sm font-semibold text-gray-900 mb-4">Account Information</h3>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-500">Email</span>
                  <span className="text-gray-900">{user?.email || 'Not set'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-500">Member since</span>
                  <span className="text-gray-900">
                    {user?.createdAt ? new Date(user.createdAt).toLocaleDateString() : 'Unknown'}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-500">Email verified</span>
                  <span className={user?.emailVerifiedAt ? 'text-green-600' : 'text-yellow-600'}>
                    {user?.emailVerifiedAt ? 'Yes' : 'No'}
                  </span>
                </div>
              </div>
            </div>
          </section>

          {/* Logout Button */}
          <section>
            <button
              onClick={handleLogout}
              className="w-full flex items-center justify-center gap-2 p-4 bg-red-50 text-red-600 rounded-lg border border-red-200 hover:bg-red-100 transition-colors"
            >
              <LogOut className="w-5 h-5" />
              <span className="font-medium">Log Out</span>
            </button>
          </section>
        </main>
      </div>
    </ProtectedRoute>
  );
}
