import request from 'supertest';
import app from '../src/index.js';
import prisma from '../src/lib/prisma.js';
import bcrypt from 'bcryptjs';

describe('Auth Endpoints', () => {
  const testUser = {
    name: 'Test User',
    email: 'test@example.com',
    password: 'password123',
  };

  beforeAll(async () => {
    // Clear existing test data
    await prisma.user.deleteMany({ where: { email: testUser.email } });
  });

  afterAll(async () => {
    await prisma.user.deleteMany({ where: { email: testUser.email } });
    await prisma.$disconnect();
  });

  it('should register a new user', async () => {
    const res = await request(app)
      .post('/api/auth/register')
      .send(testUser);

    expect(res.statusCode).toEqual(201);
    expect(res.body).toHaveProperty('access_token');
    expect(res.body.user.email).toEqual(testUser.email);
  });

  it('should login with correct credentials', async () => {
    const res = await request(app)
      .post('/api/auth/login')
      .send({
        email: testUser.email,
        password: testUser.password,
      });

    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('access_token');
  });

  it('should fail login with incorrect password', async () => {
    const res = await request(app)
      .post('/api/auth/login')
      .send({
        email: testUser.email,
        password: 'wrongpassword',
      });

    expect(res.statusCode).toEqual(401);
  });
});
