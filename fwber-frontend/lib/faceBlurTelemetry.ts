import type { FaceBlurTelemetryMetadata, FileWithFaceBlurMetadata } from '@/lib/types/faceBlur'

// Re-export types for backward compatibility
export type { FaceBlurTelemetryMetadata, FileWithFaceBlurMetadata }

/**
 * Attaches face blur metadata to a file object.
 * This function is a pure utility and does not depend on other modules.
 */
export const attachFaceBlurMetadata = (
  file: File,
  metadata: FaceBlurTelemetryMetadata
): FileWithFaceBlurMetadata => {
  const typedFile = file as FileWithFaceBlurMetadata
  typedFile.faceBlurMetadata = metadata
  return typedFile
}
