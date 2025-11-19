'use client'

import { useState, useRef, useEffect, useCallback } from 'react'
import Image from 'next/image'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Heart, X, Star } from 'lucide-react'

interface SwipeableCardProps {
  user: {
    id: number
    name: string
    age?: number
    bio?: string
    distance?: number
    compatibility_score?: number
    photos?: string[]
  }
  onSwipe: (direction: 'left' | 'right' | 'up') => void
  onAction: (action: 'like' | 'pass' | 'super_like') => void
}

export default function SwipeableCard({ user, onSwipe, onAction }: SwipeableCardProps) {
  const [isDragging, setIsDragging] = useState(false)
  const [startPos, setStartPos] = useState({ x: 0, y: 0 })
  const [currentPos, setCurrentPos] = useState({ x: 0, y: 0 })
  const [rotation, setRotation] = useState(0)
  const cardRef = useRef<HTMLDivElement>(null)

  const handleStart = useCallback((clientX: number, clientY: number) => {
    setIsDragging(true)
    setStartPos({ x: clientX, y: clientY })
    setCurrentPos({ x: clientX, y: clientY })
  }, [])

  const handleMove = useCallback((clientX: number, clientY: number) => {
    if (!isDragging) return

    const deltaX = clientX - startPos.x
    const deltaY = clientY - startPos.y
    
    setCurrentPos({ x: clientX, y: clientY })
    
    // Calculate rotation based on horizontal movement
    const rotationValue = Math.min(Math.max(deltaX * 0.1, -15), 15)
    setRotation(rotationValue)

    // Update card position
    if (cardRef.current) {
      cardRef.current.style.transform = `translate(${deltaX}px, ${deltaY}px) rotate(${rotationValue}deg)`
    }
  }, [isDragging, startPos])

  const handleEnd = useCallback(() => {
    if (!isDragging) return
    
    setIsDragging(false)
    
    const deltaX = currentPos.x - startPos.x
    const deltaY = currentPos.y - startPos.y
    const threshold = 100 // Minimum distance to trigger swipe

    if (Math.abs(deltaX) > threshold) {
      if (deltaX > 0) {
        onAction('like')
        onSwipe('right')
      } else {
        onAction('pass')
        onSwipe('left')
      }
    } else if (deltaY < -threshold) {
      onAction('super_like')
      onSwipe('up')
    } else {
      // Reset position
      if (cardRef.current) {
        cardRef.current.style.transform = 'translate(0px, 0px) rotate(0deg)'
      }
      setRotation(0)
    }
  }, [isDragging, currentPos, startPos, onAction, onSwipe])

  // Touch events
  const handleTouchStart = useCallback((e: React.TouchEvent) => {
    const touch = e.touches[0]
    handleStart(touch.clientX, touch.clientY)
  }, [handleStart])

  const handleTouchMove = useCallback((e: React.TouchEvent) => {
    const touch = e.touches[0]
    handleMove(touch.clientX, touch.clientY)
  }, [handleMove])

  const handleTouchEnd = useCallback(() => {
    handleEnd()
  }, [handleEnd])

  // Mouse events
  const handleMouseDown = useCallback((e: React.MouseEvent) => {
    e.preventDefault()
    handleStart(e.clientX, e.clientY)
  }, [handleStart])

  useEffect(() => {
    const handleGlobalMouseMove = (e: MouseEvent) => {
      if (isDragging) {
        handleMove(e.clientX, e.clientY)
      }
    }

    const handleGlobalMouseUp = () => {
      if (isDragging) {
        handleEnd()
      }
    }

    document.addEventListener('mousemove', handleGlobalMouseMove)
    document.addEventListener('mouseup', handleGlobalMouseUp)

    return () => {
      document.removeEventListener('mousemove', handleGlobalMouseMove)
      document.removeEventListener('mouseup', handleGlobalMouseUp)
    }
  }, [isDragging, handleMove, handleEnd])

  return (
    <div className="relative w-full max-w-sm mx-auto">
      <Card
        ref={cardRef}
        className={`cursor-grab active:cursor-grabbing transition-transform duration-200 ${
          isDragging ? 'shadow-2xl' : 'shadow-lg'
        }`}
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
        onTouchEnd={handleTouchEnd}
        onMouseDown={handleMouseDown}
        style={{
          // Initial transform is handled by state/ref, but we can set a default here if needed
          transform: !isDragging ? 'translate(0px, 0px) rotate(0deg)' : undefined
        }}
      >
        <CardHeader className="relative">
          <div className="aspect-square w-full bg-gray-200 rounded-lg flex items-center justify-center mb-4">
            {user.photos && user.photos.length > 0 ? (
              <Image
                src={user.photos[0]}
                alt={user.name}
                fill
                className="object-cover rounded-lg"
              />
            ) : (
              <span className="text-4xl font-semibold text-gray-600">
                {user.name.charAt(0).toUpperCase()}
              </span>
            )}
          </div>
          
          <CardTitle className="text-xl">
            {user.name}
            {user.age && <span className="text-gray-500 ml-2">({user.age})</span>}
          </CardTitle>
          
          {user.distance && (
            <p className="text-sm text-gray-500">
              {user.distance.toFixed(1)} miles away
            </p>
          )}
        </CardHeader>
        
        <CardContent className="space-y-4">
          {user.bio && (
            <p className="text-sm text-gray-600 line-clamp-3">
              {user.bio}
            </p>
          )}
          
          {user.compatibility_score && (
            <div>
              <div className="flex justify-between items-center mb-2">
                <span className="text-sm font-medium">Compatibility</span>
                <span className="text-sm text-blue-600">{user.compatibility_score}%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div 
                  className="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                  style={{ width: `${user.compatibility_score}%` }}
                ></div>
              </div>
            </div>
          )}
          
          <div className="flex space-x-2 pt-4">
            <Button
              variant="outline"
              size="sm"
              className="flex-1"
              onClick={() => {
                onAction('pass')
                onSwipe('left')
              }}
            >
              <X className="w-4 h-4 mr-1" />
              Pass
            </Button>
            
            <Button
              size="sm"
              className="flex-1 bg-blue-600 hover:bg-blue-700"
              onClick={() => {
                onAction('super_like')
                onSwipe('up')
              }}
            >
              <Star className="w-4 h-4 mr-1" />
              Super Like
            </Button>
            
            <Button
              size="sm"
              className="flex-1 bg-red-500 hover:bg-red-600"
              onClick={() => {
                onAction('like')
                onSwipe('right')
              }}
            >
              <Heart className="w-4 h-4 mr-1" />
              Like
            </Button>
          </div>
        </CardContent>
      </Card>
      
      {/* Swipe indicators */}
      <div className="absolute inset-0 pointer-events-none">
        <div className="absolute top-1/2 left-4 transform -translate-y-1/2 opacity-0 transition-opacity duration-200">
          <div className="bg-red-500 text-white px-4 py-2 rounded-lg font-semibold">
            PASS
          </div>
        </div>
        
        <div className="absolute top-1/2 right-4 transform -translate-y-1/2 opacity-0 transition-opacity duration-200">
          <div className="bg-green-500 text-white px-4 py-2 rounded-lg font-semibold">
            LIKE
          </div>
        </div>
        
        <div className="absolute top-4 left-1/2 transform -translate-x-1/2 opacity-0 transition-opacity duration-200">
          <div className="bg-blue-500 text-white px-4 py-2 rounded-lg font-semibold">
            SUPER LIKE
          </div>
        </div>
      </div>
    </div>
  )
}
