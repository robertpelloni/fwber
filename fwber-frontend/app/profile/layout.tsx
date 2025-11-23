import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'My Profile | FWBer',
  description: 'Manage your profile and photos.',
}

export default function ProfileLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return <>{children}</>
}
