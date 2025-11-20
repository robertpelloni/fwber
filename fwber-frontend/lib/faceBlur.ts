import * as faceapi from '@vladmandic/face-api'

const DEFAULT_MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/'

export type FaceBlurErrorCode = 'UNSUPPORTED_ENV' | 'MODEL_LOAD_FAILED' | 'PROCESSING_FAILED'

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

let modelLoadPromise: Promise<void> | null = null

const ensureModelsLoaded = async (modelUrl = DEFAULT_MODEL_URL) => {
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    throw new FaceBlurError('Face blur can only run inside the browser runtime.', 'UNSUPPORTED_ENV')
  }

  if (modelLoadPromise) {
    return modelLoadPromise
  }

  modelLoadPromise = (async () => {
    const tf = faceapi.tf as typeof import('@tensorflow/tfjs-core') | undefined
    if (!tf) {
      throw new FaceBlurError('Tensorflow backend unavailable for face blur.', 'MODEL_LOAD_FAILED')
    }

    const currentBackend = tf.getBackend?.()
    if (currentBackend !== 'webgl') {
      try {
        await tf.setBackend?.('webgl')
      } catch {
        await tf.setBackend?.('cpu')
      }
      await tf.ready?.()
    }

    await faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl)
  })()

  return modelLoadPromise
}

const loadImageSource = async (file: File) => {
  if (typeof createImageBitmap === 'function') {
    const bitmap = await createImageBitmap(file)
    return {
      source: bitmap as CanvasImageSource,
      width: bitmap.width,
      height: bitmap.height,
      cleanup: () => bitmap.close(),
    }
  }

  const objectUrl = URL.createObjectURL(file)
  const image = await new Promise<HTMLImageElement>((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = (event) => reject(event)
    img.src = objectUrl
  })

  return {
    source: image as CanvasImageSource,
    width: image.naturalWidth || image.width,
    height: image.naturalHeight || image.height,
    cleanup: () => URL.revokeObjectURL(objectUrl),
  }
}

const determineInputSize = (width: number, height: number) => {
  const largestSide = Math.max(width, height)
  if (largestSide >= 1920) return 832
  if (largestSide >= 1280) return 640
  if (largestSide >= 720) return 512
  return 416
}

const expandBox = (box: { x: number; y: number; width: number; height: number }, maxWidth: number, maxHeight: number) => {
  const marginRatio = 0.15
  const widthMargin = box.width * marginRatio
  const heightMargin = box.height * marginRatio

  const x = Math.max(0, box.x - widthMargin)
  const y = Math.max(0, box.y - heightMargin)
  const width = Math.min(maxWidth - x, box.width + widthMargin * 2)
  const height = Math.min(maxHeight - y, box.height + heightMargin * 2)

  return {
    x: Math.round(x),
    y: Math.round(y),
    width: Math.max(1, Math.round(width)),
    height: Math.max(1, Math.round(height)),
  }
}

const blurRegion = (
  ctx: CanvasRenderingContext2D,
  source: CanvasImageSource,
  region: { x: number; y: number; width: number; height: number },
  customRadius?: number
) => {
  const faceCanvas = document.createElement('canvas')
  faceCanvas.width = region.width
  faceCanvas.height = region.height
  const faceCtx = faceCanvas.getContext('2d')
  if (!faceCtx) return

  const blurRadius = customRadius ?? Math.max(8, Math.round(Math.min(region.width, region.height) / 8))
  faceCtx.filter = `blur(${blurRadius}px)`
  faceCtx.drawImage(source, region.x, region.y, region.width, region.height, 0, 0, region.width, region.height)

  ctx.drawImage(faceCanvas, region.x, region.y, region.width, region.height)
}

const canvasToBlob = (canvas: HTMLCanvasElement, mimeType: string) =>
  new Promise<Blob>((resolve, reject) => {
    const preferredType = mimeType === 'image/png' ? 'image/png' : 'image/jpeg'
    const quality = preferredType === 'image/png' ? undefined : 0.92
    canvas.toBlob((blob) => {
      if (blob) {
        resolve(blob)
      } else {
        reject(new Error('Unable to encode canvas to blob'))
      }
    }, preferredType, quality)
  })

const withSuffix = (fileName: string, suffix: string) => {
  const lastDot = fileName.lastIndexOf('.')
  if (lastDot === -1) {
    return `${fileName}${suffix}`
  }
  return `${fileName.slice(0, lastDot)}${suffix}${fileName.slice(lastDot)}`
}

export const blurFacesOnFile = async (file: File, options?: FaceBlurOptions): Promise<FaceBlurResult> => {
  if (!(file instanceof File)) {
    throw new FaceBlurError('Only browser File objects are supported.', 'PROCESSING_FAILED')
  }

  const startedAt = typeof performance !== 'undefined' && typeof performance.now === 'function'
    ? performance.now()
    : Date.now()

  try {
    await ensureModelsLoaded(options?.modelUrl)
  } catch (error) {
    if (error instanceof FaceBlurError) {
      throw error
    }
    throw new FaceBlurError('Unable to initialize face blur models.', 'MODEL_LOAD_FAILED', error)
  }

  const { source, width, height, cleanup } = await loadImageSource(file)

  try {
    const canvas = document.createElement('canvas')
    canvas.width = width
    canvas.height = height
    const ctx = canvas.getContext('2d')
    if (!ctx) {
      throw new FaceBlurError('Canvas rendering context unavailable.', 'PROCESSING_FAILED')
    }

    ctx.drawImage(source, 0, 0, width, height)

    const detections = await faceapi.detectAllFaces(
      canvas,
      new faceapi.TinyFaceDetectorOptions({
        inputSize: determineInputSize(width, height),
        scoreThreshold: options?.scoreThreshold ?? 0.4,
      })
    )

    const processingTimeMs = Math.max(0, Math.round(((typeof performance !== 'undefined' && typeof performance.now === 'function'
      ? performance.now()
      : Date.now()) - startedAt)))

    if (!detections.length) {
      return { file, facesFound: 0, blurred: false, processingTimeMs }
    }

    detections.forEach((detection) => {
      const region = expandBox(detection.box, width, height)
      blurRegion(ctx, source, region, options?.blurRadius)
    })

    const blob = await canvasToBlob(canvas, file.type || 'image/jpeg')
    const blurredFile = new File([blob], withSuffix(file.name, '-blurred'), { type: blob.type })
    return { file: blurredFile, facesFound: detections.length, blurred: true, processingTimeMs }
  } catch (error) {
    if (error instanceof FaceBlurError) {
      throw error
    }
    throw new FaceBlurError('Face blur failed for this photo.', 'PROCESSING_FAILED', error)
  } finally {
    cleanup()
  }
}
