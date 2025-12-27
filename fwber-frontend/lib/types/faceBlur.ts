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
