import type { FaceBlurTelemetryMetadata, FileWithFaceBlurMetadata } from '@/lib/types/faceBlur'

export type { FaceBlurTelemetryMetadata, FileWithFaceBlurMetadata }

export const attachFaceBlurMetadata = (
  file: File,
  metadata: FaceBlurTelemetryMetadata
): FileWithFaceBlurMetadata => {
  const typedFile = file as FileWithFaceBlurMetadata
  typedFile.faceBlurMetadata = metadata
  return typedFile
}
