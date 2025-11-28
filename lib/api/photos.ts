'use client'

import { useState, useEffect } from 'react'
import type { FileWithFaceBlurMetadata } from '@/lib/faceBlurTelemetry'

export interface Photo {
  id: string
  url: string
  thumbnail_url?: string
  is_primary: boolean
  order: number
  created_at: string
  updated_at: string
}

export interface PhotoUploadResponse {
  success: boolean
  photos: Photo[]
  message?: string
}

export interface PhotoReorderRequest {
  photo_ids: string[]
}

class PhotoAPI {
  private baseUrl: string

  constructor() {
    this.baseUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`
    
    const defaultHeaders: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    }

    // Add authorization header if token exists
    const token = localStorage.getItem('fwber_token')
    if (token) {
      defaultHeaders['Authorization'] = `Bearer ${token}`
    }

    const config: RequestInit = {
      ...options,
      headers: {
        ...defaultHeaders,
        ...options.headers,
      },
    }

    try {
      const response = await fetch(url, config)
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
      }

      return await response.json()
    } catch (error) {
      console.error(`API request failed for ${endpoint}:`, error)
      throw error
    }
  }

  // Get all photos for the current user
  async getPhotos(): Promise<Photo[]> {
    const response = await this.request<{ success: boolean; data: Photo[]; count: number }>('/photos')
    // Backend returns { success, data, count } format
    return response.data || []
  }

  // Upload new photos (uploads one at a time since backend expects single 'photo' field)
  async uploadPhotos(
    files: File[],
    onProgress?: (fileIndex: number, progress: number, fileName: string) => void
  ): Promise<PhotoUploadResponse> {
    const uploadedPhotos: Photo[] = []
    
    // Upload files sequentially since backend expects single photo per request
    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      const fileWithMetadata = file as FileWithFaceBlurMetadata
      
      // Report progress start
      if (onProgress) onProgress(i, 0, file.name)
      
      const formData = new FormData()
      formData.append('photo', file) // Backend expects 'photo', not 'photos[...]'
      if (fileWithMetadata.faceBlurMetadata) {
        formData.append('face_blur_metadata', JSON.stringify(fileWithMetadata.faceBlurMetadata))
      }
      
      const url = `${this.baseUrl}/photos`
      const token = localStorage.getItem('fwber_token')
      
      try {
        // Simulate progress for better UX
        if (onProgress) {
          // Simulate upload progress (50% before request completes)
          const progressInterval = setInterval(() => {
            if (onProgress) {
              const current = Math.min(50, Math.random() * 30 + 20)
              onProgress(i, current, file.name)
            }
          }, 200)
          
          const response = await fetch(url, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              ...(token && { 'Authorization': `Bearer ${token}` }),
            },
            body: formData,
          })
          
          clearInterval(progressInterval)
          
          // Progress to 80% during processing
          if (onProgress) onProgress(i, 80, file.name)
          
          if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            if (onProgress) onProgress(i, 100, file.name) // Complete even on error for UI
            throw new Error(errorData.message || `Upload failed for ${file.name}: ${response.status}`)
          }
          
          const result = await response.json()
          
          // Progress to 100% on success
          if (onProgress) onProgress(i, 100, file.name)
          
          if (result.success && result.data) {
            // Backend returns single photo object, convert to Photo[] format
            uploadedPhotos.push({
              id: String(result.data.id),
              url: result.data.url || result.data.thumbnail_url,
              thumbnail_url: result.data.thumbnail_url,
              is_primary: result.data.is_primary || false,
              order: result.data.sort_order || 0,
              created_at: result.data.created_at,
              updated_at: result.data.updated_at || result.data.created_at,
            })
          }
        } else {
          // No progress callback - simple upload
          const response = await fetch(url, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              ...(token && { 'Authorization': `Bearer ${token}` }),
            },
            body: formData,
          })
          
          if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            throw new Error(errorData.message || `Upload failed for ${file.name}: ${response.status}`)
          }
          
          const result = await response.json()
          if (result.success && result.data) {
            uploadedPhotos.push({
              id: String(result.data.id),
              url: result.data.url || result.data.thumbnail_url,
              thumbnail_url: result.data.thumbnail_url,
              is_primary: result.data.is_primary || false,
              order: result.data.sort_order || 0,
              created_at: result.data.created_at,
              updated_at: result.data.updated_at || result.data.created_at,
            })
          }
        }
      } catch (error) {
        if (onProgress) onProgress(i, 100, file.name) // Mark as complete even on error
        throw error
      }
    }
    
    return {
      success: true,
      photos: uploadedPhotos,
      message: `Successfully uploaded ${uploadedPhotos.length} photo(s)`
    }
  }

  // Update photo details
  async updatePhoto(photoId: string, updates: Partial<Photo>): Promise<Photo> {
    return this.request<Photo>(`/photos/${photoId}`, {
      method: 'PUT',
      body: JSON.stringify(updates),
    })
  }

  // Set primary photo
  async setPrimaryPhoto(photoId: string): Promise<Photo[]> {
    // Backend doesn't have /primary endpoint, use PUT with is_primary=true
    const response = await this.request<{ success: boolean; data: Photo }>(`/photos/${photoId}`, {
      method: 'PUT',
      body: JSON.stringify({ is_primary: true }),
    })
    // Return updated photos list by fetching all photos
    return this.getPhotos()
  }

  // Reorder photos
  async reorderPhotos(photoIds: string[]): Promise<Photo[]> {
    // Backend returns { success: true, message: ... }, not Photo[]
    // So we make the reorder request and then fetch updated photos
    await this.request<{ success: boolean; message?: string }>('/photos/reorder', {
      method: 'POST',
      body: JSON.stringify({ photo_ids: photoIds.map(id => parseInt(id, 10)) }), // Convert to integers for backend
    })
    // Refetch photos to get updated order
    return this.getPhotos()
  }

  // Delete photo
  async deletePhoto(photoId: string): Promise<{ success: boolean; message?: string }> {
    return this.request<{ success: boolean; message?: string }>(`/photos/${photoId}`, {
      method: 'DELETE',
    })
  }

  // Get photo upload limits and settings
  async getUploadSettings(): Promise<{
    max_photos: number
    max_file_size: number
    allowed_formats: string[]
    compression_quality: number
  }> {
    try {
      return await this.request<{
        max_photos: number
        max_file_size: number
        allowed_formats: string[]
        compression_quality: number
      }>('/photos/settings', {
        method: 'GET',
      })
    } catch (error) {
      // If endpoint doesn't exist (405 or 404), return defaults
      if (error instanceof Error && (error.message.includes('405') || error.message.includes('404'))) {
        return {
          max_photos: 6,
          max_file_size: 5 * 1024 * 1024, // 5MB
          allowed_formats: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
          compression_quality: 85,
        }
      }
      throw error
    }
  }

  // Reveal photo to a match
  async revealPhoto(photoId: string, matchId: string): Promise<{ success: boolean; status: string }> {
    return this.request<{ success: boolean; status: string }>(`/photos/${photoId}/reveal`, {
      method: 'POST',
      body: JSON.stringify({ match_id: matchId }),
    })
  }

  // Get original photo blob
  async getOriginalPhoto(photoId: string): Promise<Blob> {
    const url = `${this.baseUrl}/photos/${photoId}/original`
    const token = localStorage.getItem('fwber_token')
    
    const response = await fetch(url, {
      headers: {
        ...(token && { 'Authorization': `Bearer ${token}` }),
      },
    })

    if (!response.ok) {
      throw new Error(`Failed to fetch original photo: ${response.status}`)
    }

    return await response.blob()
  }
}

// Create singleton instance
export const photoAPI = new PhotoAPI()

// React hook for photo management
export function usePhotos() {
  const [photos, setPhotos] = useState<Photo[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchPhotos = async () => {
    setLoading(true)
    setError(null)
    
    try {
      const photoData = await photoAPI.getPhotos()
      setPhotos(photoData)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch photos')
    } finally {
      setLoading(false)
    }
  }

  const uploadPhotos = async (
    files: File[],
    onProgress?: (fileIndex: number, progress: number, fileName: string) => void
  ) => {
    setLoading(true)
    setError(null)
    
    try {
      const response = await photoAPI.uploadPhotos(files, onProgress)
      if (response.success) {
        // Refetch all photos to get updated list with proper order
        const updatedPhotos = await photoAPI.getPhotos()
        setPhotos(updatedPhotos)
        return response
      } else {
        throw new Error(response.message || 'Upload failed')
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Upload failed')
      throw err
    } finally {
      setLoading(false)
    }
  }

  const deletePhoto = async (photoId: string) => {
    setLoading(true)
    setError(null)
    
    try {
      await photoAPI.deletePhoto(photoId)
      // Remove from state immediately - compare both string and number forms
      setPhotos(prev => prev.filter(photo => String(photo.id) !== photoId && photo.id !== photoId))
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Delete failed')
      throw err
    } finally {
      setLoading(false)
    }
  }

  const setPrimaryPhoto = async (photoId: string) => {
    setLoading(true)
    setError(null)
    
    try {
      const updatedPhotos = await photoAPI.setPrimaryPhoto(photoId)
      setPhotos(updatedPhotos)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to set primary photo')
      throw err
    } finally {
      setLoading(false)
    }
  }

  const reorderPhotos = async (photoIds: string[], skipOptimisticUpdate = false) => {
    // If optimistic update was already done, don't set loading (to prevent UI flash)
    if (!skipOptimisticUpdate) {
      setLoading(true)
    }
    setError(null)
    
    try {
      const updatedPhotos = await photoAPI.reorderPhotos(photoIds)
      // Only update state if we haven't already done optimistic update
      // This prevents the "teleport" effect
      if (!skipOptimisticUpdate) {
        setPhotos(updatedPhotos)
      }
      // Otherwise, the state was already set optimistically, so we just verify it matches
    } catch (err) {
      console.error('Reorder error:', err)
      setError(err instanceof Error ? err.message : 'Failed to reorder photos')
      throw err
    } finally {
      if (!skipOptimisticUpdate) {
        setLoading(false)
      }
    }
  }

  useEffect(() => {
    fetchPhotos()
  }, [])

  return {
    photos,
    loading,
    error,
    fetchPhotos,
    uploadPhotos,
    deletePhoto,
    setPrimaryPhoto,
    reorderPhotos,
    setPhotos, // Export for optimistic updates
  }
}

// Default photo settings
const DEFAULT_PHOTO_SETTINGS = {
  max_photos: 12, // Increased from 6 to be more generous
  max_file_size: 5 * 1024 * 1024, // 5MB in bytes
  allowed_formats: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
  compression_quality: 85,
}

// Photo upload settings hook
export function usePhotoSettings() {
  const [settings, setSettings] = useState<{
    max_photos: number
    max_file_size: number
    allowed_formats: string[]
    compression_quality: number
  } | null>(DEFAULT_PHOTO_SETTINGS) // Initialize with defaults
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const fetchSettings = async () => {
    setLoading(true)
    setError(null)
    
    try {
      const photoSettings = await photoAPI.getUploadSettings()
      setSettings(photoSettings)
    } catch (err) {
      // If endpoint doesn't exist, use defaults (don't set error, just use defaults)
      if (err instanceof Error && err.message.includes('405')) {
        // Method not allowed - endpoint exists but doesn't support GET
        // Just use defaults, don't show error
        setSettings(DEFAULT_PHOTO_SETTINGS)
      } else {
        // Other error - still use defaults but might log it
        setSettings(DEFAULT_PHOTO_SETTINGS)
        setError(err instanceof Error ? err.message : 'Failed to fetch settings')
      }
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    // Don't fetch if endpoint doesn't exist - just use defaults
    // fetchSettings()
  }, [])

  return {
    settings: settings || DEFAULT_PHOTO_SETTINGS, // Always return defaults if null
    loading,
    error,
    fetchSettings,
  }
}