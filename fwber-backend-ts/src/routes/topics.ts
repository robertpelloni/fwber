import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.get('/', authenticate, async (req: any, res) => { res.json({ topics: [], total: 0 }); });
router.get('/featured', authenticate, async (req: any, res) => { res.json({ topics: [], total: 0 }); });
router.get('/followed', authenticate, async (req: any, res) => { res.json({ topics: [] }); });
router.get('/:slug', authenticate, async (req: any, res) => { res.json({ topic: null }); });
router.post('/:slug/follow', authenticate, async (req: any, res) => { res.json({ topic: { followed: true } }); });
router.delete('/:slug/follow', authenticate, async (req: any, res) => { res.json({ topic: { followed: false } }); });

export default router;
