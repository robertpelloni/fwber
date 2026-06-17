import prisma from '../lib/prisma.js';
import { SentimentAnalysisService } from './SentimentAnalysisService.js';

export class PromotionService {
  /**
   * Returns historical vibe data for a specific location.
   */
  static async getVibeHistory(lat: number, lng: number, days: number = 7) {
    const startDate = new Date(Date.now() - days * 24 * 60 * 60 * 1000);

    // We analyze the log of autonomous emotional state updates in that area
    // In this simulation, we'll return a trend based on recorded actions.
    const logs = await prisma.autonomous_actions.findMany({
      where: {
        task: 'Emotional State Update',
        created_at: { gte: startDate }
      },
      orderBy: { created_at: 'asc' },
      take: 50
    });

    return logs.map(log => ({
      timestamp: log.created_at,
      vibe: (log.metadata as any)?.emotion || 'Neutral',
      sentiment: Math.random() // Simulating sentiment variance
    }));
  }

  /**
   * Matches active promotions with the current neighborhood vibe.
   */
  static async getVibeMatchedPromotions(lat: number, lng: number) {
    const vibe = await SentimentAnalysisService.analyzeNeighborhoodSentiment(lat, lng);

    // In a real scenario, we'd query a 'promotions' table with a 'target_vibe' field.
    // Since our schema uses 'promotion_events' as a log, we'll simulate affinity logic.
    const recentPromos = await prisma.promotion_events.findMany({
      where: {
        created_at: { gte: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000) }
      },
      take: 10
    });

    return {
      current_vibe: vibe,
      matched_promotions: recentPromos.map(p => ({
        ...p,
        vibe_affinity: this.calculateAffinity(vibe, (p.metadata as any)?.vibe)
      }))
    };
  }

  private static calculateAffinity(currentVibe: string, targetVibe?: string) {
    if (!targetVibe) return 0.5;
    return currentVibe.toLowerCase() === targetVibe.toLowerCase() ? 1.0 : 0.2;
  }
}
