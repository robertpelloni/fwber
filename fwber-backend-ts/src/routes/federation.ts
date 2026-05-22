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
      name: 'Fediverse Display Name',
      summary: 'A test user for the fediverse',
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

// POST /api/federation/users/:id/inbox - Receive inbox messages
router.post('/users/:id/inbox', async (req, res) => {
  const isValid = await federationService.verifyHttpSignature(req);
  if (!isValid) {
    return res.status(401).json({ error: 'Invalid HTTP Signature' });
  }

  const payload = req.body;
  if (!payload || !payload.type) {
    return res.status(400).json({ error: 'Invalid ActivityPub payload' });
  }

  try {
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
  enabled: true,
  status: 'active',
  message: 'Federation is active',
}));

// GET /api/federation/following - Get external actors user is following
router.get('/following', async (req: any, res) => {
    try {
        const following = await (prisma as any).federation_follows.findMany({
            where: { actor_uri: `https://api.fwber.me/api/federation/actors/${req.user.id}` }
        });

        const mapped = following.map((f: any) => ({
            id: f.id.toString(),
            actor_uri: f.target_uri,
            username: f.target_uri.split('/').pop(),
            domain: new URL(f.target_uri).host,
            status: f.status
        }));

        res.json({ following: mapped });
    } catch (err: any) {
        res.status(500).json({ error: err.message });
    }
});

// GET /api/federation/followers - Get external actors following user
router.get('/followers', async (req: any, res) => {
    try {
        const followers = await (prisma as any).federation_follows.findMany({
            where: { target_uri: `https://api.fwber.me/api/federation/actors/${req.user.id}`, status: 'accepted' }
        });

        const mapped = followers.map((f: any) => ({
            id: f.id.toString(),
            actor_uri: f.actor_uri,
            username: f.actor_uri.split('/').pop(),
            domain: new URL(f.actor_uri).host
        }));

        res.json({ followers: mapped });
    } catch (err: any) {
        res.status(500).json({ error: err.message });
    }
});

// POST /api/federation/follow - Follow an external actor
router.post('/follow', async (req: any, res) => {
    const { actor_id } = req.body;
    if (!actor_id) return res.status(400).json({ error: 'actor_id is required' });

    try {
        const userUri = `https://api.fwber.me/api/federation/actors/${req.user.id}`;

        await (prisma as any).federation_follows.create({
            data: {
                actor_uri: userUri,
                target_uri: actor_id,
                status: 'pending'
            }
        });

        res.json({ success: true, message: 'Follow request initiated' });
    } catch (err: any) {
        res.status(500).json({ error: err.message });
    }
});

// GET /api/federation/search - Search for external actors
router.get('/search', async (req: any, res) => {
    const { q } = req.query;
    if (!q || typeof q !== 'string') return res.status(400).json({ error: 'Query is required' });

    // Mock search for now
    res.json({
        actors: [
            {
                id: `https://mastodon.social/users/${q.split('@')[1] || 'user'}`,
                preferredUsername: q.split('@')[1] || 'user',
                server: q.split('@')[2] || 'mastodon.social',
                name: `External ${q.split('@')[1] || 'User'}`,
                summary: 'An actor from the fediverse',
                icon: { url: 'https://placehold.co/400x400?text=Actor' }
            }
        ]
    });
});

export default router;
