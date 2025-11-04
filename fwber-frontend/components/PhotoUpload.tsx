'use client'

import { useState, useRef, useCallback, useEffect } from 'react'
import { useDropzone } from 'react-dropzone'
import Image from 'next/image'
import { Upload, X, Camera, RotateCcw, Download, Eye, ChevronLeft, ChevronRight, Trash2 } from 'lucide-react'

interface PhotoUploadProps {
  onUpload: (photos: File[], onProgress?: (fileIndex: number, progress: number, fileName: string) => void) => Promise<void>
  onRemove: (index: number) => void
  photos: string[]
  maxPhotos?: number
  maxSize?: number // in MB
  className?: string
}

interface PhotoPreview {
  file: File
  preview: string
  id: string
}

interface UploadProgress {
  fileName: string
  progress: number
  status: 'pending' | 'uploading' | 'completed' | 'error'
  error?: string
}

export default function PhotoUpload({
  onUpload,
  onRemove,
  photos = [],
  maxPhotos = 6,
  maxSize = 5,
  className = ''
}: PhotoUploadProps) {
  const [previews, setPreviews] = useState<PhotoPreview[]>([])
  const [isUploading, setIsUploading] = useState(false)
  const [uploadProgress, setUploadProgress] = useState<Map<string, UploadProgress>>(new Map())
  const [dragActive, setDragActive] = useState(false)
  const fileInputRef = useRef<HTMLInputElement>(null)

  const onDrop = useCallback(async (acceptedFiles: File[]) => {
    const validFiles = acceptedFiles.filter(file => {
      const isValidType = file.type.startsWith('image/')
      const isValidSize = file.size <= maxSize * 1024 * 1024
      
      if (!isValidType) {
        alert(`File ${file.name} is not a valid image type`)
        return false
      }
      
      if (!isValidSize) {
        alert(`File ${file.name} is too large. Maximum size is ${maxSize}MB`)
        return false
      }
      
      return true
    })

    if (validFiles.length === 0) return

    // Create previews for immediate feedback
    const newPreviews: PhotoPreview[] = validFiles.map(file => ({
      file,
      preview: URL.createObjectURL(file),
      id: Math.random().toString(36).substr(2, 9)
    }))

    setPreviews(prev => [...prev, ...newPreviews])

    // Auto-upload immediately after selection
    setIsUploading(true)
    const progressMap = new Map<string, UploadProgress>()
    newPreviews.forEach(preview => {
      progressMap.set(preview.id, {
        fileName: preview.file.name,
        progress: 0,
        status: 'pending'
      })
    })
    setUploadProgress(progressMap)

    try {
      const progressCallback = (fileIndex: number, progress: number, fileName: string) => {
        const preview = newPreviews[fileIndex]
        if (!preview) return
        
        setUploadProgress(prev => {
          const newMap = new Map(prev)
          const existing = newMap.get(preview.id) || { fileName, progress: 0, status: 'pending' as const }
          
          newMap.set(preview.id, {
            ...existing,
            fileName,
            progress,
            status: progress === 100 ? 'completed' as const : 'uploading' as const
          })
          
          return newMap
        })
      }

      await onUpload(newPreviews.map(p => p.file), progressCallback)
      
      // Mark all as completed
      setUploadProgress(prev => {
        const newMap = new Map(prev)
        newPreviews.forEach(preview => {
          const existing = newMap.get(preview.id)
          if (existing) {
            newMap.set(preview.id, { ...existing, status: 'completed', progress: 100 })
          }
        })
        return newMap
      })
      
      // Clean up previews and object URLs after upload
      newPreviews.forEach(preview => {
        URL.revokeObjectURL(preview.preview)
      })
      setPreviews([])
      
      setTimeout(() => {
        setIsUploading(false)
        setUploadProgress(new Map())
      }, 1500)
      
    } catch (error) {
      console.error('Upload failed:', error)
      // Mark all as error
      setUploadProgress(prev => {
        const newMap = new Map(prev)
        newPreviews.forEach(preview => {
          const existing = newMap.get(preview.id)
          if (existing && existing.status !== 'completed') {
            newMap.set(preview.id, { 
              ...existing, 
              status: 'error' as const,
              error: error instanceof Error ? error.message : 'Upload failed'
            })
          }
        })
        return newMap
      })
      
      setTimeout(() => {
        setIsUploading(false)
        setUploadProgress(new Map())
      }, 2000)
    }
  }, [maxSize, onUpload])

  const { getRootProps, getInputProps, isDragActive, open } = useDropzone({
    onDrop,
    accept: {
      'image/*': ['.jpeg', '.jpg', '.png', '.webp', '.gif']
    },
    maxFiles: maxPhotos - photos.length - previews.length,
    disabled: photos.length + previews.length >= maxPhotos,
    noClick: true, // Disable click on the entire dropzone
    noKeyboard: false, // Allow keyboard navigation
  })

  // Full-page drag and drop listener
  useEffect(() => {
    const handleDragEnter = (e: DragEvent) => {
      e.preventDefault()
      e.stopPropagation()
      if (e.dataTransfer?.types.includes('Files')) {
        setDragActive(true)
      }
    }

    const handleDragOver = (e: DragEvent) => {
      e.preventDefault()
      e.stopPropagation()
    }

    const handleDragLeave = (e: DragEvent) => {
      e.preventDefault()
      e.stopPropagation()
      // Only deactivate if leaving the window
      if (e.target === document || e.target === document.documentElement) {
        setDragActive(false)
      }
    }

    const handleDrop = (e: DragEvent) => {
      e.preventDefault()
      e.stopPropagation()
      setDragActive(false)
    }

    // Add listeners to the document
    document.addEventListener('dragenter', handleDragEnter)
    document.addEventListener('dragover', handleDragOver)
    document.addEventListener('dragleave', handleDragLeave)
    document.addEventListener('drop', handleDrop)

    return () => {
      document.removeEventListener('dragenter', handleDragEnter)
      document.removeEventListener('dragover', handleDragOver)
      document.removeEventListener('dragleave', handleDragLeave)
      document.removeEventListener('drop', handleDrop)
    }
  }, [])
  

  const removePreview = (id: string) => {
    const preview = previews.find(p => p.id === id)
    if (preview) {
      URL.revokeObjectURL(preview.preview)
    }
    setPreviews(prev => prev.filter(p => p.id !== id))
  }

  const removePhoto = (index: number) => {
    onRemove(index)
  }

  const totalPhotos = photos.length + previews.length

  return (
    <div className={`space-y-6 ${className}`}>
      {/* Full-page drag overlay */}
      {dragActive && (
        <div 
          {...getRootProps()}
          className="fixed inset-0 z-50 flex items-center justify-center bg-primary/10 backdrop-blur-sm"
          style={{ pointerEvents: 'auto' }}
        >
          <input {...getInputProps()} />
          <div className="bg-white dark:bg-gray-800 rounded-lg p-8 shadow-2xl border-4 border-dashed border-primary">
            <div className="flex flex-col items-center space-y-4">
              <div className="p-6 rounded-full bg-primary/20">
                <Upload className="w-16 h-16 text-primary" />
              </div>
              <h3 className="text-2xl font-bold text-gray-900 dark:text-white">
                Drop photos here
              </h3>
              <p className="text-gray-600 dark:text-gray-400">
                Release to upload (max {maxPhotos - totalPhotos} more)
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Upload Area */}
      <div
        className={`
          relative border-2 border-dashed rounded-lg p-8 text-center cursor-pointer
          transition-all duration-200 ease-in-out
          ${isDragActive || dragActive 
            ? 'border-primary bg-primary/5' 
            : 'border-muted-foreground/25 hover:border-primary/50'
          }
          ${totalPhotos >= maxPhotos ? 'opacity-50 cursor-not-allowed' : ''}
        `}
        onClick={() => {
          if (totalPhotos < maxPhotos) {
            open()
          }
        }}
        style={{
          pointerEvents: totalPhotos >= maxPhotos ? 'none' : 'auto',
          userSelect: 'none',
          WebkitUserSelect: 'none',
          minHeight: '200px',
        }}
      >
        <input {...getInputProps()} ref={fileInputRef} style={{ display: 'none' }} />
        
        <div 
          className="flex flex-col items-center space-y-4"
          style={{
            pointerEvents: 'none', // Make inner content not interfere with drag events
          }}
        >
          <div className="p-4 rounded-full bg-primary/10">
            <Camera className="w-8 h-8 text-primary" />
          </div>
          
          <div>
            <h3 className="text-lg font-semibold text-foreground">
              {isDragActive || dragActive ? 'Drop photos here' : 'Upload Photos'}
            </h3>
            <p className="text-sm text-muted-foreground mt-1">
              Drag and drop images anywhere here, or click to select
            </p>
            <p className="text-xs text-muted-foreground mt-2">
              Max {maxPhotos} photos, {maxSize}MB each • Uploads automatically
            </p>
          </div>
          
          <div className="text-xs text-muted-foreground">
            Supported: JPEG, PNG, WebP, GIF
          </div>
        </div>
      </div>

      {/* Batch Upload Progress */}
      {isUploading && uploadProgress.size > 0 && (
        <div className="space-y-4 bg-muted/50 rounded-lg p-4">
          <div className="flex justify-between items-center">
            <h4 className="font-semibold text-sm">Uploading {uploadProgress.size} photo{uploadProgress.size > 1 ? 's' : ''}...</h4>
            <span className="text-xs text-muted-foreground">
              {Array.from(uploadProgress.values()).filter(p => p.status === 'completed').length} / {uploadProgress.size} complete
            </span>
          </div>
          
          <div className="space-y-3">
            {previews.map((preview) => {
              const progress = uploadProgress.get(preview.id)
              if (!progress) return null
              
              const { progress: percent, status, fileName, error } = progress
              const isCompleted = status === 'completed'
              const isError = status === 'error'
              
              return (
                <div key={preview.id} className="space-y-1">
                  <div className="flex justify-between items-center text-xs">
                    <span className={`truncate flex-1 mr-2 ${isError ? 'text-red-600' : isCompleted ? 'text-green-600' : 'text-foreground'}`}>
                      {fileName.length > 30 ? `${fileName.substring(0, 30)}...` : fileName}
                    </span>
                    <span className={`font-medium ${isError ? 'text-red-600' : isCompleted ? 'text-green-600' : 'text-muted-foreground'}`}>
                      {isError ? 'Failed' : isCompleted ? 'Complete' : `${Math.round(percent)}%`}
                    </span>
                  </div>
                  <div className="w-full bg-muted rounded-full h-2 overflow-hidden">
                    <div 
                      className={`h-2 rounded-full transition-all duration-300 ${
                        isError 
                          ? 'bg-red-500' 
                          : isCompleted 
                            ? 'bg-green-500' 
                            : 'bg-primary'
                      }`}
                      style={{ width: `${percent}%` }}
                    />
                  </div>
                  {error && (
                    <p className="text-xs text-red-600 mt-1">{error}</p>
                  )}
                </div>
              )
            })}
          </div>
          
          {/* Overall Progress Bar */}
          <div className="pt-2 border-t border-border">
            <div className="flex justify-between text-xs mb-1">
              <span>Overall Progress</span>
              <span>
                {Math.round(
                  Array.from(uploadProgress.values()).reduce((sum, p) => sum + p.progress, 0) / uploadProgress.size
                )}%
              </span>
            </div>
            <div className="w-full bg-muted rounded-full h-2">
              <div 
                className="bg-primary h-2 rounded-full transition-all duration-300"
                style={{ 
                  width: `${Array.from(uploadProgress.values()).reduce((sum, p) => sum + p.progress, 0) / uploadProgress.size}%` 
                }}
              />
            </div>
          </div>
        </div>
      )}

      {/* Photo Grid */}
      {(photos.length > 0 || previews.length > 0) && (
        <div className="space-y-4">
          <div className="flex justify-between items-center">
            <h4 className="text-lg font-semibold">Your Photos</h4>
            <span className="text-sm text-muted-foreground">
              {totalPhotos} / {maxPhotos}
            </span>
          </div>
          
          <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
            {/* Existing Photos */}
            {photos.map((photo, index) => (
              <div key={`photo-${index}`} className="relative group">
                <div className="aspect-square rounded-lg overflow-hidden bg-muted">
                  <Image
                    src={photo}
                    alt={`Photo ${index + 1}`}
                    width={200}
                    height={200}
                    className="w-full h-full object-cover"
                  />
                </div>
                
                {/* Photo Actions */}
                <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-lg">
                  <div className="flex items-center justify-center h-full space-x-2">
                    <button
                      onClick={() => removePhoto(index)}
                      className="p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                      title="Remove photo"
                    >
                      <X className="w-4 h-4" />
                    </button>
                    <button
                      className="p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-colors"
                      title="View photo"
                    >
                      <Eye className="w-4 h-4" />
                    </button>
                  </div>
                </div>
                
                {/* Primary Photo Badge */}
                {index === 0 && (
                  <div className="absolute top-2 left-2 bg-primary text-primary-foreground px-2 py-1 rounded text-xs font-medium">
                    Primary
                  </div>
                )}
              </div>
            ))}
            
            {/* Preview Photos */}
            {previews.map((preview) => {
              const progress = uploadProgress.get(preview.id)
              const status = progress?.status || 'pending'
              const isUploadingPhoto = status === 'uploading'
              const isCompleted = status === 'completed'
              const isError = status === 'error'
              
              return (
                <div key={preview.id} className="relative group">
                  <div className="aspect-square rounded-lg overflow-hidden bg-muted">
                    <Image
                      src={preview.preview}
                      alt="Preview"
                      width={200}
                      height={200}
                      className="w-full h-full object-cover"
                    />
                    {/* Upload Progress Overlay */}
                    {isUploadingPhoto && progress && (
                      <div className="absolute inset-0 bg-black/30 flex items-center justify-center">
                        <div className="text-white text-xs font-medium">
                          {Math.round(progress.progress)}%
                        </div>
                      </div>
                    )}
                    {/* Status Overlay */}
                    {isCompleted && (
                      <div className="absolute inset-0 bg-green-500/20 flex items-center justify-center">
                        <div className="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                          <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                      </div>
                    )}
                    {isError && (
                      <div className="absolute inset-0 bg-red-500/20 flex items-center justify-center">
                        <div className="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                          <X className="w-5 h-5 text-white" />
                        </div>
                      </div>
                    )}
                  </div>
                  
                  {/* Preview Actions */}
                  {!isUploadingPhoto && (
                    <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-lg">
                      <div className="flex items-center justify-center h-full space-x-2">
                        <button
                          onClick={() => removePreview(preview.id)}
                          className="p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                          title="Remove preview"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  )}
                  
                  {/* Status Badge */}
                  <div className={`absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium ${
                    isError 
                      ? 'bg-red-500 text-white' 
                      : isCompleted 
                        ? 'bg-green-500 text-white' 
                        : isUploadingPhoto
                          ? 'bg-blue-500 text-white'
                          : 'bg-yellow-500 text-white'
                  }`}>
                    {isError ? 'Failed' : isCompleted ? 'Complete' : isUploadingPhoto ? 'Uploading...' : 'Pending'}
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      )}


      {/* Photo Tips */}
      <div className="bg-muted/50 rounded-lg p-4">
        <h5 className="font-medium text-sm mb-2">Photo Tips</h5>
        <ul className="text-xs text-muted-foreground space-y-1">
          <li>• Use high-quality, well-lit photos</li>
          <li>• Include a clear face photo as your primary image</li>
          <li>• Show your personality and interests</li>
          <li>• Avoid group photos or heavily filtered images</li>
          <li>• Keep photos recent and authentic</li>
        </ul>
      </div>
    </div>
  )
}

// Photo Gallery Component for viewing photos
export function PhotoGallery({ 
  photos, 
  photoIds, 
  initialIndex = 0, 
  onClose,
  onDelete 
}: { 
  photos: string[]
  photoIds?: string[]
  initialIndex?: number
  onClose: () => void
  onDelete?: (photoId: string) => void
}) {
  const [currentIndex, setCurrentIndex] = useState(Math.max(0, Math.min(initialIndex, photos.length - 1)))

  const nextPhoto = () => {
    setCurrentIndex((prev) => (prev + 1) % photos.length)
  }

  const prevPhoto = () => {
    setCurrentIndex((prev) => (prev - 1 + photos.length) % photos.length)
  }

  const handleDelete = async () => {
    if (onDelete && photoIds && photoIds[currentIndex]) {
      const photoId = photoIds[currentIndex]
      try {
        await onDelete(photoId)
        // After deletion, the parent will update the photos array
        // We'll handle navigation in useEffect when photos array changes
      } catch (error) {
        console.error('Delete failed:', error)
      }
    }
  }
  
  // Use ref to avoid onClose dependency issues
  const onCloseRef = useRef(onClose)
  useEffect(() => {
    onCloseRef.current = onClose
  }, [onClose])

  // Update current index when photos array changes (e.g., after deletion)
  useEffect(() => {
    if (photos.length === 0) {
      // If no photos left, close gallery
      onCloseRef.current()
    } else if (currentIndex >= photos.length) {
      // If current index is out of bounds, move to last photo
      setCurrentIndex(Math.max(0, photos.length - 1))
    }
  }, [photos.length, currentIndex])

  if (photos.length === 0) {
    return null
  }

  return (
    <div 
      className="fixed inset-0 bg-black/90 z-50 flex items-center justify-center"
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        backgroundColor: 'rgba(0, 0, 0, 0.95)',
        zIndex: 9999,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
      }}
      onClick={onClose}
    >
      <div 
        className="relative w-full h-full p-4"
        style={{
          position: 'relative',
          width: '100%',
          height: '100%',
          padding: '1rem',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
        }}
        onClick={(e) => e.stopPropagation()}
      >
        {/* Close Button */}
        <button
          onClick={onClose}
          style={{
            position: 'absolute',
            top: '1.5rem',
            right: '1.5rem',
            zIndex: 10,
            padding: '0.75rem',
            backgroundColor: 'rgba(0, 0, 0, 0.7)',
            color: 'white',
            borderRadius: '50%',
            border: '2px solid rgba(255, 255, 255, 0.3)',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}
          onMouseEnter={(e) => {
            e.currentTarget.style.backgroundColor = 'rgba(0, 0, 0, 0.9)'
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.backgroundColor = 'rgba(0, 0, 0, 0.7)'
          }}
        >
          <X style={{ width: '24px', height: '24px', color: 'white' }} />
        </button>

        {/* Delete Button */}
        {onDelete && photoIds && photoIds[currentIndex] && (
          <button
            onClick={handleDelete}
            style={{
              position: 'absolute',
              top: '1.5rem',
              right: '5rem',
              zIndex: 10,
              padding: '0.75rem',
              backgroundColor: 'rgba(239, 68, 68, 0.8)',
              color: 'white',
              borderRadius: '50%',
              border: '2px solid rgba(255, 255, 255, 0.3)',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.backgroundColor = 'rgba(239, 68, 68, 1)'
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.backgroundColor = 'rgba(239, 68, 68, 0.8)'
            }}
            title="Delete photo"
          >
            <Trash2 style={{ width: '24px', height: '24px', color: 'white' }} />
          </button>
        )}

        {/* Photo */}
        <div 
          className="relative flex items-center justify-center"
          style={{
            width: '100vw',
            height: '100vh',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '2rem',
            boxSizing: 'border-box',
          }}
        >
          <img
            src={photos[currentIndex]}
            alt={`Photo ${currentIndex + 1}`}
            style={{
              maxWidth: 'calc(100vw - 4rem)',
              maxHeight: 'calc(100vh - 4rem)',
              width: 'auto',
              height: 'auto',
              minWidth: 'min(50vw, 800px)', // Minimum size for small images
              minHeight: 'min(50vh, 600px)', // Minimum size for small images
              objectFit: 'contain',
              display: 'block',
            }}
            onLoad={(e) => {
              // For small images, scale them up proportionally but cap at 2x natural size to avoid pixelation
              const img = e.currentTarget
              const naturalWidth = img.naturalWidth
              const naturalHeight = img.naturalHeight
              const maxDisplayWidth = window.innerWidth - 64 // Account for padding
              const maxDisplayHeight = window.innerHeight - 64
              
              // Only scale up if image is significantly smaller than viewport (less than 80%)
              if (naturalWidth < maxDisplayWidth * 0.8 && naturalHeight < maxDisplayHeight * 0.8) {
                // Scale up to fill at least 60% of viewport, but cap at 2x natural size to avoid pixelation
                const targetWidth = Math.min(maxDisplayWidth * 0.6, naturalWidth * 2)
                const targetHeight = Math.min(maxDisplayHeight * 0.6, naturalHeight * 2)
                const aspectRatio = naturalWidth / naturalHeight
                
                // Determine which dimension should be used based on aspect ratio
                if (targetWidth / targetHeight > aspectRatio) {
                  // Height is the limiting factor
                  img.style.width = 'auto'
                  img.style.height = `${targetHeight}px`
                } else {
                  // Width is the limiting factor
                  img.style.width = `${targetWidth}px`
                  img.style.height = 'auto'
                }
              } else {
                // Reset to auto sizing for larger images to use maxWidth/maxHeight constraints
                img.style.width = 'auto'
                img.style.height = 'auto'
              }
            }}
          />
        </div>

        {/* Navigation */}
        {photos.length > 1 && (
          <>
            <button
              onClick={prevPhoto}
              style={{
                position: 'absolute',
                left: '2rem',
                top: '50%',
                transform: 'translateY(-50%)',
                zIndex: 10,
                padding: '1rem',
                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                color: 'white',
                borderRadius: '50%',
                border: '2px solid rgba(255, 255, 255, 0.3)',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.2s',
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.backgroundColor = 'rgba(0, 0, 0, 0.9)'
                e.currentTarget.style.transform = 'translateY(-50%) scale(1.1)'
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = 'rgba(0, 0, 0, 0.7)'
                e.currentTarget.style.transform = 'translateY(-50%) scale(1)'
              }}
            >
              <ChevronLeft style={{ width: '32px', height: '32px', color: 'white' }} />
            </button>
            
            <button
              onClick={nextPhoto}
              style={{
                position: 'absolute',
                right: '2rem',
                top: '50%',
                transform: 'translateY(-50%)',
                zIndex: 10,
                padding: '1rem',
                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                color: 'white',
                borderRadius: '50%',
                border: '2px solid rgba(255, 255, 255, 0.3)',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.2s',
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.backgroundColor = 'rgba(0, 0, 0, 0.9)'
                e.currentTarget.style.transform = 'translateY(-50%) scale(1.1)'
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = 'rgba(0, 0, 0, 0.7)'
                e.currentTarget.style.transform = 'translateY(-50%) scale(1)'
              }}
            >
              <ChevronRight style={{ width: '32px', height: '32px', color: 'white' }} />
            </button>
          </>
        )}

        {/* Photo Counter */}
        <div style={{
          position: 'absolute',
          bottom: '2rem',
          left: '50%',
          transform: 'translateX(-50%)',
          backgroundColor: 'rgba(0, 0, 0, 0.7)',
          color: 'white',
          padding: '0.5rem 1rem',
          borderRadius: '9999px',
          fontSize: '0.875rem',
          border: '2px solid rgba(255, 255, 255, 0.3)',
          zIndex: 10,
        }}>
          {currentIndex + 1} / {photos.length}
        </div>
      </div>
    </div>
  )
}