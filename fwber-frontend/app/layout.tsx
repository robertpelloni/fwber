import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import './globals.css'
import { AuthProvider } from '@/lib/auth-context'
import QueryProvider from '@/lib/query-client'
import PerformanceMonitor from '@/components/PerformanceMonitor'

const inter = Inter({
  variable: '--font-inter',
  subsets: ['latin'],
  display: 'swap', // Optimize font loading
})

export const metadata: Metadata = {
  title: 'FWBer.me - Adult Dating Platform',
  description: 'A modern adult dating platform with advanced matching algorithms',
  keywords: ['dating', 'adult', 'matching', 'relationships'],
  authors: [{ name: 'FWBer.me Team' }],
  openGraph: {
    title: 'FWBer.me - Adult Dating Platform',
    description: 'A modern adult dating platform with advanced matching algorithms',
    type: 'website',
    locale: 'en_US',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'FWBer.me - Adult Dating Platform',
    description: 'A modern adult dating platform with advanced matching algorithms',
  },
  robots: {
    index: true,
    follow: true,
  },
  // Performance optimizations
  other: {
    'X-DNS-Prefetch-Control': 'on',
  },
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en" className={inter.variable}>
      <head>
        {/* DNS prefetch for better performance */}
        <link rel="dns-prefetch" href="//localhost" />
        <link rel="preconnect" href="http://localhost:8001" />
        
        {/* Preload critical resources */}
        <link rel="preload" href="/_next/static/css/app/layout.css" as="style" />
      </head>
      <body className="min-h-screen bg-background font-sans antialiased text-optimized">
        <QueryProvider>
          <AuthProvider>
            <div className="relative flex min-h-screen flex-col">
              <div className="flex-1">{children}</div>
            </div>
            <PerformanceMonitor />
          </AuthProvider>
        </QueryProvider>
      </body>
    </html>
  )
}
