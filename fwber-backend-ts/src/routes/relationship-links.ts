import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.get('/', authenticate, async (req: any, res) => { res.json({ data: [] }); });
router.get('/requests', authenticate, async (req: any, res) => { res.json({ data: [] }); });
router.post('/', authenticate, async (req: any, res) => { res.json({ success: true }); });
router.delete('/:id', authenticate, async (req: any, res) => { res.json({ success: true }); });

export default router;
