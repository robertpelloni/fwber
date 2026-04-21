import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/safety/walk/active
router.get('/walk/active', authenticate, async (req, res) => {
  res.json({ active: false });
});

// GET /api/safety/contacts - Get emergency contacts
router.get('/contacts', authenticate, async (req, res) => {
  res.json({ contacts: [] });
});

// POST /api/safety/contacts - Add emergency contact
router.post('/contacts', authenticate, async (req, res) => {
  res.json({ contact: req.body, success: true });
});

// DELETE /api/safety/contacts/:id - Remove emergency contact
router.delete('/contacts/:id', authenticate, async (req, res) => {
  res.json({ success: true });
});

export default router;
