import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { SentimentAnalysisService } from '../services/SentimentAnalysisService.js';
import { TokenDistributionService } from '../services/TokenDistributionService.js';
import { ActivityNotificationService } from '../services/ActivityNotificationService.js';

const router = Router();
router.use(authenticate);
const tokenService = new TokenDistributionService();

// GET /api/merchant/pulse/vibe — get neighborhood vibe based on recent artifacts
router.get('/pulse/vibe', async (req: any, res) => {
  try {
    const lat = Number(req.query.lat) || 42.33;
    const lng = Number(req.query.lng) || -83.05;

    // Count recent artifacts by type in the area
    const recent = await prisma.proximity_artifacts.findMany({
      where: { moderation_status: 'clean', expires_at: { gt: new Date() } },
      take: 50,
      orderBy: { created_at: 'desc' as const }
    });

    const total = recent.length;
    if (total === 0) {
      return res.json({ vibe: 'quiet', score: 0.3, confidence: 0.1, message: 'Not much activity nearby right now', artifact_count: 0 });
    }

    // Determine vibe from artifact types
    const shouts = recent.filter((a: any) => a.type === 'shout').length;
    const questions = recent.filter((a: any) => a.type === 'question').length;
    const events = recent.filter((a: any) => a.type === 'event').length;
    const recs = recent.filter((a: any) => a.type === 'recommendation').length;

    let heuristicVibe = 'neutral';
    let score = 0.5;
    if (events > 2) { heuristicVibe = 'hype'; score = 0.9; }
    else if (shouts > 3) { heuristicVibe = 'social'; score = 0.75; }
    else if (recs > 2) { heuristicVibe = 'chill'; score = 0.6; }
    else if (questions > 1) { heuristicVibe = 'curious'; score = 0.55; }

    // AI-driven neighborhood sentiment
    const aiAnalysis = await SentimentAnalysisService.analyzeNeighborhoodSentiment(lat, lng);

    res.json({
      business_name: 'Merchant', // Placeholder
      location: { lat, lng, radius: 5000 },
      analysis: {
        vibe: aiAnalysis.vibe !== 'Neutral' ? aiAnalysis.vibe : heuristicVibe,
        sentiment: aiAnalysis.sentiment || 0.5,
        trending_keywords: aiAnalysis.keywords || [],
        activity_score: total,
        summary: aiAnalysis.summary || `Heuristic detection: ${heuristicVibe}`,
        post_count: total,
        last_updated: new Date().toISOString()
      },
      heuristic_vibe: heuristicVibe,
      ai_raw: aiAnalysis
    });
  } catch (err: any) {
    res.json({ vibe: 'neutral', score: 0.5, confidence: 0, message: 'Vibe analysis not yet available' });
  }
});

// POST /api/merchant/pulse/broadcast — Broadcast a notification to nearby users based on vibe
router.post('/pulse/broadcast', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { content, vibe_target, discount_code, lat, lng } = req.body;
    const broadcastCost = 50;

    // 1. Check & Spend Tokens
    try {
      await tokenService.spendTokens(userId, broadcastCost, 'Vibe Pulse Broadcast');
    } catch (e: any) {
      return res.status(403).json({ error: e.message });
    }

    // 2. Vibe Check (if targeted)
    const currentLat = lat || 42.33;
    const currentLng = lng || -83.05;
    const aiAnalysis = await SentimentAnalysisService.analyzeNeighborhoodSentiment(currentLat, currentLng);

    if (vibe_target && vibe_target !== 'any' && aiAnalysis.vibe.toLowerCase() !== vibe_target.toLowerCase()) {
      return res.json({
        status: 'skipped',
        message: `Vibe mismatch. Target: ${vibe_target}, Current: ${aiAnalysis.vibe}`,
        current_vibe: aiAnalysis.vibe,
        token_cost: broadcastCost
      });
    }

    // 3. Identify nearby users (within ~1 mile / 1.6km)
    const nearbyUsers = await prisma.user_locations.findMany({
      where: {
        latitude: { gte: currentLat - 0.015, lte: currentLat + 0.015 },
        longitude: { gte: currentLng - 0.015, lte: currentLng + 0.015 },
        updated_at: { gte: new Date(Date.now() - 60 * 60 * 1000) } // Active in last hour
      },
      select: { user_id: true }
    });

    // 4. Send Notifications
    const broadcastId = `broadcast_${Date.now()}`;
    const notificationPromises = nearbyUsers.map(u =>
      ActivityNotificationService.notify(
        u.user_id,
        'Local Flash Deal',
        content,
        { type: 'merchant_broadcast', discount_code, vibe: aiAnalysis.vibe, broadcast_id: broadcastId }
      )
    );
    await Promise.all(notificationPromises);

    res.json({
      status: 'success',
      recipient_count: nearbyUsers.length,
      current_vibe: aiAnalysis.vibe,
      token_cost: broadcastCost
    });
  } catch (err: any) {
    console.error('[Merchant] Broadcast error:', err.message);
    res.status(500).json({ error: 'Failed to transmit broadcast' });
  }
});

export default router;
