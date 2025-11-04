'use client';

import { useState, useEffect, useCallback } from 'react';

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

  const handleSuccess = (position: GeolocationPosition) => {
    setLocation({
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      accuracy: position.coords.accuracy,
      altitude: position.coords.altitude,
      altitudeAccuracy: position.coords.altitudeAccuracy,
      heading: position.coords.heading,
      speed: position.coords.speed,
    });
    setError(null);
    setLoading(false);
  };

  const handleError = (err: GeolocationPositionError) => {
    setError({
      code: err.code,
      message: err.message,
    });
    setLocation(null);
    setLoading(false);
  };

  const refetch = () => {
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
  };

  useEffect(() => {
    refetch();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [enableHighAccuracy, timeout, maximumAge, watch]);

  return { location, error, loading, refetch };
}
