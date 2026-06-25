'use client'

import { motion } from 'framer-motion'
import { ReactNode } from 'react'

interface Props {
  children: ReactNode
  className?: string
  intensity?: 'subtle' | 'medium' | 'strong'
}

export function AmbientGlow({ children, className = '', intensity = 'medium' }: Props) {
  const opacity = intensity === 'subtle' ? 0.3 : intensity === 'medium' ? 0.5 : 0.7
  const blur = intensity === 'subtle' ? '60px' : intensity === 'medium' ? '80px' : '100px'

  return (
    <div className={`relative ${className}`}>
      {/* Purple glow */}
      <div
        className="absolute -inset-[100px] pointer-events-none"
        aria-hidden="true"
        style={{
          background: `radial-gradient(circle at 30% 40%, hsla(259, 80%, 60%, ${opacity * 0.3}), transparent 60%)`,
          filter: `blur(${blur})`,
        }}
      />
      {/* Pink glow */}
      <div
        className="absolute -inset-[100px] pointer-events-none"
        aria-hidden="true"
        style={{
          background: `radial-gradient(circle at 70% 60%, hsla(336, 80%, 60%, ${opacity * 0.2}), transparent 50%)`,
          filter: `blur(${blur})`,
        }}
      />
      {/* Cyan glow */}
      <div
        className="absolute -inset-[100px] pointer-events-none"
        aria-hidden="true"
        style={{
          background: `radial-gradient(circle at 50% 80%, hsla(189, 60%, 55%, ${opacity * 0.15}), transparent 50%)`,
          filter: `blur(${blur})`,
        }}
      />
      {children}
    </div>
  )
}

export function ShimmerBorder({ children, className = '' }: { children: ReactNode; className?: string }) {
  return (
    <div className={`relative overflow-hidden rounded-2xl ${className}`}>
      {/* Animated gradient border */}
      <motion.div
        className="absolute inset-0 rounded-2xl"
        style={{
          background: 'conic-gradient(from 0deg, #ec4899, #a855f7, #22d3ee, #a855f7, #ec4899)',
          mask: 'linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0)',
          maskComposite: 'exclude',
          WebkitMask: 'linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0)',
          WebkitMaskComposite: 'xor',
          padding: '1.5px',
        }}
        animate={{ rotate: 360 }}
        transition={{ duration: 6, repeat: Infinity, ease: 'linear' }}
      />
      {children}
    </div>
  )
}

export function GlowingDot({ className = '' }: { className?: string }) {
  return (
    <span className={`relative inline-flex h-2 w-2 ${className}`}>
      <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-purple-400 opacity-75" />
      <span className="relative inline-flex h-2 w-2 rounded-full bg-purple-500" />
    </span>
  )
}

export function PremiumBadge({ text, variant = 'shiny' }: { text: string; variant?: 'shiny' | 'gold' | 'glass' | 'success' }) {
  const styles: Record<string, string> = {
    shiny: 'bg-gradient-to-r from-pink-500/20 via-purple-500/20 to-cyan-500/20 text-pink-500 dark:text-pink-400 border-pink-500/30',
    gold: 'bg-gradient-to-r from-amber-400/20 to-orange-500/20 text-amber-600 dark:text-amber-400 border-amber-500/30',
    glass: 'glass-light text-foreground border-white/30 dark:border-white/10',
    success: 'bg-gradient-to-r from-emerald-400/20 to-green-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
  }

  return (
    <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider backdrop-blur-sm ${styles[variant] || styles.shiny}`}>
      {text}
    </span>
  )
}

export function GradientIcon({ children, gradient = 'from-pink-500 via-purple-500 to-cyan-500', className = '' }: {
  children: ReactNode; gradient?: string; className?: string
}) {
  return (
    <div className={`inline-flex items-center justify-center rounded-xl bg-gradient-to-br ${gradient} shadow-lg p-2.5 text-white ${className}`}>
      {children}
    </div>
  )
}
