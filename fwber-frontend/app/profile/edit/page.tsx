import ProtectedRoute from '@/components/ProtectedRoute'
import ProfilePageClient from '@/components/profile/ProfilePageClient'
import AppHeader from '@/components/AppHeader'

export const dynamic = 'force-dynamic'

export default function ProfileEditPage() {
  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Edit Profile" />
        <ProfilePageClient />
      </div>
    </ProtectedRoute>
  )
}
