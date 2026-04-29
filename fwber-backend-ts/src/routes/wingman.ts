import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import * as ai from '../lib/wingman-ai.js';
import prisma from '../lib/prisma.js';
import OpenAI from 'openai';

const openai = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });

const router = Router();

// Helper: store result so share links work
async function storeResult(userId: bigint, type: string, content: any): Promise<string> {
  const shareId = `${type.slice(0, 4)}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
  try {
    await prisma.viral_contents.create({
      data: {
        id: shareId,
        user_id: userId,
        type,
        content: JSON.stringify(content),
      },
    });
  } catch (err: any) {
    console.error('[storeResult]', err.message);
  }
  return shareId;
}

router.use(authenticate);

// ─── Roast / Hype ───────────────────────────────────────────────────────────

router.post('/roast', async (req: any, res) => {
  try {
    const mode = req.body?.mode === 'hype' ? 'hype' : 'roast';
    const roast = await ai.generateRoast(BigInt(req.user.id), mode);
    const userId = BigInt(req.user.id);
    const shareId = await storeResult(userId, mode, { text: roast, user_name: req.user?.name });
    res.json({ roast, share_id: shareId });
  } catch (err: any) {
    console.error('[wingman/roast]', err.message);
    res.json({ roast: 'Your profile is like a parking ticket — technically valid but nobody wants to deal with it.', share_id: `err-${Date.now()}` });
  }
});

// ─── Vibe Check ─────────────────────────────────────────────────────────────

router.get('/vibe-check', async (req: any, res) => {
  try {
    const result = await ai.generateVibeCheck(BigInt(req.user.id));
    const shareId = await storeResult(BigInt(req.user.id), 'vibe', { ...result, user_name: req.user?.name });
    res.json({ ...result, share_id: shareId });
  } catch (err: any) {
    console.error('[wingman/vibe-check]', err.message);
    res.json({ green_flags: ['Bold enough to be here'], red_flags: ['Profile might be haunted'], share_id: `err-${Date.now()}` });
  }
});

// ─── Quirk Check ────────────────────────────────────────────────────────────

router.post('/quirk-check', async (req: any, res) => {
  try {
    const quirk = req.body?.quirk || '';
    if (!quirk.trim()) { res.json({ flag_type: 'Beige Flag', reason: 'Tell us your quirk first!', emoji: '🤷' }); return; }
    const result = await ai.generateQuirkCheck(quirk);
    res.json({ ...result, share_id: `quirk-${Date.now()}-${Math.random().toString(36).slice(2, 8)}` });
  } catch (err: any) {
    console.error('[wingman/quirk-check]', err.message);
    res.json({ flag_type: 'Beige Flag', reason: "Our AI is having a moment. Try again.", emoji: '🤷' });
  }
});

// ─── Cosmic Match ───────────────────────────────────────────────────────────

router.get('/cosmic-match', async (req: any, res) => {
  try {
    const result = await ai.generateCosmicMatch(BigInt(req.user.id));
    const shareId = await storeResult(BigInt(req.user.id), 'cosmic', { ...result, user_name: req.user?.name });
    res.json({ ...result, share_id: shareId });
  } catch (err: any) {
    console.error('[wingman/cosmic-match]', err.message);
    res.json({ best_match: 'A creative soul', best_reason: 'The stars are cloudy.', worst_match: 'A energy vampire', worst_reason: 'Trust the universe.', share_id: `err-${Date.now()}` });
  }
});

// ─── Nemesis ────────────────────────────────────────────────────────────────

router.get('/nemesis', async (req: any, res) => {
  try {
    const result = await ai.generateNemesis(BigInt(req.user.id));
    const shareId = await storeResult(BigInt(req.user.id), 'nemesis', { ...result, user_name: req.user?.name });
    res.json({ ...result, share_id: shareId });
  } catch (err: any) {
    console.error('[wingman/nemesis]', err.message);
    res.json({ nemesis_type: 'The Chronically Basic', clashing_traits: ['AI malfunction'], why_it_would_fail: 'Our oracle is resting.', scientific_explanation: 'Error 503: Psychic overload.' });
  }
});

// ─── Dating Fortune ─────────────────────────────────────────────────────────

router.get('/fortune', async (req: any, res) => {
  try {
    const result = await ai.generateFortune(BigInt(req.user.id));
    const shareId = await storeResult(BigInt(req.user.id), 'fortune', { text: result.fortune, user_name: req.user?.name });
    res.json({ ...result, share_id: shareId });
  } catch (err: any) {
    console.error('[wingman/fortune]', err.message);
    res.json({ fortune: 'The stars are resting. Try again later.' });
  }
});

// ─── Profile Analysis ───────────────────────────────────────────────────────

router.get('/profile-analysis', async (req: any, res) => {
  try {
    const result = await ai.generateProfileAnalysis(BigInt(req.user.id));
    res.json(result);
  } catch (err: any) {
    console.error('[wingman/profile-analysis]', err.message);
    res.json({ score: 50, strengths: ['Profile exists'], weaknesses: ['AI is resting'], tips: ['Try again in a moment'] });
  }
});

// ─── Date Ideas ─────────────────────────────────────────────────────────────

router.get('/date-ideas/:matchId', async (req: any, res) => {
  try {
    const result = await openai.chat.completions.create({
      model: 'gpt-4o-mini',
      messages: [{
        role: 'user',
        content: `Generate 5 creative date ideas for a couple in the Detroit metro area. Each should have a title, short description (1 sentence), vibe (one word), and estimated cost ($ to $$$). Respond in JSON: { "ideas": [{ "title": "...", "description": "...", "vibe": "...", "estimated_cost": "..." }] }`
      }],
      temperature: 0.9,
      max_tokens: 600,
    });
    const text = result.choices[0]?.message?.content?.trim() || '';
    const cleaned = text.replace(/```json\n?/g, '').replace(/```/g, '').trim();
    res.json(JSON.parse(cleaned));
  } catch (err: any) {
    console.error('[wingman/date-ideas]', err.message);
    res.json({ ideas: [
      { title: 'Sunset at Belle Isle', description: 'Pack a blanket and watch the skyline.', vibe: 'Romantic', estimated_cost: 'Free' },
      { title: 'Jazz at Cliff Bell\'s', description: 'Classic Detroit with live jazz.', vibe: 'Sophisticated', estimated_cost: '$$' },
      { title: 'Arcade Night at Offworld', description: 'Retro games and pizza.', vibe: 'Playful', estimated_cost: '$' },
      { title: 'Eastern Market Morning', description: 'Browse vendors and grab coffee.', vibe: 'Casual', estimated_cost: '$' },
      { title: 'Detroit Institute of Arts', description: 'Explore world-class art together.', vibe: 'Cultural', estimated_cost: '$' },
    ] });
  }
});

// ─── Ice Breakers ───────────────────────────────────────────────────────────

router.get('/ice-breakers/:matchId', async (req: any, res) => {
  try {
    const suggestion = await openai.chat.completions.create({
      model: 'gpt-4o-mini',
      messages: [{ role: 'user', content: 'Generate one clever, unique dating ice breaker message. Just the message text, nothing else. Be specific and creative — avoid clichés.' }],
      temperature: 0.95,
      max_tokens: 100,
    });
    res.json({ suggestion: suggestion.choices[0]?.message?.content?.trim() || 'What\'s the most spontaneous thing you\'ve done this week?' });
  } catch (err: any) {
    res.json({ suggestion: 'Ask about their favorite local spot — it always sparks a great conversation.' });
  }
});

// ─── Reply Suggestions ──────────────────────────────────────────────────────

router.get('/replies/:matchId', async (req: any, res) => {
  try {
    const suggestion = await openai.chat.completions.create({
      model: 'gpt-4o-mini',
      messages: [{ role: 'user', content: 'Generate one clever, flirty dating reply suggestion. Just the message text. Be witty and engaging.' }],
      temperature: 0.95,
      max_tokens: 100,
    });
    res.json({ suggestion: suggestion.choices[0]?.message?.content?.trim() || 'That\'s awesome! What made you choose that?' });
  } catch (err: any) {
    res.json({ suggestion: 'That\'s awesome! What made you choose that?' });
  }
});

// ─── Compatibility Audit ────────────────────────────────────────────────────

router.post('/compatibility-audit/:targetId', async (req: any, res) => {
  try {
    const prisma = (await import('../lib/prisma.js')).default;
    const myProfile = await prisma.user_profiles.findFirst({ where: { user_id: BigInt(req.user.id) } });
    const theirProfile = await prisma.user_profiles.findFirst({ where: { user_id: BigInt(req.params.targetId) } });

    let prompt = 'Compare these two dating profiles and give a compatibility score.';
    if (myProfile?.bio) prompt += `\n\nProfile A bio: ${myProfile.bio}`;
    if (theirProfile?.bio) prompt += `\n\nProfile B bio: ${theirProfile.bio}`;
    if (myProfile?.interests) prompt += `\n\nProfile A interests: ${myProfile.interests}`;
    if (theirProfile?.interests) prompt += `\n\nProfile B interests: ${theirProfile.interests}`;

    const result = await openai.chat.completions.create({
      model: 'gpt-4o-mini',
      messages: [{ role: 'user', content: `${prompt}\n\nRespond in JSON: { "overall_score": number, "strengths": ["..."], "weaknesses": ["..."], "surviving_the_apocalypse_together": boolean }` }],
      temperature: 0.8,
      max_tokens: 300,
    });
    const text = result.choices[0]?.message?.content?.trim() || '';
    const cleaned = text.replace(/```json\n?/g, '').replace(/```/g, '').trim();
    res.json(JSON.parse(cleaned));
  } catch (err: any) {
    console.error('[wingman/compatibility-audit]', err.message);
    res.json({ overall_score: 73, strengths: ['Shared sense of humor'], weaknesses: ['Different vibes'], surviving_the_apocalypse_together: true });
  }
});

export default router;
