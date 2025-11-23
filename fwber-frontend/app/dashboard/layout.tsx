import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Dashboard | FWBer',
  description: 'View your matches, messages, and activity.',
}

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return <>{children}</>
}
