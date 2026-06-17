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
    const analysis = await SentimentAnalysisService.analyzeNeighborhoodSentiment(lat, lng);
    const { vibe, keywords } = analysis;

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
      current_keywords: keywords,
      matched_promotions: recentPromos.map(p => ({
        ...p,
        vibe_affinity: this.calculateAffinity(vibe, keywords || [], (p.metadata as any)?.vibe, (p.metadata as any)?.keywords)
      }))
    };
  }

  private static calculateAffinity(currentVibe: string, currentKeywords: string[], targetVibe?: string, targetKeywords?: string[]) {
    let score = 0.5;

    // Vibe match
    if (targetVibe) {
        score = currentVibe.toLowerCase() === targetVibe.toLowerCase() ? 0.8 : 0.2;
    }

    // Keyword overlap boost
    if (targetKeywords && currentKeywords.length > 0) {
        const overlap = targetKeywords.filter(k => currentKeywords.some(ck => ck.toLowerCase() === k.toLowerCase()));
        if (overlap.length > 0) {
            score += (overlap.length * 0.1);
        }
    }

    return Math.min(score, 1.0);
  }
}
