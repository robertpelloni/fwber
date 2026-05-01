import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import OpenAI from 'openai';
import prisma from '../lib/prisma.js';

const router = Router();

const openai = new OpenAI({ 
  apiKey: process.env.OPENROUTER_API_KEY || process.env.OPENAI_API_KEY,
  baseURL: process.env.OPENROUTER_API_KEY ? 'https://openrouter.ai/api/v1' : undefined,
  defaultHeaders: process.env.OPENROUTER_API_KEY ? {
    'HTTP-Referer': 'https://www.fwber.me',
    'X-Title': 'fwber',
  } : undefined
});

router.use(authenticate);

/**
 * POST /api/content-generation/generate-bio
 * Generates an AI profile bio based on preferences
 */
router.post('/generate-bio', async (req: any, res) => {
  const userId = BigInt(req.user.id);
  const { personality, interests, goals, style, target_audience } = req.body;

  try {
    // AI Content Generation is free
    /*
    const user = await prisma.users.findUnique({ where: { id: userId } });
    if (!user || Number(user.token_balance) < 20) {
      return res.status(403).json({ error: 'Insufficient tokens. Generating a bio costs 20 FWB tokens.' });
    }

    // Deduct tokens
    await prisma.users.update({
      where: { id: userId },
      data: { token_balance: { decrement: 20 } }
    });
    */

    const model = process.env.OPENROUTER_API_KEY ? 'google/gemini-2.0-flash-lite-preview-02-05:free' : 'gpt-4o-mini';

    const prompt = `Create a compelling dating profile bio based on:
Personality: ${personality}
Interests: ${Array.isArray(interests) ? interests.join(', ') : interests}
Goals: ${goals}
Style: ${style}
Target Audience: ${target_audience}

Respond in JSON format: { "suggestions": [{ "content": "...", "provider": "ai", "confidence": 0.9, "safety_score": 0.99 }] }`;

    const completion = await openai.chat.completions.create({
      model: model,
      messages: [{ role: 'user', content: prompt }],
      response_format: { type: 'json_object' }
    });

    const result = JSON.parse(completion.choices[0]?.message?.content || '{}');
    
    res.json({
      success: true,
      data: result,
      user_id: req.user.id,
      generated_at: new Date().toISOString()
    });
  } catch (error) {
    console.error('[GENERATE_BIO_ERROR]', error);
    res.status(500).json({ error: 'Failed to generate bio' });
  }
});

/**
 * POST /api/content-generation/optimize
 * Optimizes existing content
 */
router.post('/optimize', async (req: any, res) => {
  const { content, optimization_types } = req.body;
  const model = process.env.OPENROUTER_API_KEY ? 'google/gemini-2.0-flash-lite-preview-02-05:free' : 'gpt-4o-mini';

  try {
    const prompt = `Optimize the following content for ${optimization_types?.join(', ') || 'engagement'}:
"${content}"

Respond in JSON format: { "optimized": "...", "overall_score": 0.9, "improvements": {} }`;

    const completion = await openai.chat.completions.create({
      model: model,
      messages: [{ role: 'user', content: prompt }],
      response_format: { type: 'json_object' }
    });

    const result = JSON.parse(completion.choices[0]?.message?.content || '{}');

    res.json({
      success: true,
      data: result,
      user_id: req.user.id,
      optimized_at: new Date().toISOString()
    });
  } catch (error) {
    console.error('[OPTIMIZE_CONTENT_ERROR]', error);
    res.status(500).json({ error: 'Failed to optimize content' });
  }
});

// GET /api/content-generation/stats
router.get('/stats', (_req, res) => {
  res.json({ success: true, data: { total_generations: 0, tokens_used: 0, remaining: 0 } });
});

// GET /api/content-generation/optimization-stats
router.get('/optimization-stats', (_req, res) => {
  res.json({ success: true, data: { optimization_score: 0, suggestions: [] } });
});

export default router;
