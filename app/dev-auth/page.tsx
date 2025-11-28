"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"

export default function DevAuthPage() {
  const router = useRouter()
  const [status, setStatus] = useState("Setting dev token…")

  useEffect(() => {
    try {
      localStorage.setItem("auth_token", "dev")
      setStatus("Dev token set. Redirecting to profile…")
      // Wait longer to ensure auth context has time to update
      const t = setTimeout(() => {
        // Force a page reload to ensure auth context picks up the token
        window.location.href = "/profile"
      }, 1000)
      return () => clearTimeout(t)
    } catch (e) {
      setStatus("Failed to set token. Check localStorage permissions.")
    }
  }, [router])

  return (
    <div className="min-h-screen flex items-center justify-center">
      <div className="text-center">
        <div className="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-4" />
        <p className="text-muted-foreground">{status}</p>
      </div>
    </div>
  )
}
