// Global BigInt JSON serializer — converts all BigInt to Number for API responses
(BigInt.prototype as any).toJSON = function () { return Number(this); };

import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import morgan from 'morgan';
import * as dotenv from 'dotenv';
import { createServer } from 'http';
import authRoutes from './routes/auth.js';
import analyticsRoutes from './routes/analytics.js';
import dashboardRoutes from './routes/dashboard.js';
import profileRoutes from './routes/profile.js';
import notificationRoutes from './routes/notifications.js';
import safetyRoutes from './routes/safety.js';
import matchesRoutes from './routes/matches.js';
import prisma from './lib/prisma.js';
import { setupSocketIO } from './socket.js';

dotenv.config();

const app = express();
const port = process.env.PORT || 4000;
const httpServer = createServer(app);

// Initialize Socket.io
setupSocketIO(httpServer);

// Middleware
app.use(cors());
app.use(helmet());
app.use(morgan('dev'));
app.use(express.json());

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/analytics', analyticsRoutes);
app.use('/api/dashboard', dashboardRoutes);
app.use('/api/profile', profileRoutes);
app.use('/api/notifications', notificationRoutes);
app.use('/api/safety', safetyRoutes);
app.use('/api/matches', matchesRoutes);

// Health Check
app.get('/health', async (req, res) => {
  try {
    await prisma.$queryRaw`SELECT 1`;
    res.json({ status: 'ok', database: 'connected', version: '2.0.0-ts' });
  } catch (error) {
    res.status(503).json({ status: 'error', database: 'disconnected' });
  }
});

// Basic Route for testing
app.get('/', (req, res) => {
  res.json({ message: 'FWBER TypeScript Backend API v2.0' });
});

httpServer.listen(port, () => {
  console.log(`[server]: Server is running at http://localhost:${port}`);
});

export default app;
