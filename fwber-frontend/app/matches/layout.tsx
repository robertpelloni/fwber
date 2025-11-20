import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Discover Matches | FWBer',
  description: 'Find people near you.',
}

export default function MatchesLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return <>{children}</>
}
