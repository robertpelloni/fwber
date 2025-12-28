// Dynamic import type for face-api
type FaceApi = typeof import('@vladmandic/face-api')

let faceapi: FaceApi | null = null

async function getFaceApi(): Promise<FaceApi> {
  if (faceapi) return faceapi
  
  if (typeof window === 'undefined') {
    throw new Error('face-api can only be loaded in the browser')
  }

  // Dynamic import to avoid SSR issues
  faceapi = await import('@vladmandic/face-api')
  return faceapi
}

export type FaceBlurErrorCode = 'UNSUPPORTED_ENV' | 'MODEL_LOAD_FAILED' | 'PROCESSING_FAILED' | 'CANVAS_ERROR' | 'BLOB_ERROR'

export class FaceBlurError extends Error {
  code: FaceBlurErrorCode

  constructor(message: string, code: FaceBlurErrorCode, cause?: unknown) {
    super(message)
    this.name = 'FaceBlurError'
    this.code = code
    if (cause) {
      try {
        // `cause` is not yet supported in our TS target, so assign manually
        Object.defineProperty(this, 'cause', { value: cause })
      } catch {
        // noop
      }
    }
  }
}

let modelsLoaded = false
const MODEL_BASE_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/'

export async function loadModels() {
  if (modelsLoaded) return

  try {
    const api = await getFaceApi()
    
    await Promise.all([
      api.nets.ssdMobilenetv1.loadFromUri(MODEL_BASE_URL),
      api.nets.faceLandmark68Net.loadFromUri(MODEL_BASE_URL),
    ])
    
    modelsLoaded = true
  } catch (error) {
    console.error('Failed to load face detection models:', error)
    throw new FaceBlurError('Failed to load face detection models', 'MODEL_LOAD_FAILED', error)
  }
}

export interface FaceBlurOptions {
  blurRadius?: number
  scoreThreshold?: number
  modelUrl?: string
}

export interface FaceBlurResult {
  file: File
  facesFound: number
  blurred: boolean
  processingTimeMs: number
}

export const blurFacesOnFile = async (file: File, options?: FaceBlurOptions): Promise<FaceBlurResult> => {
  const startTime = performance.now()

  if (typeof window === 'undefined') {
    throw new FaceBlurError('Face blur only supported in browser', 'UNSUPPORTED_ENV')
  }

  try {
    await loadModels()
    const api = await getFaceApi()

    if (!faceapi) {
      throw new FaceBlurError('FaceAPI module not loaded', 'MODEL_LOAD_FAILED')
    }

    // Create an image element from the file
    const img = await api.bufferToImage(file)
    
    // Detect faces
    // ssdMobilenetv1 is slower but more accurate than tinyFaceDetector
    const detections = await api.detectAllFaces(img).withFaceLandmarks()
    
    const facesFound = detections.length

    if (facesFound === 0) {
      return {
        file,
        facesFound: 0,
        blurred: false,
        processingTimeMs: performance.now() - startTime
      }
    }

    // Create canvas to draw blurred image
    const canvas = document.createElement('canvas')
    canvas.width = img.width
    canvas.height = img.height
    const ctx = canvas.getContext('2d')
    
    if (!ctx) {
      throw new FaceBlurError('Could not get canvas context', 'CANVAS_ERROR')
    }

    // Draw original image
    ctx.drawImage(img, 0, 0)

    // Apply blur to detected faces
    detections.forEach(detection => {
      const { x, y, width, height } = detection.detection.box
      
      // Expand the box slightly to ensure full coverage
      const padding = width * 0.1
      const paddedX = Math.max(0, x - padding)
      const paddedY = Math.max(0, y - padding)
      const paddedWidth = Math.min(img.width - paddedX, width + padding * 2)
      const paddedHeight = Math.min(img.height - paddedY, height + padding * 2)

      ctx.save()
      ctx.beginPath()
      ctx.rect(paddedX, paddedY, paddedWidth, paddedHeight)
      ctx.clip()
      ctx.filter = `blur(${options?.blurRadius || 20}px)`
      ctx.drawImage(img, 0, 0)
      ctx.restore()
    })

    // Convert canvas back to file
    const blob = await new Promise<Blob | null>(resolve => canvas.toBlob(resolve, file.type, 0.9))
    
    if (!blob) {
      throw new FaceBlurError('Failed to create blob from canvas', 'BLOB_ERROR')
    }

    const processedFile = new File([blob], file.name, { type: file.type, lastModified: Date.now() })

    return {
      file: processedFile,
      facesFound,
      blurred: true,
      processingTimeMs: performance.now() - startTime
    }

  } catch (error) {
    console.error('Face blur processing error:', error)
    if (error instanceof FaceBlurError) throw error
    throw new FaceBlurError('Failed to process image', 'PROCESSING_FAILED', error)
  }
}

