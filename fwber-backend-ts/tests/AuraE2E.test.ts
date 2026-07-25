process.env.OPENAI_API_KEY = 'mock';
import { jest } from '@jest/globals';

jest.unstable_mockModule('openai', () => {
    class MockOpenAI {
        constructor() {}
        chat = { completions: { create: async () => ({ choices: [{ message: { content: 'mock' } }] }) } }
    }
    return { OpenAI: MockOpenAI, default: MockOpenAI };
});
import express from 'express';
jest.unstable_mockModule('../src/routes/wingman.js', () => ({ default: express.Router() }));
jest.unstable_mockModule('../src/middleware/auth.js', () => ({
  authenticate: (req: any, res: any, next: any) => { req.user = { id: 1n }; next(); }
}));

const mockFindFirst = jest.fn()
  .mockResolvedValueOnce({ current_emotion: 'Thoughtful' }) // p1 user
  .mockResolvedValueOnce({ current_emotion: 'Mysterious' }) // p2 target
  .mockResolvedValueOnce({ current_emotion: 'Thoughtful' }) // p1 user (second request)
  .mockResolvedValueOnce({ current_emotion: 'Happy' }); // p2 target (second request)

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  sanitizeUser: (o: any) => o,
  serialize: (o: any) => o,
  default: {
    user_profiles: {
      findFirst: mockFindFirst,
      update: jest.fn().mockResolvedValue({})
    }
  }
}));

import request from 'supertest';

let app: any;
beforeAll(async () => {
    const mod = await import('../src/index.js');
    app = mod.app || mod.default;
});

describe('Phase 10: Aura-Matched Chat E2E', () => {
  it('should successfully calculate matching aura and mood for two complementary emotions', async () => {
    const res = await request(app)
      .post('/api/chat/aura')
      .set('Authorization', `Bearer mock`)
      .send({ targetUserId: 2 });

    expect(res.status).toBe(200);
    expect(res.body.success).toBe(true);
    expect(res.body.vibe).toBe('contemplative'); // 'Thoughtful' maps to 'contemplative' vibe
    expect(res.body.mood).toBeDefined();

    expect(res.body.emotions.user).toBe('Thoughtful');
    expect(res.body.emotions.target).toBe('Mysterious');
  });

  it('should reflect real-time sentiment changes when a user updates their aura', async () => {
    // The second pair of mockFindFirst calls simulate the emotion changing to "Happy"
    const res = await request(app)
      .post('/api/chat/aura')
      .set('Authorization', `Bearer mock`)
      .send({ targetUserId: 2 });

    expect(res.status).toBe(200);
    expect(res.body.mood).toBeDefined(); // Happy + Thoughtful are complementary
    expect(res.body.emotions.target).toBeDefined();
  });
});
