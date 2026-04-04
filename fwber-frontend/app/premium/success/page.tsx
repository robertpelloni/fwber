'use client'

import Link from 'next/link'
import { CheckCircle2, Crown } from 'lucide-react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { Button } from '@/components/ui/button'

export default function PremiumSuccessPage() {
  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Gold Activated" />
        <main className="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6 lg:px-8">
          <div className="rounded-3xl border border-green-200 bg-white p-10 shadow-sm dark:border-green-900/40 dark:bg-gray-900">
            <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
              <CheckCircle2 className="h-8 w-8 text-green-600 dark:text-green-400" />
            </div>
            <h1 className="text-3xl font-black text-gray-900 dark:text-white">Premium purchase received</h1>
            <p className="mt-4 text-gray-600 dark:text-gray-300">
              Your Gold upgrade was confirmed. If billing details are still propagating, the restored subscription history page will reflect them shortly.
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
              <Link href="/who-likes-you">
                <Button className="w-full bg-gradient-to-r from-yellow-500 to-yellow-700 text-white hover:from-yellow-600 hover:to-yellow-800 sm:w-auto">
                  <Crown className="mr-2 h-4 w-4" />
                  Open who likes you
                </Button>
              </Link>
              <Link href="/settings/subscription">
                <Button variant="outline" className="w-full sm:w-auto">View billing history</Button>
              </Link>
            </div>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}
