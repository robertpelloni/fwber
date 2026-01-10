'use client'

import { useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { apiClient } from '@/lib/api/client'
import { 
  Skull, ArrowLeft, RefreshCw, Share2, 
  Sparkles, AlertTriangle, Zap, Brain
} from 'lucide-react'
import Link from 'next/link'

interface NemesisResponse {
  nemesis_type: string
  clashing_traits: string[]
  why_it_would_fail: string
  scientific_explanation: string
  share_id: string
}

export default function NemesisPage() {
  const [result, setResult] = useState<NemesisResponse | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [revealed, setRevealed] = useState(false)

  async function findNemesis() {
    setLoading(true)
    setError(null)
    setRevealed(false)

    try {
      const response = await apiClient.get<NemesisResponse>('/wingman/nemesis')
      setResult(response.data)
      setTimeout(() => setRevealed(true), 500)
    } catch (err) {
      console.error('Failed to find nemesis:', err)
      setError('The nemesis detector is offline. Try again later.')
    } finally {
      setLoading(false)
    }
  }

  async function handleShare() {
    if (!result) return

    const shareText = `My Dating Nemesis: "${result.nemesis_type}" - ${result.clashing_traits.join(', ')}. Get yours at fwber!`
    
    if (navigator.share) {
      try {
        await navigator.share({
          title: 'My Dating Nemesis',
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
      <div className="min-h-screen bg-gradient-to-b from-red-950 via-rose-950 to-black">
        <AppHeader />
        
        <main className="max-w-2xl mx-auto px-4 py-6">
          <div className="flex items-center gap-3 mb-8">
            <Link 
              href="/home" 
              className="p-2 -ml-2 hover:bg-red-800/30 rounded-lg transition"
            >
              <ArrowLeft className="w-5 h-5 text-gray-400" />
            </Link>
            <div>
              <h1 className="text-2xl font-bold text-white flex items-center gap-2">
                <Skull className="w-7 h-7 text-red-400" />
                Find Your Nemesis
              </h1>
              <p className="text-sm text-gray-400">
                Discover who you should absolutely avoid dating
              </p>
            </div>
          </div>

          <div className="relative">
            <div className="absolute inset-0 bg-gradient-to-r from-red-500/20 via-rose-500/20 to-red-500/20 rounded-3xl blur-xl" />
            
            <div className="relative bg-gradient-to-br from-red-900/80 via-rose-900/80 to-slate-900/80 rounded-3xl border border-red-500/30 overflow-hidden">
              <div className="relative p-8 text-center">
                <div className="mb-6">
                  <div className="w-24 h-24 mx-auto bg-gradient-to-br from-red-500 to-rose-600 rounded-full flex items-center justify-center shadow-lg shadow-red-500/30">
                    <Skull className="w-12 h-12 text-white" />
                  </div>
                </div>

                {!result && !loading && (
                  <div className="space-y-4">
                    <h2 className="text-xl font-semibold text-white">
                      Who is your dating arch-nemesis?
                    </h2>
                    <p className="text-gray-400 max-w-md mx-auto">
                      Using &quot;science&quot; and personality analysis, we&apos;ll identify the type of person 
                      guaranteed to drive you absolutely crazy (in a bad way).
                    </p>
                  </div>
                )}

                {loading && (
                  <div className="space-y-4 py-4">
                    <div className="flex justify-center gap-2">
                      <AlertTriangle className="w-8 h-8 text-red-400 animate-pulse" />
                      <Zap className="w-8 h-8 text-yellow-400 animate-pulse" style={{ animationDelay: '0.3s' }} />
                      <Brain className="w-8 h-8 text-purple-400 animate-pulse" style={{ animationDelay: '0.6s' }} />
                    </div>
                    <p className="text-red-300 animate-pulse">
                      Analyzing psychological compatibility...
                    </p>
                  </div>
                )}

                {result && !loading && (
                  <div className={`space-y-6 transition-all duration-700 ${revealed ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'}`}>
                    <div className="bg-gradient-to-br from-red-500/20 to-rose-500/10 border border-red-500/30 rounded-2xl p-6">
                      <div className="flex items-center justify-center gap-2 mb-4">
                        <Skull className="w-8 h-8 text-red-400" />
                        <span className="text-xl font-bold text-white">Your Nemesis:</span>
                      </div>
                      <h3 className="text-2xl font-bold text-red-300 mb-4">{result.nemesis_type}</h3>
                      
                      <div className="mb-4">
                        <p className="text-sm text-gray-400 mb-2">Clashing Traits:</p>
                        <div className="flex flex-wrap gap-2 justify-center">
                          {result.clashing_traits.map((trait, index) => (
                            <span 
                              key={index}
                              className="px-3 py-1 bg-red-500/20 text-red-300 rounded-full text-sm border border-red-500/30"
                            >
                              {trait}
                            </span>
                          ))}
                        </div>
                      </div>
                    </div>

                    <div className="bg-slate-800/50 rounded-xl p-4 text-left">
                      <h4 className="text-sm font-semibold text-gray-300 mb-2 flex items-center gap-2">
                        <AlertTriangle className="w-4 h-4 text-yellow-400" />
                        Why It Would Fail:
                      </h4>
                      <p className="text-sm text-gray-400">{result.why_it_would_fail}</p>
                    </div>

                    <div className="bg-slate-800/50 rounded-xl p-4 text-left">
                      <h4 className="text-sm font-semibold text-gray-300 mb-2 flex items-center gap-2">
                        <Brain className="w-4 h-4 text-purple-400" />
                        Scientific Explanation:
                      </h4>
                      <p className="text-sm text-gray-400 italic">{result.scientific_explanation}</p>
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
                    onClick={findNemesis}
                    disabled={loading}
                    className="flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-rose-700 transition disabled:opacity-50 shadow-lg shadow-red-500/30"
                  >
                    {loading ? (
                      <RefreshCw className="w-5 h-5 animate-spin" />
                    ) : (
                      <Skull className="w-5 h-5" />
                    )}
                    {result ? 'Find Another Nemesis' : 'Find My Nemesis'}
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
              href="/wingman/cosmic"
              className="p-4 bg-slate-800/50 rounded-xl border border-purple-500/20 hover:border-purple-500/40 transition text-center"
            >
              <Sparkles className="w-8 h-8 mx-auto text-violet-400 mb-2" />
              <span className="text-sm text-gray-300">Cosmic Match</span>
            </Link>
            <Link
              href="/wingman/fortune"
              className="p-4 bg-slate-800/50 rounded-xl border border-purple-500/20 hover:border-purple-500/40 transition text-center"
            >
              <Sparkles className="w-8 h-8 mx-auto text-yellow-400 mb-2" />
              <span className="text-sm text-gray-300">Fortune</span>
            </Link>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}
