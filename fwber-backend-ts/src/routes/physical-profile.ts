import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.get('/', authenticate, async (req: any, res) => { res.json({}); });
router.put('/', authenticate, async (req: any, res) => { res.json({}); });
router.post('/avatar/request', authenticate, async (req: any, res) => { res.json({ success: true, image_url: null }); });

export default router;
