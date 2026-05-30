import { Router, type Request, type Response } from 'express';
import prisma from '../lib/prisma.js';
import { ContactSyncService } from '../services/ContactSyncService.js';
import { authenticate, type AuthRequest } from '../middleware/auth.js';

const router = Router();

/**
 * Endpoint to fetch already synced contacts for the user
 * GET /api/integrations/contacts/data
 */
router.get('/', authenticate, async (req: AuthRequest, res: Response) => {
    try {
        const integrations = await (prisma as any).userIntegration.findMany({
            where: { userId: BigInt(req.user.id) },

        const integrations = await prisma.userIntegration.findMany({
            where: { userId: BigInt(req.user!.id) },
            include: { syncedContacts: true }
        });

        // Flatten all contacts from all providers
        let allContacts: any[] = [];
        integrations.forEach((i: any) => {
            allContacts = [...allContacts, ...i.syncedContacts];
        });

        res.json({ contacts: allContacts });
    } catch (err: any) {
        res.status(500).json({ error: err.message });
    }
});

/**
 * Endpoint to manually trigger a sync for the user
 * POST /api/integrations/contacts/data/sync
 */
router.post('/sync', authenticate, async (req: AuthRequest, res: Response) => {
    try {
        const integrations = await (prisma as any).userIntegration.findMany({
            where: { userId: BigInt(req.user.id) }

        const integrations = await prisma.userIntegration.findMany({
            where: { userId: BigInt(req.user!.id) }
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
