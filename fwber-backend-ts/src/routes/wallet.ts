import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/wallet - Get user wallet balance
router.get('/', authenticate, async (req: any, res) => {
  res.json({
    balance: 0,
    tokens: 0,
    currency: 'USD',
    transactions: [],
  });
});

// GET /api/wallet/transactions - Transaction history
router.get('/transactions', authenticate, async (req: any, res) => {
  res.json({ transactions: [], total: 0 });
});

// POST /api/wallet/top-up - Add funds
router.post('/top-up', authenticate, async (req: any, res) => {
  res.json({ balance: 0, message: 'Top-up initiated' });
});

// POST /api/wallet/withdraw - Withdraw funds
router.post('/withdraw', authenticate, async (req: any, res) => {
  res.json({ balance: 0, message: 'Withdrawal initiated' });
});

export default router;
