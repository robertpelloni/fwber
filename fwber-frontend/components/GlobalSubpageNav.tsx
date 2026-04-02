'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import { ArrowLeft } from 'lucide-react'

import { useAuth } from '@/lib/auth-context'
import { Logo } from '@/components/Logo'

function shouldHideGlobalSubpageNav(pathname: string): boolean {
  return pathname === '/'
    || pathname === '/dashboard'
    || pathname.startsWith('/dashboard/')
    || pathname.startsWith('/api/')
}

export default function GlobalSubpageNav() {
  const pathname = usePathname()
  const router = useRouter()
  const { user } = useAuth()
  const [hasLocalHeader, setHasLocalHeader] = useState(false)
  const [headerCheckComplete, setHeaderCheckComplete] = useState(false)

  useEffect(() => {
    const updateHeaderPresence = () => {
      setHasLocalHeader(Boolean(document.querySelector('[data-app-header="true"]')))
      setHeaderCheckComplete(true)
    }

    updateHeaderPresence()

    const frameId = window.requestAnimationFrame(updateHeaderPresence)
    const timeoutId = window.setTimeout(updateHeaderPresence, 250)
    const observer = new MutationObserver(updateHeaderPresence)

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['data-app-header'],
    })

    return () => {
      window.cancelAnimationFrame(frameId)
      window.clearTimeout(timeoutId)
      observer.disconnect()
    }
  }, [pathname])

  if (!headerCheckComplete || shouldHideGlobalSubpageNav(pathname) || hasLocalHeader) {
    return null
  }

  const homeHref = user ? '/dashboard' : '/'

  const handleBack = () => {
    if (window.history.length > 1) {
      router.back()
      return
    }

    router.push(homeHref)
  }

  return (
    <div className="fixed left-4 top-4 z-[60] flex items-center gap-2 rounded-full border border-white/60 bg-white/90 px-2 py-2 shadow-lg backdrop-blur dark:border-gray-700/70 dark:bg-gray-900/90">
      <button
        type="button"
        onClick={handleBack}
        className="rounded-full p-2 text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
        aria-label="Go back"
        title="Go back"
      >
        <ArrowLeft className="h-5 w-5" />
      </button>
      <Link href={homeHref} className="flex items-center transition-opacity hover:opacity-90" aria-label="Go home">
        <Logo className="text-2xl" />
      </Link>
    </div>
  )
}
