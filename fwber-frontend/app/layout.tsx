import type { Metadata, Viewport } from 'next'
import { Inter } from 'next/font/google'
import './globals.css'
import { AuthProvider } from '@/lib/auth-context'
import QueryProvider from '@/lib/query-client'
import PerformanceMonitor from '@/components/PerformanceMonitor'
import { ToastProvider } from '@/components/ToastProvider'
import NotificationPermissionHandler from '@/components/NotificationPermissionHandler'
import SentryInitializer from '@/components/SentryInitializer'
import { ThemeProvider } from '@/components/ThemeProvider'
import FeedbackModal from '@/components/FeedbackModal'
import { MercureProvider } from '@/lib/contexts/MercureContext'
import { SolanaProvider } from '@/components/SolanaProvider'

const inter = Inter({
  variable: '--font-inter',
  subsets: ['latin'],
  display: 'swap', // Optimize font loading
})

export const viewport: Viewport = {
  width: 'device-width',
  initialScale: 1,
  maximumScale: 5,
  themeColor: '#f97316',
}

export const metadata: Metadata = {
  title: 'FWBer.me - Adult Social Network',
  description: 'Adult Social Network - Friends, Dating, Hookups, Ads, Groups, Fun, Love, Lust, and More!',
  keywords: ['dating', 'adult', 'matching', 'relationships', 'hookups', 'friends', 'groups', 'ads'],
  authors: [{ name: 'FWBer.me Team' }],
  manifest: '/manifest.json',
  icons: {
    icon: [
      { url: '/favicon.svg', type: 'image/svg+xml' },
      { url: '/icon.svg', type: 'image/svg+xml', sizes: 'any' },
    ],
    apple: [
      { url: '/icon.svg', type: 'image/svg+xml' },
    ],
  },
  openGraph: {
    title: 'FWBer.me - Adult Dating Platform',
    description: 'A modern adult dating platform with advanced matching algorithms',
    type: 'website',
    locale: 'en_US',
    siteName: 'FWBer.me',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'FWBer.me - Adult Dating Platform',
    description: 'A modern adult dating platform with advanced matching algorithms',
  },
  alternates: {
    types: {
      'application/json+oembed': 'https://fwber.me/api/oembed',
    },
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
        {/* <link rel="preload" href="/_next/static/css/app/layout.css" as="style" /> */}
      </head>

      <body
        className="min-h-screen bg-background font-sans antialiased text-optimized"
        suppressHydrationWarning
      >
        <ThemeProvider
          attribute="class"
          defaultTheme="system"
          enableSystem
          disableTransitionOnChange
        >
          <QueryProvider>
            <AuthProvider>
              <MercureProvider>
                <SolanaProvider>
                  <NotificationPermissionHandler />
                  <SentryInitializer />
                  <ToastProvider>
                    <div className="relative flex min-h-screen flex-col">
                      <div className="flex-1">{children}</div>
                    </div>
                    <FeedbackModal />
                    <PerformanceMonitor />
                  </ToastProvider>
                </SolanaProvider>
              </MercureProvider>
            </AuthProvider>
          </QueryProvider>
        </ThemeProvider>
      </body>
    </html>
  )
}
