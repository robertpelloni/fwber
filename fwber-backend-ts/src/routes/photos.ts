import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.get('/', authenticate, async (req: any, res) => { res.json({ success: true, data: [], count: 0 }); });
router.post('/', authenticate, async (req: any, res) => { res.json({ success: true, data: { id: Date.now(), file_path: '', is_primary: false, is_private: false } }); });
router.put('/:id', authenticate, async (req: any, res) => { res.json({ success: true, data: { id: Number(req.params.id), ...req.body } }); });
router.delete('/:id', authenticate, async (req: any, res) => { res.json({ success: true, message: 'Photo deleted' }); });
router.post('/reorder', authenticate, async (req: any, res) => { res.json({ success: true }); });
router.get('/settings', authenticate, async (req: any, res) => { res.json({ success: true, data: { auto_blur: false, reveal_price: 10 } }); });
router.post('/:id/reveal', authenticate, async (req: any, res) => { res.json({ success: true, status: 'requested' }); });
router.post('/:id/unlock', authenticate, async (req: any, res) => { res.json({ success: true, message: 'Unlocked', balance: 0 }); });
router.get('/:id/original', authenticate, async (req: any, res) => { res.json({ success: true }); });

export default router;
