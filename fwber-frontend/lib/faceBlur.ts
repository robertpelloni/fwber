// import type * as FaceApi from '@vladmandic/face-api'

// let faceapi: typeof FaceApi | null = null

// const loadFaceApi = async () => {
//   if (!faceapi) {
//     faceapi = await import('@vladmandic/face-api')
//   }
//   return faceapi
// }

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

export const blurFacesOnFile = async (file: File, options?: FaceBlurOptions): Promise<FaceBlurResult> => {
  console.warn('Face blur is temporarily disabled due to build issues.')
  return { file, facesFound: 0, blurred: false, processingTimeMs: 0 }
}

