import prisma from '../lib/prisma.js';
import type { geo_spoof_detections } from '@prisma/client';
import { AutonomousService } from './AutonomousService.js';

export interface IpIntelligenceData {
  latitude: number;
  longitude: number;
  isVpn: boolean;
  isDataCenter: boolean;
}

export class GeoSpoofDetectionService {
  /**
   * Detect potential geolocation spoofing.
   */
  async detectSpoof(userId: number, latitude: number, longitude: number, ipAddress: string, ipData?: IpIntelligenceData): Promise<geo_spoof_detections | null> {
    const detectionFlags: string[] = [];
    let suspicionScore = 0;
    let distanceKm = 0;
    let ipLat: number | null = null;
    let ipLon: number | null = null;

    if (ipData) {
      ipLat = ipData.latitude;
      ipLon = ipData.longitude;
      distanceKm = this.calculateDistance(latitude, longitude, ipLat, ipLon);
    } else {
      ipLat = latitude;
      ipLon = longitude;
      distanceKm = 0;
    }

    // Flag: Large distance from IP location
    if (distanceKm > 500) {
      detectionFlags.push('ip_distance_extreme');
      suspicionScore += 40;
    } else if (distanceKm > 200) {
      detectionFlags.push('ip_distance_high');
      suspicionScore += 25;
    } else if (distanceKm > 100) {
      detectionFlags.push('ip_distance_moderate');
      suspicionScore += 15;
    }

    // Check for impossible velocity (teleportation)
    const lastDetection = await prisma.geo_spoof_detections.findFirst({
      where: { user_id: userId },
      orderBy: { id: 'desc' }
    });

    let velocityKmh: number | null = null;
    if (lastDetection) {
      const diffSeconds = (new Date().getTime() - new Date(lastDetection.detected_at).getTime()) / 1000;
      const hours = Math.max(diffSeconds, 1) / 3600.0;
      const travelDistance = this.calculateDistance(Number(lastDetection.latitude), Number(lastDetection.longitude), latitude, longitude);

      velocityKmh = Math.round(travelDistance / hours);

      if (velocityKmh > 1000) {
        detectionFlags.push('impossible_velocity');
        suspicionScore += 50;
      } else if (velocityKmh > 500) {
        detectionFlags.push('suspicious_velocity');
        suspicionScore += 30;
      } else if (velocityKmh > 200) {
        detectionFlags.push('high_velocity');
        suspicionScore += 15;
      }
    }

    // Heuristic fallback: large coordinate jump
    if (velocityKmh === null && lastDetection) {
      const coordJumpKm = this.calculateDistance(Number(lastDetection.latitude), Number(lastDetection.longitude), latitude, longitude);
      if (coordJumpKm > 3000) {
        velocityKmh = Math.round(coordJumpKm);
        detectionFlags.push('impossible_velocity');
        suspicionScore += 50;
      }
    }

    // Indicators
    if (ipData?.isVpn) {
      detectionFlags.push('vpn_or_proxy');
      suspicionScore += 20;
    }
    if (ipData?.isDataCenter) {
      detectionFlags.push('datacenter_ip');
      suspicionScore += 15;
    }

    // Frequent changes
    const recentCount = await prisma.geo_spoof_detections.count({
      where: {
        user_id: userId,
        detected_at: { gte: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000) }
      }
    });
    if (recentCount > 10) {
      detectionFlags.push('frequent_location_changes');
      suspicionScore += 10;
    }

    if (detectionFlags.includes('impossible_velocity')) {
      suspicionScore = Math.max(suspicionScore, 50);
    }

    if (suspicionScore >= 25) {
      return await prisma.geo_spoof_detections.create({

      const detection = await prisma.geo_spoof_detections.create({
        data: {
          user_id: userId,
          ip_address: ipAddress,
          latitude,
          longitude,
          ip_latitude: ipLat,
          ip_longitude: ipLon,
          distance_km: Math.round(distanceKm),
          velocity_kmh: velocityKmh,
          suspicion_score: Math.min(100, suspicionScore),
          detection_flags: detectionFlags,
          is_confirmed_spoof: false,
          detected_at: new Date()
        }
      });
      await AutonomousService.logAction('Geo-Spoof Detected', 'Completed', {
        userId,
        suspicionScore,
        flags: detectionFlags,
        velocityKmh
      });

      return detection;
    }

    return null;
  }

  private calculateDistance(lat1: number, lon1: number, lat2: number, lon2: number): number {
    const R = 6371; // km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  }
}
