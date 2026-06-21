import { jest } from '@jest/globals';
import request from 'supertest';
import crypto from 'crypto';
import express from 'express';

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: {
    user_quests: {
      findUnique: jest.fn(),
      update: jest.fn(),
    },
    quests: {
      findMany: jest.fn()
    }
  },
  serialize: (data: any) => JSON.parse(JSON.stringify(data, (key, value) => typeof value === 'bigint' ? value.toString() : value))
}));

jest.unstable_mockModule('../src/services/TokenDistributionService.js', () => ({
  TokenDistributionService: jest.fn().mockImplementation(() => ({
    awardTokens: jest.fn().mockResolvedValue(true)
  }))
}));

jest.unstable_mockModule('../src/middleware/auth.js', () => ({
  authenticate: (req: any, res: any, next: any) => {
    req.user = { id: '1', role: 'user' };
    next();
  }
}));

let app: any;

describe('Quest Verification API', () => {
  beforeAll(async () => {
    app = express();
    app.use(express.json());
    const questsRouter = await import('../src/routes/quests.js');
    app.use('/api/quests', questsRouter.default);
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('rejects completion without proof for secure quests', async () => {
    const prismaModule: any = await import('../src/lib/prisma.js');
    prismaModule.default.user_quests.findUnique.mockResolvedValue({
      id: 1n,
      status: 'active',
      quests: {
        id: 1n,
        title: 'Secret Drop',
        token_reward: '50.00',
        verification_secret: 'super-secret-123'
      }
    });

    const response = await request(app)
      .post('/api/quests/1/complete')
      .send({}); // No proof provided

    expect(response.status).toBe(400);
    expect(response.body.error).toBe('ZK/NFC Proof required for this quest');
  });

  it('rejects completion with invalid proof', async () => {
    const prismaModule: any = await import('../src/lib/prisma.js');
    prismaModule.default.user_quests.findUnique.mockResolvedValue({
      id: 1n,
      status: 'active',
      quests: {
        id: 1n,
        title: 'Secret Drop',
        token_reward: '50.00',
        verification_secret: 'super-secret-123'
      }
    });

    const response = await request(app)
      .post('/api/quests/1/complete')
      .send({ proof: 'invalid-hash-here' });

    expect(response.status).toBe(403);
    expect(response.body.error).toBe('Invalid ZK/NFC Proof');
  });

  it('accepts completion with valid proof', async () => {
    const prismaModule: any = await import('../src/lib/prisma.js');
    prismaModule.default.user_quests.findUnique.mockResolvedValue({
      id: 1n,
      status: 'active',
      quests: {
        id: 1n,
        title: 'Secret Drop',
        token_reward: '50.00',
        verification_secret: 'super-secret-123'
      }
    });

    const userId = 1;
    const questId = 1;
    const expectedProof = crypto
      .createHash('sha256')
      .update(`${questId}${userId}super-secret-123`)
      .digest('hex');

    const response = await request(app)
      .post('/api/quests/1/complete')
      .send({ proof: expectedProof });

    expect(response.status).toBe(200);
    expect(response.body.success).toBe(true);
  });
});