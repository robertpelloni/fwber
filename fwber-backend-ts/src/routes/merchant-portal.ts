import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// POST /api/merchant-portal/register
router.post('/register', (_req, res) => {
  res.json({ success: true, merchant: { id: Date.now(), ..._req.body } });
});

// GET /api/merchant-portal/profile
router.get('/profile', (_req, res) => {
  res.json({ business_name: '', description: '', is_verified: false, rating: 0, total_deals: 0 });
});

// PUT /api/merchant-portal/profile
router.put('/profile', (_req, res) => {
  res.json({ success: true, profile: { ..._req.body } });
});

// GET /api/merchant-portal/promotions
router.get('/promotions', (_req, res) => {
  res.json([]);
});

// POST /api/merchant-portal/promotions
router.post('/promotions', (_req, res) => {
  res.json({ success: true, promotion: { id: Date.now(), ..._req.body } });
});

// GET /api/merchant-portal/promotions/:id
router.get('/promotions/:id', (_req, res) => {
  res.json({ id: _req.params.id, title: '', description: '', active: false });
});

// PUT /api/merchant-portal/promotions/:id
router.put('/promotions/:id', (_req, res) => {
  res.json({ success: true, promotion: { id: _req.params.id, ..._req.body } });
});

// DELETE /api/merchant-portal/promotions/:id
router.delete('/promotions/:id', (_req, res) => {
  res.json({ success: true });
});

// GET /api/merchant-portal/analytics
router.get('/analytics', (_req, res) => {
  res.json({ views: 0, clicks: 0, conversions: 0, revenue: 0 });
});

// GET /api/merchant-portal/inventory
router.get('/inventory', (_req, res) => {
  res.json({ items: [] });
});

// POST /api/merchant-portal/inventory
router.post('/inventory', (_req, res) => {
  res.json({ item: { id: Date.now(), ..._req.body } });
});

// POST /api/merchant-portal/inventory/redeem
router.post('/inventory/redeem', (_req, res) => {
  res.json({ success: true, item_name: '', user_name: '' });
});

export default router;
