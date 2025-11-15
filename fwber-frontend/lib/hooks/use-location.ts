'use client';

import { useState, useEffect, useCallback } from 'react';
import { logLocation } from '@/lib/logger';

export interface Location {
  latitude: number;
  longitude: number;
  accuracy?: number;
  altitude?: number | null;
  altitudeAccuracy?: number | null;
  heading?: number | null;
  speed?: number | null;
}

export interface LocationError {
  code: number;
  message: string;
}

export interface UseLocationOptions {
  enableHighAccuracy?: boolean;
  timeout?: number;
  maximumAge?: number;
  watch?: boolean;
}

export interface UseLocationReturn {
  location: Location | null;
  error: LocationError | null;
  loading: boolean;
  refetch: () => void;
}

export function useLocation(options: UseLocationOptions = {}): UseLocationReturn {
  const [location, setLocation] = useState<Location | null>(null);
  const [error, setError] = useState<LocationError | null>(null);
  const [loading, setLoading] = useState(true);

  const {
    enableHighAccuracy = true,
    timeout = 10000,
    maximumAge = 0,
    watch = false,
  } = options;

  const handleSuccess = useCallback((position: GeolocationPosition) => {
    const coords = {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
    };

    setLocation({
      ...coords,
      accuracy: position.coords.accuracy,
      altitude: position.coords.altitude,
      altitudeAccuracy: position.coords.altitudeAccuracy,
      heading: position.coords.heading,
      speed: position.coords.speed,
    });
    setError(null);
    setLoading(false);

    logLocation.permissionGranted(coords);
  }, []);

  const handleError = useCallback((err: GeolocationPositionError) => {
    const error = {
      code: err.code,
      message: err.message,
    };

    setError(error);
    setLocation(null);
    setLoading(false);

    logLocation.permissionDenied(error);
  }, []);

  const refetch = useCallback(() => {
    setLoading(true);
    setError(null);

    if (!navigator.geolocation) {
      setError({
        code: 0,
        message: 'Geolocation is not supported by this browser.',
      });
      setLoading(false);
      return;
    }

    const geoOptions: PositionOptions = {
      enableHighAccuracy,
      timeout,
      maximumAge,
    };

    if (watch) {
      const watchId = navigator.geolocation.watchPosition(
        handleSuccess,
        handleError,
        geoOptions
      );
      return () => navigator.geolocation.clearWatch(watchId);
    } else {
      navigator.geolocation.getCurrentPosition(
        handleSuccess,
        handleError,
        geoOptions
      );
    }
  }, [enableHighAccuracy, timeout, maximumAge, watch, handleSuccess, handleError]);

  useEffect(() => {
    refetch();
  }, [refetch]);

  return { location, error, loading, refetch };
}
