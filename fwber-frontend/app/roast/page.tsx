'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import { Flame, Sparkles, Copy, Share2, Wand2 } from 'lucide-react'
import AppHeader from '@/components/AppHeader'
import ProtectedRoute from '@/components/ProtectedRoute'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import { useAuth } from '@/lib/auth-context'
import { useAiWingman } from '@/lib/hooks/use-ai-wingman'
import { roastPublic } from '@/lib/api/content-generation'
import { useToast } from '@/components/ToastProvider'

export default function RoastPage() {
  const { isAuthenticated } = useAuth()
  const { roastProfile } = useAiWingman()
  const { showError, showSuccess } = useToast()
  const [mode, setMode] = useState<'roast' | 'hype'>('roast')
  const [result, setResult] = useState<string | null>(null)
  const [shareId, setShareId] = useState<string | null>(null)
  const [publicForm, setPublicForm] = useState({ name: '', job: '', trait: '' })
  const [isPublicLoading, setIsPublicLoading] = useState(false)

  const isRoast = mode === 'roast'
  const title = isRoast ? 'Roast Generator' : 'Hype Generator'
  const accentClasses = isRoast
    ? 'from-orange-500 to-red-600'
    : 'from-cyan-500 to-blue-600'

  const activeShareUrl = useMemo(() => {
    if (!shareId || typeof window === 'undefined') {
      return null
    }

    return `${window.location.origin}/share/${shareId}`
  }, [shareId])

  const handleAuthenticatedGenerate = async () => {
    try {
      const response = await roastProfile.mutateAsync(mode)
      setResult(response.roast)
      setShareId(response.share_id || null)
    } catch (error) {
      showError('Generation failed', error instanceof Error ? error.message : 'Unable to generate content right now.')
    }
  }

  const handlePublicGenerate = async () => {
    if (!publicForm.name || !publicForm.job || !publicForm.trait) {
      showError('Missing details', 'Name, job, and standout trait are all required for a public preview.')
      return
    }

    setIsPublicLoading(true)

    try {
      const response = await roastPublic(publicForm.name, publicForm.job, publicForm.trait, mode)
      setResult(response.roast)
      setShareId(response.share_id || null)
    } catch (error) {
      showError('Generation failed', error instanceof Error ? error.message : 'Unable to generate preview right now.')
    } finally {
      setIsPublicLoading(false)
    }
  }

  const handleCopy = async () => {
    if (!result) {
      return
    }

    await navigator.clipboard.writeText(activeShareUrl ? `${result}\n\n${activeShareUrl}` : result)
    showSuccess('Copied', 'Your AI result is now on the clipboard.')
  }

  const handleShare = async () => {
    if (!result) {
      return
    }

    if (navigator.share) {
      try {
        await navigator.share({
          title: isRoast ? 'My fwber profile roast' : 'My fwber profile hype',
          text: result,
          url: activeShareUrl || undefined,
        })
        return
      } catch {
        // fall through to copy
      }
    }

    await handleCopy()
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
      <AppHeader title={title} />
      <main className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div className="mb-8 rounded-3xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div className="max-w-2xl">
              <h1 className="text-4xl font-black tracking-tight text-gray-900 dark:text-white">{title}</h1>
              <p className="mt-3 text-base text-gray-600 dark:text-gray-300">
                Bring back one of the pruned AI features: generate a playful roast or a confidence-boosting hype pass for your profile.
                Signed-in users can generate from their actual profile, while guests can try a public preview.
              </p>
            </div>
            <div className={`inline-flex items-center gap-2 rounded-full bg-gradient-to-r ${accentClasses} px-4 py-2 text-sm font-semibold text-white shadow-lg`}>
              <Wand2 className="h-4 w-4" />
              Restored AI Surface
            </div>
          </div>
        </div>

        <Tabs value={mode} onValueChange={(value) => { setMode(value as 'roast' | 'hype'); setResult(null); setShareId(null) }} className="space-y-6">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="roast">Roast 🔥</TabsTrigger>
            <TabsTrigger value="hype">Hype ✨</TabsTrigger>
          </TabsList>

          <TabsContent value={mode} className="space-y-6">
            <div className="grid gap-6 lg:grid-cols-2">
              <Card>
                <CardHeader>
                  <CardTitle>{isAuthenticated ? 'Use my real profile' : 'Sign in for full profile mode'}</CardTitle>
                  <CardDescription>
                    {isAuthenticated
                      ? 'Generate from your actual profile and optionally get a shareable result id.'
                      : 'Authenticated mode uses your saved profile. Sign in to generate the full version.'}
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {isAuthenticated ? (
                    <Button
                      onClick={handleAuthenticatedGenerate}
                      disabled={roastProfile.isPending}
                      className={`w-full bg-gradient-to-r ${accentClasses} text-white`}
                    >
                      {isRoast ? <Flame className="mr-2 h-4 w-4" /> : <Sparkles className="mr-2 h-4 w-4" />}
                      {roastProfile.isPending ? 'Generating…' : isRoast ? 'Roast my profile' : 'Hype my profile'}
                    </Button>
                  ) : (
                    <Link href="/login" className="block">
                      <Button className="w-full" variant="outline">Sign in to use profile mode</Button>
                    </Link>
                  )}
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Public preview</CardTitle>
                  <CardDescription>
                    Try the restored roast/hype generator without signing in.
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="name">Name</Label>
                    <Input id="name" value={publicForm.name} onChange={(e) => setPublicForm((prev) => ({ ...prev, name: e.target.value }))} placeholder="Mia" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="job">Job / vibe</Label>
                    <Input id="job" value={publicForm.job} onChange={(e) => setPublicForm((prev) => ({ ...prev, job: e.target.value }))} placeholder="Night-shift nurse" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="trait">Standout trait</Label>
                    <Textarea id="trait" value={publicForm.trait} onChange={(e) => setPublicForm((prev) => ({ ...prev, trait: e.target.value }))} placeholder="Always arrives 10 minutes early and has three chaotic group chats going at once." />
                  </div>
                  <Button onClick={handlePublicGenerate} disabled={isPublicLoading} variant="secondary" className="w-full">
                    {isPublicLoading ? 'Generating…' : isRoast ? 'Preview roast' : 'Preview hype'}
                  </Button>
                </CardContent>
              </Card>
            </div>

            <Card>
              <CardHeader>
                <CardTitle>Result</CardTitle>
                <CardDescription>
                  {result ? 'Your restored AI output is ready.' : 'Generate a roast or hype result to see it here.'}
                </CardDescription>
              </CardHeader>
              <CardContent>
                {result ? (
                  <div className="space-y-4">
                    <div className={`rounded-2xl border p-5 text-base leading-7 ${isRoast ? 'border-orange-200 bg-orange-50 dark:border-orange-900/50 dark:bg-orange-950/20' : 'border-cyan-200 bg-cyan-50 dark:border-cyan-900/50 dark:bg-cyan-950/20'}`}>
                      {result}
                    </div>
                    <div className="flex flex-wrap gap-3">
                      <Button onClick={handleCopy} variant="outline"><Copy className="mr-2 h-4 w-4" />Copy</Button>
                      <Button onClick={handleShare}><Share2 className="mr-2 h-4 w-4" />Share</Button>
                    </div>
                    {activeShareUrl && (
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        Share link: <a href={activeShareUrl} className="text-blue-600 underline">{activeShareUrl}</a>
                      </p>
                    )}
                  </div>
                ) : (
                  <div className="rounded-2xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    Nothing generated yet.
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </main>
    </div>
  )
}
