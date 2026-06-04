import axios from 'axios';
import * as h3 from 'h3-js';
import redis from '../lib/redis.js';
import { AutonomousTaskExecutor } from './AutonomousTaskExecutor.js';

export class GeoScreenerService {
  private enabled: boolean;
  private baseUrl: string;
  private timeout: number;
  private bloomEnabled: boolean;

  constructor() {
    this.enabled = process.env.GEO_SCREENER_ENABLED === 'true' || false;
    this.baseUrl = (process.env.GEO_SCREENER_URL || 'http://127.0.0.1:8081').replace(/\/$/, '');
    this.timeout = parseInt(process.env.GEO_SCREENER_TIMEOUT || '2000');
    this.bloomEnabled = process.env.GEO_BLOOM_ENABLED !== 'false';
  }

  /**
   * Index a user's location in the Rust H3 spatial index and the local Bloom filter.
   */
  async indexLocation(userId: number, lat: number, lng: number): Promise<boolean> {
    if (!this.enabled) return false;

    return AutonomousTaskExecutor.execute(
      { type: 'Location Indexing', impact: 'low', module: 'Proximity' },
      async () => {
        // 1. Mark the cell as active in Redis (Bloom Filter proxy)
        if (this.bloomEnabled) {
          try {
            const h3Index = h3.latLngToCell(lat, lng, 7);
            const cellKey = `geo:active_cells:res7`;
            await redis.sadd(cellKey, h3Index);
            await redis.expire(cellKey, 86400); // 24 hours
          } catch (error) {
            console.warn(`[GeoBloom] Failed to update active cells: ${(error as Error).message}`);
          }
        }

        // 2. Index in Rust service
        try {
          const response = await axios.post(`${this.baseUrl}/index`, {
            user_id: userId,
            lat,
            lng,
          }, { timeout: this.timeout });

          return response.status === 200;
        } catch (error) {
          console.warn(`[GeoScreener] Failed to index location for user ${userId}: ${(error as Error).message}`);
          return false;
        }
      }
    );
  }

  /**
   * Get nearby user IDs from the Rust H3 spatial index with a Bloom Filter shortcut.
   */
  async getNearbyUserIds(lat: number, lng: number, radiusMeters: number): Promise<number[] | null> {
    if (!this.enabled) return null;

    // 1. Check Bloom Filter (Active Cells Set)
    if (this.bloomEnabled) {
      try {
        const h3Index = h3.latLngToCell(lat, lng, 7);
        const isMember = await redis.sismember(`geo:active_cells:res7`, h3Index);
        
        // Optimization: If the primary cell is empty, skip expensive HTTP call
        if (!isMember) return [];
      } catch (error) {
        console.warn(`[GeoBloom] Check failed, falling back to full query: ${(error as Error).message}`);
      }
    }

    // 2. Full query to Rust service
    try {
      const response = await axios.get(`${this.baseUrl}/nearby`, {
        params: { lat, lng, radius_m: radiusMeters },
        timeout: this.timeout
      });

      if (response.status === 200) {
        const users = response.data.users || [];
        if (users.length > 50) {
          await AutonomousTaskExecutor.execute(
            { type: 'High Density Proximity', impact: 'low', module: 'Proximity' },
            async () => ({ lat, lng, radiusMeters, user_count: users.length })
          );
        }
        return users;
      }
      return null;
    } catch (error) {
      console.error(`[GeoScreener] Failed to fetch nearby users: ${(error as Error).message}`);
      return null;
    }
  }
}
