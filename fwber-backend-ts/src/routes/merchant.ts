import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { SentimentAnalysisService } from '../services/SentimentAnalysisService.js';

const router = Router();
router.use(authenticate);

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
    const aiVibe = await SentimentAnalysisService.analyzeNeighborhoodSentiment(lat, lng);

    res.json({
      vibe: aiVibe !== 'Neutral' ? aiVibe : heuristicVibe,
      score,
      confidence: Math.min(total / 10, 1),
      message: `${total} recent signals nearby. AI vibe: ${aiVibe}`,
      artifact_count: total,
      heuristic_vibe: heuristicVibe,
      ai_vibe: aiVibe,
      breakdown: { shouts, questions, events, recommendations: recs }
    });
  } catch (err: any) {
    res.json({ vibe: 'neutral', score: 0.5, confidence: 0, message: 'Vibe analysis not yet available' });
  }
});

export default router;
