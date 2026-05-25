'use client'

import { useRef, useCallback, useEffect, useState } from 'react'

/**
 * Auto-save hook with debounced persistence.
 *
 * Tracks a "dirty" flag — whenever formData changes, we schedule a save
 * after `debounceMs` milliseconds of inactivity.  The caller provides an
 * `onSave` callback that receives the current form data and persists it.
 *
 * Returns:
 *   status     – 'idle' | 'saving' | 'saved' | 'error'
 *   saveNow()  – immediately flush (used before unload / tab close)
 *   isDirty    – whether unsaved changes exist
 */
export type AutoSaveStatus = 'idle' | 'saving' | 'saved' | 'error'

export function useAutoSave<T>({
  data,
  onSave,
  debounceMs = 1500,
  enabled = true,
}: {
  data: T
  onSave: (data: T) => Promise<void>
  debounceMs?: number
  enabled?: boolean
}) {
  const [status, setStatus] = useState<AutoSaveStatus>('idle')
  const [isDirty, setIsDirty] = useState(false)
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const dataRef = useRef<T>(data)
  const onSaveRef = useRef(onSave)
  const savingRef = useRef(false)

  // Keep refs fresh so the timer closure always sees the latest
  useEffect(() => { dataRef.current = data }, [data])
  useEffect(() => { onSaveRef.current = onSave }, [onSave])

  // When data changes, mark dirty and schedule a debounced save
  useEffect(() => {
    if (!enabled) return

    setIsDirty(true)
    setStatus('idle')

    if (timerRef.current) clearTimeout(timerRef.current)

    timerRef.current = setTimeout(async () => {
      if (savingRef.current) return
      savingRef.current = true
      setStatus('saving')
      try {
        await onSaveRef.current(dataRef.current)
        setStatus('saved')
        setIsDirty(false)
        // Reset status after a moment so the indicator fades
        setTimeout(() => setStatus(prev => (prev === 'saved' ? 'idle' : prev)), 3000)
      } catch {
        setStatus('error')
      } finally {
        savingRef.current = false
      }
    }, debounceMs)

    return () => {
      if (timerRef.current) clearTimeout(timerRef.current)
    }
  }, [data, debounceMs, enabled])

  // Flush immediately (e.g. beforeunload)
  const saveNow = useCallback(async () => {
    if (!isDirty && !savingRef.current) return
    if (timerRef.current) clearTimeout(timerRef.current)
    if (savingRef.current) return
    savingRef.current = true
    setStatus('saving')
    try {
      await onSaveRef.current(dataRef.current)
      setStatus('saved')
      setIsDirty(false)
    } catch {
      setStatus('error')
    } finally {
      savingRef.current = false
    }
  }, [isDirty])

  // Warn before leaving with unsaved changes
  useEffect(() => {
    if (!enabled) return
    const handler = (e: BeforeUnloadEvent) => {
      if (isDirty) {
        e.preventDefault()
      }
    }
    window.addEventListener('beforeunload', handler)
    return () => window.removeEventListener('beforeunload', handler)
  }, [isDirty, enabled])

  // Save on visibility change (mobile tab switch, etc.)
  useEffect(() => {
    if (!enabled) return
    const handler = () => {
      if (document.visibilityState === 'hidden' && isDirty) {
        saveNow()
      }
    }
    document.addEventListener('visibilitychange', handler)
    return () => document.removeEventListener('visibilitychange', handler)
  }, [isDirty, saveNow, enabled])

  return { status, saveNow, isDirty } as const
}
