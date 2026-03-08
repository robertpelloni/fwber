'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { useAuth } from '@/lib/auth-context'
import { Logo } from '@/components/Logo'
import { ConnectionStatusBadge } from './realtime/PresenceComponents'
import { NotificationBell } from './NotificationBell'
import { ThemeToggle } from '@/components/ThemeToggle'
import {
    type LucideIcon,
    Menu,
    X,
    Home,
    Heart,
    MessageSquare,
    Users,
    MapPin,
    User,
    Settings,
    LogOut,
    CreditCard,
    Calendar,
    Radio,
    Zap,
    Wallet,
    Trophy,
    Sparkles,
    HelpCircle,
    Tag,
    Target,
    Layout,
    Mic,
    Briefcase,
    CalendarHeart,
    Shield,
    Gamepad2
} from 'lucide-react'

interface AppHeaderProps {
    title?: string
    showNav?: boolean
}

interface NavItem {
    href: string
    label: string
    icon: LucideIcon
}

const navLinks: NavItem[] = [
    { href: '/dashboard', label: 'Dashboard', icon: Home },
    { href: '/recommendations', label: 'For You', icon: Sparkles },
    { href: '/matches', label: 'Matches', icon: Heart },
    { href: '/messages', label: 'Messages', icon: MessageSquare },
    { href: '/events', label: 'Events', icon: Calendar },
    { href: '/groups', label: 'Groups', icon: Users },
    { href: '/proximity-chatrooms', label: 'Local Chat', icon: Radio },
    { href: '/conference-pulse', label: 'Conference', icon: Briefcase },
    { href: '/date-planner', label: 'Date Planner', icon: CalendarHeart },
    { href: '/safety', label: 'Safety', icon: Shield },
    { href: '/audio-rooms', label: 'Audio Rooms', icon: Mic },
    { href: '/burner', label: 'Burner Bridge', icon: Zap },
    { href: '/bulletin-boards', label: 'Boards', icon: Layout },
    { href: '/deals', label: 'Deals', icon: Tag },
    { href: '/bounties', label: 'Bounties', icon: Target },
    { href: '/nearby', label: 'Nearby', icon: MapPin },
    { href: '/wingman', label: 'Arcade', icon: Gamepad2 },
    { href: '/wallet', label: 'Wallet', icon: Wallet },
    { href: '/leaderboard', label: 'Leaderboard', icon: Trophy },
]

const accountLinks: NavItem[] = [
    { href: '/settings', label: 'Settings', icon: Settings },
    { href: '/settings/account', label: 'Account', icon: User },
    { href: '/settings/subscription', label: 'Billing', icon: CreditCard },
]

function isActivePath(pathname: string, href: string) {
    if (href === '/dashboard') {
        return pathname === href
    }

    return pathname === href || pathname.startsWith(`${href}/`)
}

function getNavLinkClasses(isActive: boolean, compact = false) {
    return [
        'group flex items-center rounded-xl font-medium transition-colors',
        compact ? 'gap-2 px-3 py-2 text-sm' : 'gap-3 px-4 py-3 text-sm',
        isActive
            ? 'bg-gradient-to-r from-pink-500 to-orange-500 text-white shadow-sm'
            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
    ].join(' ')
}

function getUtilityLinkClasses(isActive: boolean) {
    return [
        'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
        isActive
            ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
    ].join(' ')
}

export default function AppHeader({ title = 'FWBer', showNav = true }: AppHeaderProps) {
    const { user, logout } = useAuth()
    const pathname = usePathname()
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false)

    useEffect(() => {
        const bodyClass = 'app-shell-sidebar'

        if (showNav) {
            document.body.classList.add(bodyClass)
        } else {
            document.body.classList.remove(bodyClass)
        }

        return () => {
            document.body.classList.remove(bodyClass)
        }
    }, [showNav])

    const userDisplayName = user?.name || user?.email || 'User'
    const userInitial = userDisplayName.charAt(0).toUpperCase()

    const handleLogout = () => {
        logout()
    }

    return (
        <>
            <header className={`sticky top-0 z-40 bg-white shadow dark:bg-gray-900 ${showNav ? 'app-header-with-sidebar' : ''}`}>
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex min-w-0 items-center gap-3">
                        <Link href="/dashboard" className="flex items-center gap-2 transition-opacity hover:opacity-90" aria-label="Go to dashboard">
                            <Logo className="text-3xl" />
                        </Link>
                        <div className="min-w-0">
                            <div className="flex items-center gap-3">
                                <ConnectionStatusBadge />
                                {title && title !== 'FWBer' && (
                                    <span className="hidden truncate text-sm font-medium text-gray-500 dark:text-gray-400 sm:inline">
                                        {title}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 lg:gap-3">
                        <ThemeToggle />
                        <NotificationBell />

                        <div className="hidden items-center gap-2 lg:flex">
                            {accountLinks.map((link) => {
                                const active = isActivePath(pathname, link.href)

                                return (
                                    <Link
                                        key={link.href}
                                        href={link.href}
                                        className={getUtilityLinkClasses(active)}
                                    >
                                        <link.icon className="h-4 w-4" />
                                        <span>{link.label}</span>
                                    </Link>
                                )
                            })}

                            <button
                                onClick={handleLogout}
                                className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:hover:bg-red-900/20"
                                aria-label="Log out"
                            >
                                <LogOut className="h-4 w-4" />
                                <span>Log out</span>
                            </button>
                        </div>

                        <div className="hidden items-center gap-2 rounded-full border border-gray-200 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-900 sm:flex">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-orange-500 text-sm font-bold text-white">
                                {userInitial}
                            </div>
                            <span className="max-w-32 truncate text-sm font-medium text-gray-700 dark:text-gray-300">
                                {userDisplayName}
                            </span>
                        </div>

                        {showNav && (
                            <button
                                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                                className="rounded-lg p-2 text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 lg:hidden"
                                aria-label={mobileMenuOpen ? 'Close navigation menu' : 'Open navigation menu'}
                            >
                                {mobileMenuOpen ? (
                                    <X className="h-6 w-6" />
                                ) : (
                                    <Menu className="h-6 w-6" />
                                )}
                            </button>
                        )}
                    </div>
                </div>

                {showNav && mobileMenuOpen && (
                    <div className="border-t border-gray-200 px-4 py-4 dark:border-gray-700 lg:hidden">
                        <nav className="space-y-1" aria-label="Mobile primary navigation">
                            {navLinks.map((link) => {
                                const active = isActivePath(pathname, link.href)

                                return (
                                    <Link
                                        key={link.href}
                                        href={link.href}
                                        onClick={() => setMobileMenuOpen(false)}
                                        className={getNavLinkClasses(active)}
                                    >
                                        <link.icon className="h-5 w-5" />
                                        <span>{link.label}</span>
                                    </Link>
                                )
                            })}
                        </nav>

                        <div className="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <div className="mb-3 flex items-center gap-3 px-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-orange-500 text-base font-bold text-white">
                                    {userInitial}
                                </div>
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        {userDisplayName}
                                    </p>
                                    <p className="truncate text-xs text-gray-500 dark:text-gray-400">
                                        {user?.email || 'Signed in'}
                                    </p>
                                </div>
                            </div>

                            <div className="space-y-1">
                                {accountLinks.map((link) => {
                                    const active = isActivePath(pathname, link.href)

                                    return (
                                        <Link
                                            key={link.href}
                                            href={link.href}
                                            onClick={() => setMobileMenuOpen(false)}
                                            className={getNavLinkClasses(active, true)}
                                        >
                                            <link.icon className="h-5 w-5" />
                                            <span>{link.label}</span>
                                        </Link>
                                    )
                                })}

                                <button
                                    onClick={() => {
                                        setMobileMenuOpen(false)
                                        handleLogout()
                                    }}
                                    className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:hover:bg-red-900/20"
                                >
                                    <LogOut className="h-5 w-5" />
                                    <span>Log out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </header>

            {showNav && (
                <aside className="fixed inset-y-16 left-0 z-30 hidden w-72 border-r border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95 lg:flex lg:flex-col">
                    <div className="flex-1 overflow-y-auto px-4 py-6">
                        <div className="mb-6 rounded-2xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-4 dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-orange-500 text-base font-bold text-white">
                                    {userInitial}
                                </div>
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        {userDisplayName}
                                    </p>
                                    <p className="truncate text-xs text-gray-500 dark:text-gray-400">
                                        {user?.email || 'Signed in'}
                                    </p>
                                </div>
                            </div>
                            <p className="text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Browse the main sections from the left rail while account controls stay in the upper-right corner.
                            </p>
                        </div>

                        <nav className="space-y-1" aria-label="Primary navigation">
                            {navLinks.map((link) => {
                                const active = isActivePath(pathname, link.href)

                                return (
                                    <Link
                                        key={link.href}
                                        href={link.href}
                                        className={getNavLinkClasses(active)}
                                    >
                                        <link.icon className="h-5 w-5" />
                                        <span>{link.label}</span>
                                    </Link>
                                )
                            })}
                        </nav>

                        <div className="mt-6 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950/60">
                            <Link
                                href="/help"
                                className="flex items-center gap-2 text-sm font-medium text-gray-700 transition-colors hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            >
                                <HelpCircle className="h-4 w-4" />
                                <span>Need help?</span>
                            </Link>
                        </div>
                    </div>
                </aside>
            )}
        </>
    )
}