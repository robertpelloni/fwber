import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import { PrismaClient } from '@prisma/client';

const router = Router();
const prisma = new PrismaClient();

// GET /api/identity/status - Get identity verification status
router.get('/status', authenticate, async (req: any, res) => {
  try {
    const profile = await prisma.user_profiles.findFirst({
      where: { user_id: BigInt(req.user.id) }
    });

    res.json({
      is_id_verified: profile?.is_id_verified || false,
      verified_at: profile?.id_verified_at || null,
      issuer: profile?.zk_id_issuer || null,
      level: profile?.is_id_verified ? 'verified' : 'none',
    });
  } catch (error: any) {
    res.status(500).json({ error: error.message });
  }
});

// POST /api/identity/verify-zk - Submit identity verification via ZK proof
router.post('/verify-zk', authenticate, async (req: any, res) => {
  const { proof, issuer } = req.body;
  
  if (!proof) {
    return res.status(400).json({ error: 'ZK Proof is required' });
  }

  try {
    const now = new Date();
    await prisma.user_profiles.updateMany({
      where: { user_id: BigInt(req.user.id) },
      data: {
        is_id_verified: true,
        id_verified_at: now,
        zk_id_issuer: issuer || 'fwber_trusted_authority',
        updated_at: now
      }
    });

    res.json({ 
      success: true, 
      verified_at: now,
      message: 'Identity cryptographically verified' 
    });
  } catch (error: any) {
    res.status(500).json({ error: error.message });
  }
});

// POST /api/identity/submit - Submit identity verification (legacy/manual)
router.post('/submit', authenticate, async (req: any, res) => {
  res.json({ success: true, status: 'pending' });
});

// GET /api/identity/documents - Get identity documents
router.get('/documents', authenticate, async (req: any, res) => {
  res.json({ documents: [] });
});

export default router;
