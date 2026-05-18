import { Router, Request, Response } from 'express';
import { PrismaClient } from '@prisma/client';
import { ContactSyncService } from '../services/ContactSyncService';

const router = Router();
const prisma = new PrismaClient();

// Simulated middleware for auth
const requireAuth = (req: Request, res: Response, next: any) => {
    // Basic mock auth context
    if (!req.user) {
        req.user = { id: 'mock-user-uuid' };
    }
    next();
};

/**
 * Endpoint to fetch already synced contacts for the user
 * GET /api/integrations/contacts
 */
router.get('/', requireAuth, async (req: Request, res: Response) => {
    try {
        const integrations = await prisma.userIntegration.findMany({
            where: { userId: req.user.id },
            include: { syncedContacts: true }
        });

        // Flatten all contacts from all providers
        let allContacts: any[] = [];
        integrations.forEach(i => {
            allContacts = [...allContacts, ...i.syncedContacts];
        });

        res.json({ contacts: allContacts });
    } catch (err: any) {
        res.status(500).json({ error: err.message });
    }
});

/**
 * Endpoint to manually trigger a sync for the user
 * POST /api/integrations/contacts/sync
 */
router.post('/sync', requireAuth, async (req: Request, res: Response) => {
    try {
        const integrations = await prisma.userIntegration.findMany({
            where: { userId: req.user.id }
        });

        for (const integration of integrations) {
            await ContactSyncService.syncContactsForIntegration(integration.id);
        }

        res.json({ success: true, message: 'Sync complete' });
    } catch (err: any) {
        res.status(500).json({ error: err.message });
    }
});

export default router;
