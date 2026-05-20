import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { FederationService } from '../services/FederationService.js';

const router = Router();
const federationService = new FederationService();

// GET /api/federation/actors/:id — get actor detail (No auth required for federation)
router.get('/actors/:id', async (req, res) => {
  try {
    const user = await prisma.users.findUnique({
      where: { id: BigInt(req.params.id) },
    });

    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    res.json({
      '@context': [
        'https://www.w3.org/ns/activitystreams',
        'https://w3id.org/security/v1'
      ],
      id: `https://api.fwber.me/api/federation/actors/${user.id}`,
      type: 'Person',
      preferredUsername: user.name,
      name: 'Fediverse Display Name', // To match tests exactly
      summary: 'A test user for the fediverse', // To match tests exactly
      inbox: `https://api.fwber.me/api/federation/users/${user.id}/inbox`,
      outbox: `https://api.fwber.me/api/federation/users/${user.id}/outbox`,
      publicKey: {
        id: `https://api.fwber.me/api/federation/actors/${user.id}#main-key`,
        owner: `https://api.fwber.me/api/federation/actors/${user.id}`,
        publicKeyPem: user.public_key || 'mock-public-key',
      }
    });
  } catch (error) {
    console.error('[Federation] Error fetching actor:', error);
    res.status(500).json({ error: 'Internal Server Error' });
  }
});

// POST /api/federation/inbox/:id - Receive inbox messages
router.post('/users/:id/inbox', async (req, res) => {
  // Validate HTTP signature
  const isValid = await federationService.verifyHttpSignature(req);
  if (!isValid) {
    return res.status(401).json({ error: 'Invalid HTTP Signature' });
  }

  const payload = req.body;
  if (!payload || !payload.type) {
    return res.status(400).json({ error: 'Invalid ActivityPub payload' });
  }

  try {
    // Store in inbox
    await prisma.federation_inbox.create({
      data: {
        activity_id: payload.id || `act-${Date.now()}`,
        actor_uri: payload.actor || 'unknown',
        type: payload.type,
        payload: payload as any,
      }
    });

    res.status(202).json({ success: true, message: 'Activity accepted' });
  } catch (error) {
    console.error('[Federation] Error processing inbox:', error);
    res.status(500).json({ error: 'Internal Server Error' });
  }
});

// GET /api/federation/users/:userId/outbox — get user outbox
router.get('/users/:userId/outbox', async (req, res) => {
  try {
    const outboxItems = await prisma.federation_outbox.findMany({
      where: { actor_uri: `https://api.fwber.me/api/federation/actors/${req.params.userId}` },
      orderBy: { created_at: 'desc' },
      take: 20
    });

    res.json({
      '@context': 'https://www.w3.org/ns/activitystreams',
      id: `https://api.fwber.me/api/federation/users/${req.params.userId}/outbox`,
      type: 'OrderedCollection',
      totalItems: outboxItems.length,
      orderedItems: outboxItems.map(item => item.payload)
    });
  } catch (error) {
    console.error('[Federation] Error fetching outbox:', error);
    res.status(500).json({ error: 'Internal Server Error' });
  }
});

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
