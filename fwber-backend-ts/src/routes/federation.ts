import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/federation — list federation status
router.get('/', (_req, res) => res.json({
  enabled: false,
  status: 'disabled',
  message: 'Federation is not yet enabled for this instance',
}));

// GET /api/federation/actors/detail — get actor detail
router.get('/actors/detail', (_req, res) => res.json({
  actor: null,
  posts: [],
  message: 'Federation actors are not yet available',
}));

// GET /api/federation/posts — get federation posts
router.get('/posts', (_req, res) => res.json({
  posts: [],
  total: 0,
  message: 'Federation posts are not yet available',
}));

// GET /api/federation/users/:userId/outbox — get user outbox
router.get('/users/:userId/outbox', (_req, res) => res.json({
  orderedItems: [],
  totalItems: 0,
  type: 'OrderedCollectionPage',
}));

// POST /api/federation — create federation resource
router.post('/', (_req, res) => res.json({ success: true, message: 'Federation is not yet enabled' }));

export default router;
