import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

/**
 * GET /api/relationship-links
 * Returns established relationship links for the current user
 */
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const links = await prisma.relationship_links.findMany({
      where: {
        user_id: userId,
        is_confirmed: true,
      },
      include: {
        users_relationship_links_related_user_idTousers: {
          select: {
            id: true,
            name: true,
            user_profiles: {
              select: {
                display_name: true,
                avatar_url: true,
              }
            }
          }
        }
      }
    });

    res.json({
      data: links.map(l => ({
        id: Number(l.id),
        relationship_type: l.relationship_type,
        relationship_type_label: l.relationship_type.charAt(0).toUpperCase() + l.relationship_type.slice(1),
        visibility: l.visibility,
        visibility_label: l.visibility.charAt(0).toUpperCase() + l.visibility.slice(1),
        note: l.note,
        confirmed_at: l.confirmed_at?.toISOString(),
        is_confirmed: l.is_confirmed,
        requested_by_user_id: Number(l.requested_by_user_id),
        related_user: l.users_relationship_links_related_user_idTousers ? {
          id: Number(l.users_relationship_links_related_user_idTousers.id),
          name: l.users_relationship_links_related_user_idTousers.name,
          display_name: l.users_relationship_links_related_user_idTousers.user_profiles?.[0]?.display_name,
          avatar_url: l.users_relationship_links_related_user_idTousers.user_profiles?.[0]?.avatar_url,
        } : null
      }))
    });
  } catch (error: any) {
    console.error('[RelationshipLinks] GET Error:', error.message);
    res.status(500).json({ message: 'Internal Server Error' });
  }
});

/**
 * GET /api/relationship-links/requests
 * Returns pending relationship requests
 */
router.get('/requests', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const requests = await prisma.relationship_links.findMany({
      where: {
        related_user_id: userId,
        is_confirmed: false,
      },
      include: {
        users_relationship_links_user_idTousers: {
          select: {
            id: true,
            name: true,
            user_profiles: {
              select: {
                display_name: true,
                avatar_url: true,
              }
            }
          }
        }
      }
    });

    res.json({
      data: requests.map(l => ({
        id: Number(l.id),
        relationship_type: l.relationship_type,
        requested_by: {
          id: Number(l.users_relationship_links_user_idTousers.id),
          name: l.users_relationship_links_user_idTousers.name,
          display_name: l.users_relationship_links_user_idTousers.user_profiles?.[0]?.display_name,
          avatar_url: l.users_relationship_links_user_idTousers.user_profiles?.[0]?.avatar_url,
        },
        requested_at: l.created_at?.toISOString()
      }))
    });
  } catch (error: any) {
    console.error('[RelationshipLinks] Requests Error:', error.message);
    res.status(500).json({ message: 'Internal Server Error' });
  }
});

/**
 * POST /api/relationship-links
 * Request a relationship link with another user
 */
router.post('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { related_user_id, relationship_type, visibility, note } = req.body;

    if (!related_user_id || !relationship_type) {
      return res.status(400).json({ message: 'related_user_id and relationship_type are required' });
    }

    const relatedUserId = BigInt(related_user_id);

    // Don't link to self
    if (userId === relatedUserId) {
      return res.status(400).json({ message: "You cannot link to yourself" });
    }

    // Check for existing link
    const existing = await prisma.relationship_links.findFirst({
      where: {
        OR: [
          { user_id: userId, related_user_id: relatedUserId },
          { user_id: relatedUserId, related_user_id: userId }
        ]
      }
    });

    if (existing) {
      return res.status(400).json({ message: 'A relationship link already exists or is pending' });
    }

    const link = await prisma.relationship_links.create({
      data: {
        user_id: userId,
        related_user_id: relatedUserId,
        relationship_type,
        visibility: visibility || 'public',
        note,
        requested_by_user_id: userId,
        is_confirmed: false
      }
    });

    res.json({ success: true, id: Number(link.id) });
  } catch (error: any) {
    console.error('[RelationshipLinks] POST Error:', error.message);
    res.status(500).json({ message: 'Internal Server Error' });
  }
});

/**
 * POST /api/relationship-links/:id/confirm
 * Confirm a relationship request
 */
router.post('/:id/confirm', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const linkId = BigInt(req.params.id);

    const request = await prisma.relationship_links.findUnique({
      where: { id: linkId }
    });

    if (!request || request.related_user_id !== userId) {
      return res.status(404).json({ message: 'Request not found' });
    }

    // Update the request and create the reciprocal link
    await prisma.$transaction([
      prisma.relationship_links.update({
        where: { id: linkId },
        data: {
          is_confirmed: true,
          confirmed_at: new Date()
        }
      }),
      prisma.relationship_links.create({
        data: {
          user_id: userId,
          related_user_id: request.user_id,
          relationship_type: request.relationship_type,
          visibility: request.visibility,
          is_confirmed: true,
          confirmed_at: new Date(),
          requested_by_user_id: request.user_id
        }
      })
    ]);

    res.json({ success: true });
  } catch (error: any) {
    console.error('[RelationshipLinks] Confirm Error:', error.message);
    res.status(500).json({ message: 'Internal Server Error' });
  }
});

/**
 * DELETE /api/relationship-links/:id
 * Remove/Cancel a relationship link
 */
router.delete('/:id', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const linkId = BigInt(req.params.id);

    const link = await prisma.relationship_links.findUnique({
      where: { id: linkId }
    });

    if (!link || (link.user_id !== userId && link.related_user_id !== userId)) {
      return res.status(404).json({ message: 'Link not found' });
    }

    // Delete both sides if confirmed
    if (link.is_confirmed) {
      const otherSide = await prisma.relationship_links.findFirst({
        where: {
          user_id: link.related_user_id!,
          related_user_id: link.user_id,
          is_confirmed: true
        }
      });

      const idsToDelete = [linkId];
      if (otherSide) idsToDelete.push(otherSide.id);

      await prisma.relationship_links.deleteMany({
        where: { id: { in: idsToDelete } }
      });
    } else {
      await prisma.relationship_links.delete({
        where: { id: linkId }
      });
    }

    res.json({ success: true });
  } catch (error: any) {
    console.error('[RelationshipLinks] DELETE Error:', error.message);
    res.status(500).json({ message: 'Internal Server Error' });
  }
});

export default router;
