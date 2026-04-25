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
import photosRoutes from './routes/photos.js';
import topicsRoutes from './routes/topics.js';
import verificationRoutes from './routes/verification.js';
import physicalProfileRoutes from './routes/physical-profile.js';
import friendsRoutes from './routes/friends.js';
import relationshipLinksRoutes from './routes/relationship-links.js';
import vouchRoutes from './routes/vouch.js';
import eventsRoutes from './routes/events.js';
import locationRoutes from './routes/location.js';
import wingmanRoutes from './routes/wingman.js';
import recommendationsRoutes from './routes/recommendations.js';
import marketplaceRoutes from './routes/marketplace.js';
import giftsRoutes from './routes/gifts.js';
import dealsRoutes from './routes/deals.js';
import leaderboardRoutes from './routes/leaderboard.js';
import identityRoutes from './routes/identity.js';
import groupsRoutes from './routes/groups.js';
import walletRoutes from './routes/wallet.js';
import venuesRoutes from './routes/venues.js';
import premiumRoutes from './routes/premium.js';
import userRoutes from './routes/user.js';
import proximityRoutes from './routes/proximity.js';
import onboardingRoutes from './routes/onboarding.js';
import videoRoutes from './routes/video.js';
import referralsRoutes from './routes/referrals.js';
import boostsRoutes from './routes/boosts.js';
import contentGenerationRoutes from './routes/content-generation.js';
import catsRoutes from './routes/cats.js';
import securityRoutes from './routes/security.js';
import blocksRoutes from './routes/blocks.js';
import subscriptionsRoutes from './routes/subscriptions.js';
import notificationPreferencesRoutes from './routes/notification-preferences.js';
import hardwareTokensRoutes from './routes/hardware-tokens.js';
import merchantRoutes from './routes/merchant.js';
import bountiesRoutes from './routes/bounties.js';
import achievementsRoutes from './routes/achievements.js';
import scrapbookRoutes from './routes/scrapbook.js';
import chatroomsRoutes from './routes/chatrooms.js';
import bulletinBoardsRoutes from './routes/bulletin-boards.js';
import burnerLinksRoutes from './routes/burner-links.js';
import feedbackRoutes from './routes/feedback.js';
import journalsRoutes from './routes/journals.js';
import proximityChatroomsRoutes from './routes/proximity-chatrooms.js';
import reportsRoutes from './routes/reports.js';
import merchantPortalRoutes from './routes/merchant-portal.js';
import moderationRoutes from './routes/moderation.js';
import messagesRoutes from './routes/messages.js';
import federationRoutes from './routes/federation.js';
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
app.use('/api/photos', photosRoutes);
app.use('/api/topics', topicsRoutes);
app.use('/api/verification', verificationRoutes);
app.use('/api/physical-profile', physicalProfileRoutes);
app.use('/api/friends', friendsRoutes);
app.use('/api/relationship-links', relationshipLinksRoutes);
app.use('/api/vouch', vouchRoutes);
app.use('/api/events', eventsRoutes);
app.use('/api/location', locationRoutes);
app.use('/api/wingman', wingmanRoutes);
app.use('/api/recommendations', recommendationsRoutes);
app.use('/api/marketplace', marketplaceRoutes);
app.use('/api/gifts', giftsRoutes);
app.use('/api/deals', dealsRoutes);
app.use('/api/leaderboard', leaderboardRoutes);
app.use('/api/identity', identityRoutes);
app.use('/api/groups', groupsRoutes);
app.use('/api/wallet', walletRoutes);
app.use('/api/venues', venuesRoutes);
app.use('/api/premium', premiumRoutes);
app.use('/api/user', userRoutes);
app.use('/api/proximity', proximityRoutes);
app.use('/api/onboarding', onboardingRoutes);
app.use('/api/video', videoRoutes);
app.use('/api/referrals', referralsRoutes);
app.use('/api/boosts', boostsRoutes);
app.use('/api/content-generation', contentGenerationRoutes);
app.use('/api/cats', catsRoutes);
app.use('/api/security', securityRoutes);
app.use('/api/blocks', blocksRoutes);
app.use('/api/subscriptions', subscriptionsRoutes);
app.use('/api/notification-preferences', notificationPreferencesRoutes);
app.use('/api/hardware-tokens', hardwareTokensRoutes);
app.use('/api/merchant', merchantRoutes);
app.use('/api/bounties', bountiesRoutes);
app.use('/api/achievements', achievementsRoutes);
app.use('/api/scrapbook', scrapbookRoutes);
app.use('/api/chatrooms', chatroomsRoutes);
app.use('/api/bulletin-boards', bulletinBoardsRoutes);
app.use('/api/burner-links', burnerLinksRoutes);
app.use('/api/feedback', feedbackRoutes);
app.use('/api/journals', journalsRoutes);
app.use('/api/proximity-chatrooms', proximityChatroomsRoutes);
app.use('/api/reports', reportsRoutes);
app.use('/api/merchant-portal', merchantPortalRoutes);
app.use('/api/moderation', moderationRoutes);
app.use('/api/messages', messagesRoutes);
app.use('/api/federation', federationRoutes);

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
