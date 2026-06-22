'use client'

import { useEffect, useRef, useCallback } from 'react'

interface Particle {
  x: number
  y: number
  vx: number
  vy: number
  size: number
  opacity: number
  hue: number
  life: number
  maxLife: number
}

export function AnimatedParticles({ className = '' }: { className?: string }) {
  const canvasRef = useRef<HTMLCanvasElement>(null)
  const particlesRef = useRef<Particle[]>([])
  const frameRef = useRef(0)
  const mouseRef = useRef({ x: -1000, y: -1000 })

  const spawnParticle = useCallback((x: number, y: number) => {
    const hue = 259 + Math.random() * 70 // purple to pink range
    particlesRef.current.push({
      x, y,
      vx: (Math.random() - 0.5) * 2,
      vy: (Math.random() - 0.5) * 2 - 1,
      size: Math.random() * 3 + 1,
      opacity: 0.6 + Math.random() * 0.4,
      hue,
      life: 0,
      maxLife: 80 + Math.random() * 120,
    })
  }, [])

  useEffect(() => {
    const canvas = canvasRef.current
    if (!canvas) return

    const ctx = canvas.getContext('2d')
    if (!ctx) return

    const resize = () => {
      canvas.width = window.innerWidth
      canvas.height = window.innerHeight
    }
    resize()
    window.addEventListener('resize', resize)

    // Spawn initial particles
    for (let i = 0; i < 40; i++) {
      spawnParticle(Math.random() * canvas.width, Math.random() * canvas.height)
    }

    const onMouse = (e: MouseEvent) => {
      mouseRef.current = { x: e.clientX, y: e.clientY }
      // Spawn particles at mouse position
      if (Math.random() > 0.5) {
        spawnParticle(e.clientX + (Math.random() - 0.5) * 20, e.clientY + (Math.random() - 0.5) * 20)
      }
    }
    window.addEventListener('mousemove', onMouse)

    let animationId: number

    const animate = () => {
      if (!canvas || !ctx) return
      ctx.clearRect(0, 0, canvas.width, canvas.height)

      const particles = particlesRef.current
      for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i]
        p.x += p.vx
        p.y += p.vy
        p.vy += 0.01 // gravity
        p.life++
        p.opacity = Math.max(0, p.opacity - 0.003)

        if (p.life > p.maxLife || p.opacity <= 0) {
          particles.splice(i, 1)
          continue
        }

        // Draw particle
        const alpha = p.opacity * (1 - p.life / p.maxLife)
        ctx.beginPath()
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2)
        ctx.fillStyle = `hsla(${p.hue}, 80%, 65%, ${alpha})`
        ctx.fill()

        // Glow around particle
        const gradient = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.size * 4)
        gradient.addColorStop(0, `hsla(${p.hue}, 80%, 65%, ${alpha * 0.3})`)
        gradient.addColorStop(1, `hsla(${p.hue}, 80%, 65%, 0)`)
        ctx.beginPath()
        ctx.arc(p.x, p.y, p.size * 4, 0, Math.PI * 2)
        ctx.fillStyle = gradient
        ctx.fill()
      }

      // Keep particle count reasonable
      const maxParticles = 80
      while (particles.length < maxParticles && Math.random() > 0.6) {
        spawnParticle(Math.random() * canvas.width, Math.random() * canvas.height)
      }

      // Draw connection lines between nearby particles
      ctx.strokeStyle = `hsla(259, 50%, 60%, 0.06)`
      ctx.lineWidth = 0.5
      for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
          const dx = particles[i].x - particles[j].x
          const dy = particles[i].y - particles[j].y
          const dist = Math.sqrt(dx * dx + dy * dy)
          if (dist < 120) {
            const alpha = (1 - dist / 120) * 0.15
            ctx.beginPath()
            ctx.moveTo(particles[i].x, particles[i].y)
            ctx.lineTo(particles[j].x, particles[j].y)
            ctx.strokeStyle = `hsla(259, 50%, 60%, ${alpha})`
            ctx.stroke()
          }
        }
      }

      animationId = requestAnimationFrame(animate)
    }

    animate()

    return () => {
      window.removeEventListener('resize', resize)
      window.removeEventListener('mousemove', onMouse)
      cancelAnimationFrame(animationId)
    }
  }, [spawnParticle])

  return (
    <canvas
      ref={canvasRef}
      className={`fixed inset-0 pointer-events-none z-0 ${className}`}
      aria-hidden="true"
    />
  )
}
