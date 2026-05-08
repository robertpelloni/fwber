import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/bounties
router.get('/', async (req: any, res) => {
  try {
    const page = parseInt(req.query.page as string) || 1;
    const perPage = Math.min(parseInt(req.query.per_page as string) || 20, 50);
    const sort = req.query.sort as string || 'newest';

    const where: any = { deleted_at: null, status: { in: ['active', 'closed', 'fulfilled'] } };

    let orderBy: any = { created_at: 'desc' as const };
    if (sort === 'reward') orderBy = { token_reward: 'desc' as const };

    const [bounties, total] = await Promise.all([
      prisma.match_bounties.findMany({
        where, orderBy, skip: (page - 1) * perPage, take: perPage,
        include: {
          users: { select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true } } } }
        }
      }),
      prisma.match_bounties.count({ where })
    ]);

    const data = bounties.map((b: any) => {
      const profile = Array.isArray(b.users?.user_profiles) ? b.users.user_profiles[0] : b.users?.user_profiles;
      return {
        id: Number(b.id), title: b.slug, description: b.description,
        reward_amount: Number(b.token_reward), status: b.status,
        expires_at: b.expires_at, created_at: b.created_at,
        creator: { id: Number(b.users?.id), name: profile?.display_name || b.users?.name || 'Anonymous', avatar_url: profile?.avatar_url || null }
      };
    });

    res.json({ data, total, page, per_page: perPage });
  } catch (err: any) {
    console.error('[bounties] list error:', err.message);
    res.json({ data: [], total: 0, page: 1, per_page: 20 });
  }
});

// POST /api/bounties
router.post('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { title, description, reward_amount } = req.body;
    if (!title) return res.status(422).json({ error: 'Title is required' });
    const reward = Number(reward_amount) || 100;

    const user = await prisma.users.findUnique({ where: { id: userId }, select: { token_balance: true } });
    if (!user || Number(user.token_balance || 0) < reward) return res.status(400).json({ error: 'Insufficient balance' });

    const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') + '-' + Date.now().toString(36);
    const bounty = await prisma.match_bounties.create({
      data: { user_id: userId, slug, token_reward: reward, description: description || '', status: 'active', expires_at: new Date(Date.now() + 7 * 86400000) }
    });
    await prisma.users.update({ where: { id: userId }, data: { token_balance: { decrement: reward } } });

    res.status(201).json({ id: Number(bounty.id), slug: bounty.slug, reward_amount: Number(bounty.token_reward), status: bounty.status, message: 'Bounty created' });
  } catch (err: any) {
    console.error('[bounties] create error:', err.message);
    res.status(500).json({ error: 'Failed to create bounty' });
  }
});

// GET /api/bounties/:id
router.get('/:id', async (req: any, res) => {
  try {
    const bounty = await prisma.match_bounties.findUnique({
      where: { id: BigInt(req.params.id) },
      include: { users: { select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true } } } } }
    });
    if (!bounty || bounty.deleted_at) return res.status(404).json({ message: 'Bounty not found' });
    const profile = Array.isArray(bounty.users?.user_profiles) ? bounty.users.user_profiles[0] : bounty.users?.user_profiles;
    res.json({
      id: Number(bounty.id), title: bounty.slug, description: bounty.description,
      reward_amount: Number(bounty.token_reward), status: bounty.status, expires_at: bounty.expires_at, created_at: bounty.created_at,
      creator: { id: Number(bounty.users?.id), name: profile?.display_name || bounty.users?.name || 'Anonymous', avatar_url: profile?.avatar_url || null }
    });
  } catch (err: any) {
    res.status(404).json({ message: 'Bounty not found' });
  }
});

export default router;
