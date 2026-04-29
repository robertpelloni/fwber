import { Router } from 'express';
import * as ai from '../lib/wingman-ai.js';
import prisma from '../lib/prisma.js';

const router = Router();

// POST /api/public/roast — Guest roast/hype (no auth required)
router.post('/roast', async (req, res) => {
  try {
    const { name = 'Anonymous', job = 'mysterious stranger', trait = 'enigmatic', mode = 'roast' } = req.body || {};
    const roast = await ai.generatePublicRoast(
      String(name),
      String(job),
      String(trait),
      mode === 'hype' ? 'hype' : 'roast'
    );
    const type = mode === 'hype' ? 'hype' : 'roast';
    const shareId = `${type.slice(0, 4)}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
    try {
      await prisma.viral_contents.create({
        data: { id: shareId, user_id: 0, type, content: JSON.stringify({ text: roast, user_name: name }) },
      });
    } catch {}
    res.json({ roast, is_preview: false, cta: 'Create your own profile at fwber.me', share_id: shareId });
  } catch (err: any) {
    console.error('[public/roast]', err.message);
    res.json({
      roast: `Hey ${req.body?.name || 'you'}, your profile gives off "I still use Internet Explorer" energy. But hey, at least you showed up!`,
      is_preview: true,
      cta: 'Create a profile for a personalized roast',
    });
  }
});

export default router;
