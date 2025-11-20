import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Messages | FWBer',
  description: 'Chat with your matches.',
}

export default function MessagesLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return <>{children}</>
}
