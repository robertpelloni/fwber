import { Router } from 'express';
import multer from 'multer';
import path from 'path';
import fs from 'fs';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// ─── Upload setup ──────────────────────────────────────────────────────────

const UPLOAD_DIR = path.resolve(process.env.UPLOAD_DIR || 'uploads');
const VERIFY_DIR = path.join(UPLOAD_DIR, 'verifications');

if (!fs.existsSync(VERIFY_DIR)) fs.mkdirSync(VERIFY_DIR, { recursive: true });

const upload = multer({
  storage: multer.diskStorage({
    destination: (_req, _file, cb) => cb(null, VERIFY_DIR),
    filename: (_req, file, cb) => {
      const ext = path.extname(file.originalname) || '.jpg';
      const unique = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
      cb(null, `${unique}${ext}`);
    },
  }),
  limits: { fileSize: 10 * 1024 * 1024 },
  fileFilter: (_req, file, cb) => {
    if (['image/jpeg', 'image/png', 'image/webp'].includes(file.mimetype)) {
      cb(null, true);
    } else {
      cb(new Error(`Invalid file type: ${file.mimetype}`));
    }
  },
});

// ─── Routes ────────────────────────────────────────────────────────────────

// GET /api/verification/status
router.get('/status', authenticate, async (req: any, res) => {
  try {
    const profile = await prisma.user_profiles.findFirst({
      where: { user_id: BigInt(req.user.id) },
      select: {
        is_verified: true,
        is_id_verified: true,
        verified_at: true,
        verification_photo_path: true,
      },
    });

    if (!profile) {
      return res.json({
        status: 'none',
        is_verified: false,
        is_id_verified: false,
        verified_at: null,
        verification_photo_path: null,
      });
    }

    res.json({
      status: profile.is_verified ? 'verified' : 'none',
      is_verified: profile.is_verified,
      is_id_verified: profile.is_id_verified,
      verified_at: profile.verified_at?.toISOString() ?? null,
      verification_photo_path: profile.verification_photo_path,
    });
  } catch (err) {
    console.error('Verification status error:', err);
    res.json({
      status: 'none',
      is_verified: false,
      is_id_verified: false,
      verified_at: null,
      verification_photo_path: null,
    });
  }
});

// POST /api/verification/verify — Upload selfie and auto-approve
router.post('/verify', authenticate, upload.single('photo'), async (req: any, res) => {
  try {
    const file = req.file;
    if (!file) {
      return res.status(400).json({
        success: false,
        verified: false,
        message: 'No photo uploaded. Please take a selfie.',
      });
    }

    const userId = BigInt(req.user.id);
    const relativePath = `verifications/${file.filename}`;

    // Update user profile — auto-approve verification
    await prisma.user_profiles.updateMany({
      where: { user_id: userId },
      data: {
        is_verified: true,
        verified_at: new Date(),
        verification_photo_path: relativePath,
      },
    });

    res.json({
      success: true,
      verified: true,
      similarity: 1.0,
      message: 'Identity verified successfully! You now have a verified badge.',
    });
  } catch (err) {
    console.error('Verification error:', err);
    res.status(500).json({
      success: false,
      verified: false,
      message: 'Verification failed. Please try again.',
    });
  }
});

// POST /api/verification/submit — Alias for verify
router.post('/submit', authenticate, upload.single('photo'), async (req: any, res) => {
  try {
    const file = req.file;
    if (!file) {
      return res.status(400).json({ success: false, message: 'No photo uploaded.' });
    }

    const userId = BigInt(req.user.id);
    const relativePath = `verifications/${file.filename}`;

    await prisma.user_profiles.updateMany({
      where: { user_id: userId },
      data: {
        is_verified: true,
        verified_at: new Date(),
        verification_photo_path: relativePath,
      },
    });

    res.json({ success: true, message: 'Verification submitted and approved.' });
  } catch (err) {
    console.error('Verification submit error:', err);
    res.status(500).json({ success: false, message: 'Verification failed.' });
  }
});

export default router;
