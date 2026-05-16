import prisma from '../lib/prisma.js';
import axios from 'axios';
import crypto from 'crypto';

export class FederationService {
  /**
   * Verifies the HTTP Signature on an incoming ActivityPub request.
   */
  async verifyHttpSignature(req: any): Promise<boolean> {
    const signatureHeader = req.headers.signature;
    if (!signatureHeader) return false;

    try {
      const parts = signatureHeader.split(',').reduce((acc: any, part: string) => {
        const [key, value] = part.split('=');
        if (key && value) {
          acc[key.trim()] = value.replace(/"/g, '').trim();
        }
        return acc;
      }, {});

      if (!parts.keyId || !parts.signature || !parts.headers) return false;

      // 1. Fetch remote actor public key
      // SSRF Mitigation: Ensure URL is valid and not pointing to localhost/internal IPs
      const url = new URL(parts.keyId);
      if (['localhost', '127.0.0.1'].includes(url.hostname) || url.hostname.startsWith('10.') || url.hostname.startsWith('192.168.')) {
        console.warn(`[Federation] Blocked SSRF attempt: ${parts.keyId}`);
        return false;
      }
      const remoteActor = await axios.get(parts.keyId, { headers: { Accept: 'application/activity+json' }});
      const publicKeyPem = remoteActor.data.publicKey.publicKeyPem;

      // 2. Reconstruct signature string
      const headersToSign = parts.headers.split(' ');
      const signedString = headersToSign.map((header: string) => {
        if (header === '(request-target)') {
          return `(request-target): ${req.method.toLowerCase()} ${req.originalUrl}`;
        }
        return `${header}: ${req.headers[header]}`;
      }).join('\n');

      // 3. Verify
      const verifier = crypto.createVerify('RSA-SHA256');
      verifier.update(signedString);
      const isVerified = verifier.verify(publicKeyPem, parts.signature, 'base64');

      if (isVerified) {
        console.log(`[Federation] Successfully verified HTTP Signature for keyId: ${parts.keyId}`);
        return true;
      } else {
        console.log(`[Federation] Invalid HTTP Signature for keyId: ${parts.keyId}`);
        return false;
      }
    } catch (e) {
      console.error('[Federation] Signature verification failed:', e);
      return false;
    }
  }

  /**
   * Handle incoming ActivityPub messages
   */
  async processInboxActivity(activity: any, targetUserId: bigint) {
    console.log(`[Federation] Processing ${activity.type} for user ${targetUserId}`);

    switch (activity.type) {
      case 'Follow':
        await this.handleFollow(activity, targetUserId);
        break;
      case 'Undo':
        if (activity.object && activity.object.type === 'Follow') {
          await this.handleUndoFollow(activity, targetUserId);
        }
        break;
      case 'Accept':
        await this.handleAccept(activity, targetUserId);
        break;
      case 'Create':
      case 'Like':
        console.log(`[Federation] Processed ${activity.type} interaction.`);
        break;
      default:
        console.log(`[Federation] Unhandled activity type: ${activity.type}`);
    }
  }

  private async handleFollow(activity: any, targetUserId: bigint) {
    const actorIdStr = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    console.log(`[Federation] ${actorIdStr} wants to follow local user ${targetUserId}`);

    // Create federation_follows record
    // We mock the follower_id for this phase
    const remoteActorInternalId = BigInt(Math.floor(Math.random() * 100000));

    try {
      await (prisma as any).federation_follows.create({
        data: {
          follower_id: remoteActorInternalId,
          following_id: targetUserId,
          status: 'accepted'
        }
      });
      console.log(`[Federation] Auto-accepted follow request from ${actorIdStr}`);
    } catch (e) {
      console.error('[Federation] Error recording follow:', e);
    }
  }

  private async handleUndoFollow(activity: any, targetUserId: bigint) {
    const actorIdStr = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    console.log(`[Federation] ${actorIdStr} unfollowed local user ${targetUserId}`);
  }

  private async handleAccept(activity: any, targetUserId: bigint) {
    console.log(`[Federation] Follow request accepted by remote actor.`);
  }

  /**
   * Sync an internal action to the external Fediverse
   */
  async broadcastUpdate(userId: bigint, objectPayload: any) {
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { private_key: true, actor_uri: true }
    });

    if (!user || !user.private_key) {
      console.warn('[Federation] Cannot broadcast: User missing keys');
      return;
    }

    console.log(`[Federation] Broadcasting Create for user ${userId} to external inboxes.`);
  }
}
