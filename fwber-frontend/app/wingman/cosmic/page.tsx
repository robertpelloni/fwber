'use client'

import { useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { apiClient } from '@/lib/api/client'
import { 
  Star, ArrowLeft, RefreshCw, Share2, 
  Heart, HeartCrack, Sparkles, Moon
} from 'lucide-react'
import Link from 'next/link'

interface CosmicMatchResponse {
  best_match: string
  best_reason: string
  worst_match: string
  worst_reason: string
  share_id: string
}

const zodiacEmojis: Record<string, string> = {
  aries: '♈',
  taurus: '♉',
  gemini: '♊',
  cancer: '♋',
  leo: '♌',
  virgo: '♍',
  libra: '♎',
  scorpio: '♏',
  sagittarius: '♐',
  capricorn: '♑',
  aquarius: '♒',
  pisces: '♓',
}

function getZodiacEmoji(sign: string): string {
  const normalized = sign.toLowerCase().trim()
  return zodiacEmojis[normalized] || '⭐'
}

export default function CosmicMatchPage() {
  const [result, setResult] = useState<CosmicMatchResponse | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [revealed, setRevealed] = useState(false)

  async function getCosmicMatch() {
    setLoading(true)
    setError(null)
    setRevealed(false)

    try {
      const response = await apiClient.get<CosmicMatchResponse>('/wingman/cosmic-match')
      setResult(response.data)
      setTimeout(() => setRevealed(true), 500)
    } catch (err) {
      console.error('Failed to get cosmic match:', err)
      setError('The stars are misaligned. Try again later.')
    } finally {
      setLoading(false)
    }
  }

  async function handleShare() {
    if (!result) return

    const shareText = `My Cosmic Match: Best with ${result.best_match} ${getZodiacEmoji(result.best_match)}, Avoid ${result.worst_match} ${getZodiacEmoji(result.worst_match)} - Get yours at fwber!`
    
    if (navigator.share) {
      try {
        await navigator.share({
          title: 'My Cosmic Match',
          text: shareText,
        })
      } catch {
        await navigator.clipboard.writeText(shareText)
      }
    } else {
      await navigator.clipboard.writeText(shareText)
      alert('Result copied to clipboard!')
    }
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gradient-to-b from-violet-950 via-indigo-950 to-black">
        <AppHeader />
        
        <main className="max-w-2xl mx-auto px-4 py-6">
          <div className="flex items-center gap-3 mb-8">
            <Link 
              href="/home" 
              className="p-2 -ml-2 hover:bg-purple-800/30 rounded-lg transition"
            >
              <ArrowLeft className="w-5 h-5 text-gray-400" />
            </Link>
            <div>
              <h1 className="text-2xl font-bold text-white flex items-center gap-2">
                <Star className="w-7 h-7 text-yellow-400" />
                Cosmic Match
              </h1>
              <p className="text-sm text-gray-400">
                Discover your best and worst zodiac matches
              </p>
            </div>
          </div>

          <div className="relative">
            <div className="absolute inset-0 bg-gradient-to-r from-violet-500/20 via-indigo-500/20 to-purple-500/20 rounded-3xl blur-xl" />
            
            <div className="relative bg-gradient-to-br from-violet-900/80 via-indigo-900/80 to-slate-900/80 rounded-3xl border border-violet-500/30 overflow-hidden">
              <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
                {[...Array(30)].map((_, i) => (
                  <div 
                    key={i}
                    className="absolute bg-white rounded-full animate-pulse"
                    style={{
                      width: `${Math.random() * 3 + 1}px`,
                      height: `${Math.random() * 3 + 1}px`,
                      top: `${Math.random() * 100}%`,
                      left: `${Math.random() * 100}%`,
                      opacity: Math.random() * 0.5 + 0.2,
                      animationDelay: `${Math.random() * 3}s`,
                      animationDuration: `${Math.random() * 2 + 2}s`,
                    }}
                  />
                ))}
              </div>

              <div className="relative p-8 text-center">
                <div className="mb-6">
                  <div className="w-24 h-24 mx-auto bg-gradient-to-br from-violet-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-violet-500/30">
                    <Moon className="w-12 h-12 text-white" />
                  </div>
                </div>

                {!result && !loading && (
                  <div className="space-y-4">
                    <h2 className="text-xl font-semibold text-white">
                      What do the stars say about your love life?
                    </h2>
                    <p className="text-gray-400 max-w-md mx-auto">
                      Based on your profile, we&apos;ll reveal your most compatible 
                      and least compatible zodiac signs.
                    </p>
                  </div>
                )}

                {loading && (
                  <div className="space-y-4 py-4">
                    <div className="flex justify-center gap-2">
                      {['♈', '♉', '♊', '♋', '♌', '♍'].map((emoji, i) => (
                        <span 
                          key={i}
                          className="text-2xl animate-bounce"
                          style={{ animationDelay: `${i * 0.1}s` }}
                        >
                          {emoji}
                        </span>
                      ))}
                    </div>
                    <p className="text-violet-300 animate-pulse">
                      Consulting the celestial bodies...
                    </p>
                  </div>
                )}

                {result && !loading && (
                  <div className={`space-y-6 transition-all duration-700 ${revealed ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'}`}>
                    <div className="grid md:grid-cols-2 gap-4">
                      <div className="bg-gradient-to-br from-green-500/20 to-emerald-500/10 border border-green-500/30 rounded-2xl p-5">
                        <div className="flex items-center justify-center gap-2 mb-3">
                          <Heart className="w-6 h-6 text-green-400" />
                          <span className="text-green-300 font-semibold">Best Match</span>
                        </div>
                        <div className="text-4xl mb-2">{getZodiacEmoji(result.best_match)}</div>
                        <h3 className="text-xl font-bold text-white mb-2">{result.best_match}</h3>
                        <p className="text-sm text-gray-300">{result.best_reason}</p>
                      </div>

                      <div className="bg-gradient-to-br from-red-500/20 to-rose-500/10 border border-red-500/30 rounded-2xl p-5">
                        <div className="flex items-center justify-center gap-2 mb-3">
                          <HeartCrack className="w-6 h-6 text-red-400" />
                          <span className="text-red-300 font-semibold">Worst Match</span>
                        </div>
                        <div className="text-4xl mb-2">{getZodiacEmoji(result.worst_match)}</div>
                        <h3 className="text-xl font-bold text-white mb-2">{result.worst_match}</h3>
                        <p className="text-sm text-gray-300">{result.worst_reason}</p>
                      </div>
                    </div>
                  </div>
                )}

                {error && (
                  <div className="bg-red-500/10 border border-red-500/30 rounded-xl p-4 text-red-300">
                    {error}
                  </div>
                )}

                <div className="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                  <button
                    onClick={getCosmicMatch}
                    disabled={loading}
                    className="flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-violet-500 to-indigo-600 text-white font-semibold rounded-xl hover:from-violet-600 hover:to-indigo-700 transition disabled:opacity-50 shadow-lg shadow-violet-500/30"
                  >
                    {loading ? (
                      <RefreshCw className="w-5 h-5 animate-spin" />
                    ) : (
                      <Star className="w-5 h-5" />
                    )}
                    {result ? 'Check Again' : 'Reveal My Cosmic Match'}
                  </button>

                  {result && (
                    <button
                      onClick={handleShare}
                      className="flex items-center justify-center gap-2 px-6 py-3 bg-slate-700/50 text-white font-semibold rounded-xl hover:bg-slate-700 transition border border-slate-600"
                    >
                      <Share2 className="w-5 h-5" />
                      Share
                    </button>
                  )}
                </div>
              </div>
            </div>
          </div>

          <div className="mt-8 grid grid-cols-2 gap-4">
            <Link
              href="/wingman/fortune"
              className="p-4 bg-slate-800/50 rounded-xl border border-purple-500/20 hover:border-purple-500/40 transition text-center"
            >
              <Sparkles className="w-8 h-8 mx-auto text-yellow-400 mb-2" />
              <span className="text-sm text-gray-300">Fortune</span>
            </Link>
            <Link
              href="/wingman/nemesis"
              className="p-4 bg-slate-800/50 rounded-xl border border-purple-500/20 hover:border-purple-500/40 transition text-center"
            >
              <HeartCrack className="w-8 h-8 mx-auto text-red-400 mb-2" />
              <span className="text-sm text-gray-300">Find Nemesis</span>
            </Link>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}
