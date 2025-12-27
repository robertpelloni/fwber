import { useEffect, useRef, useState, useCallback } from 'react';
import { loadModels, FaceBlurError } from '@/lib/faceblur';
import { isFeatureEnabled } from '@/lib/featureFlags';

// Dynamic import type for face-api
type FaceApi = typeof import('@vladmandic/face-api');

interface UseVideoFaceBlurResult {
  processedStream: MediaStream | null;
  isBlurEnabled: boolean;
  toggleBlur: () => void;
  isModelLoaded: boolean;
  error: Error | null;
}

export function useVideoFaceBlur(originalStream: MediaStream | null): UseVideoFaceBlurResult {
  const [processedStream, setProcessedStream] = useState<MediaStream | null>(null);
  const [isBlurEnabled, setIsBlurEnabled] = useState(false);
  const [isModelLoaded, setIsModelLoaded] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  const isBlurEnabledRef = useRef(isBlurEnabled);
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const requestRef = useRef<number>();
  const detectionRef = useRef<any[]>([]);
  const lastDetectionTimeRef = useRef<number>(0);
  const faceApiRef = useRef<FaceApi | null>(null);

  // Sync ref
  useEffect(() => {
    isBlurEnabledRef.current = isBlurEnabled;
  }, [isBlurEnabled]);

  // Initialize models
  useEffect(() => {
    if (!isFeatureEnabled('clientFaceBlur')) return;

    let mounted = true;

    const init = async () => {
      try {
        await loadModels();
        if (mounted) {
          // We need to import it here to get the instance
          faceApiRef.current = await import('@vladmandic/face-api');
          setIsModelLoaded(true);
        }
      } catch (err) {
        console.error('Failed to load face blur models:', err);
        if (mounted) setError(err instanceof Error ? err : new Error('Failed to load models'));
      }
    };

    init();

    return () => {
      mounted = false;
    };
  }, []);

  // Processing loop
  const processFrame = useCallback(() => {
    if (!videoRef.current || !canvasRef.current || !faceApiRef.current) return;

    const video = videoRef.current;
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    const api = faceApiRef.current;

    if (!ctx || video.readyState !== 4) {
      requestRef.current = requestAnimationFrame(processFrame);
      return;
    }

    // Match canvas size to video
    if (canvas.width !== video.videoWidth || canvas.height !== video.videoHeight) {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
    }

    // Draw original frame
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    if (isBlurEnabledRef.current && isModelLoaded) {
      // Run detection periodically (every 200ms) to save performance
      const now = performance.now();
      if (now - lastDetectionTimeRef.current > 200) {
        // Async detection - don't await here to avoid blocking render loop
        // We use the tinyFaceDetector for speed in video
        const options = new api.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
        
        api.detectAllFaces(video, options).then(detections => {
          detectionRef.current = detections;
          lastDetectionTimeRef.current = now;
        }).catch(err => console.error('Face detection error:', err));
      }

      // Apply blur based on last known detections
      detectionRef.current.forEach(detection => {
        const { x, y, width, height } = detection.box;
        
        ctx.save();
        ctx.beginPath();
        // Ellipse for face
        ctx.ellipse(
            x + width / 2, 
            y + height / 2, 
            width / 2 * 1.2, // slightly wider
            height / 2 * 1.4, // slightly taller
            0, 0, 2 * Math.PI
        );
        ctx.clip();
        ctx.filter = 'blur(20px)';
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        ctx.restore();
      });
    }

    requestRef.current = requestAnimationFrame(processFrame);
  }, [isModelLoaded]); // Removed isBlurEnabled dependency

  // Setup stream processing
  useEffect(() => {
    if (!originalStream) {
      setProcessedStream(null);
      return;
    }

    // Create hidden video element to play the source stream
    const video = document.createElement('video');
    video.srcObject = originalStream;
    video.muted = true;
    video.playsInline = true;
    video.autoplay = true;
    videoRef.current = video;

    // Create canvas for processing
    const canvas = document.createElement('canvas');
    canvasRef.current = canvas;

    // Start processing loop
    video.onloadedmetadata = () => {
        video.play().then(() => {
            requestRef.current = requestAnimationFrame(processFrame);
        }).catch(e => console.error('Error playing hidden video:', e));
    };

    // Capture stream from canvas (30 FPS)
    const stream = canvas.captureStream(30);
    
    // Add audio track from original stream
    originalStream.getAudioTracks().forEach(track => {
      stream.addTrack(track);
    });

    setProcessedStream(stream);

    return () => {
      if (requestRef.current) cancelAnimationFrame(requestRef.current);
      video.pause();
      video.srcObject = null;
      video.remove();
    };
  }, [originalStream, processFrame]);

  const toggleBlur = useCallback(() => {
    setIsBlurEnabled(prev => !prev);
  }, []);

  return {
    processedStream,
    isBlurEnabled,
    toggleBlur,
    isModelLoaded,
    error
  };
}
