export interface FaceBlurTelemetryMetadata {
  facesDetected?: number
  blurApplied?: boolean
  processingTimeMs?: number
  skippedReason?: string
  originalFileName?: string
  processedFileName?: string
  warningMessage?: string
  previewId?: string
}

export type FileWithFaceBlurMetadata = File & {
  faceBlurMetadata?: FaceBlurTelemetryMetadata;
}

// Ensure the module has at least one value export to prevent "no exports" errors
export const FACE_BLUR_TYPES_VERSION = '1.0.0';
