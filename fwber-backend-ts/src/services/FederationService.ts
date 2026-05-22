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
    const actorUri = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    const targetUri = `https://api.fwber.me/api/federation/actors/${targetUserId}`;

    console.log(`[Federation] ${actorUri} wants to follow local user ${targetUserId}`);

    try {
        // Find or create remote user record
        let remoteUser = await prisma.users.findUnique({
            where: { actor_uri: actorUri }
        });

        if (!remoteUser) {
            // Attempt to fetch actor info for basic details
            try {
                const actorRes = await axios.get(actorUri, { headers: { Accept: 'application/activity+json' }});
                remoteUser = await prisma.users.create({
                    data: {
                        name: actorRes.data.preferredUsername || 'remote_user',
                        email: `remote-${Date.now()}@federated`,
                        password: 'REMOTE_ACCOUNT_NO_LOGIN',
                        actor_uri: actorUri,
                        is_remote: true,
                        public_key: actorRes.data.publicKey?.publicKeyPem || null
                    }
                });
            } catch (err) {
                console.warn(`[Federation] Could not fetch remote actor details for ${actorUri}, creating minimal record`);
                remoteUser = await prisma.users.create({
                    data: {
                        name: 'remote_user',
                        email: `remote-${Date.now()}@federated`,
                        password: 'REMOTE_ACCOUNT_NO_LOGIN',
                        actor_uri: actorUri,
                        is_remote: true
                    }
                });
            }
        }

      await (prisma as any).federation_follows.create({
        data: {
          actor_uri: actorUri,
          target_uri: targetUri,
          status: 'accepted'
        }
      });
      console.log(`[Federation] Auto-accepted follow request from ${actorUri}`);

      // TODO: Send an Accept activity back to the remote user
    } catch (e) {
      console.error('[Federation] Error recording follow:', e);
    }
  }

  private async handleUndoFollow(activity: any, targetUserId: bigint) {
    const actorUri = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    const targetUri = `https://api.fwber.me/api/federation/actors/${targetUserId}`;

    console.log(`[Federation] ${actorUri} unfollowed local user ${targetUserId}`);

    try {
        await (prisma as any).federation_follows.deleteMany({
            where: {
                actor_uri: actorUri,
                target_uri: targetUri
            }
        });
    } catch (err) {
        console.error('[Federation] Error undoing follow:', err);
    }
  }

  private async handleAccept(activity: any, targetUserId: bigint) {
    const actorUri = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    const targetUri = `https://api.fwber.me/api/federation/actors/${targetUserId}`;

    console.log(`[Federation] Follow request accepted by remote actor ${actorUri}.`);

    try {
        await (prisma as any).federation_follows.updateMany({
            where: {
                actor_uri: targetUri,
                target_uri: actorUri
            },
            data: { status: 'accepted' }
        });
    } catch (err) {
        console.error('[Federation] Error handling accept:', err);
    }
  }

  /**
   * Sync an internal action to the external Fediverse
   */
  async broadcastUpdate(userId: bigint, objectPayload: any) {
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { private_key: true, name: true }
    });

    if (!user || !user.private_key) {
      console.warn(`[Federation] Cannot broadcast: User ${userId} missing keys`);
      return;
    }

    const actorUri = `https://api.fwber.me/api/federation/actors/${userId}`;
    const followers = await (prisma as any).federation_follows.findMany({
        where: { target_uri: actorUri, status: 'accepted' }
    });

    if (followers.length === 0) return;

    const activity = {
        '@context': 'https://www.w3.org/ns/activitystreams',
        id: `https://api.fwber.me/api/federation/activities/${Date.now()}`,
        type: 'Create',
        actor: actorUri,
        object: objectPayload
    };

    console.log(`[Federation] Broadcasting to ${followers.length} followers for user ${userId}.`);

    for (const follower of followers) {
        try {
            // In a real implementation, we would resolve the follower's inbox URI
            // For now, we assume the actor_uri is the actor's profile and we'd fetch it to find the inbox
            const actorRes = await axios.get(follower.actor_uri, { headers: { Accept: 'application/activity+json' }});
            const inbox = actorRes.data.inbox;

            if (inbox) {
                await this.sendSignedRequest(inbox, activity, user.private_key, actorUri);
            }
        } catch (err: any) {
            console.error(`[Federation] Failed to deliver to ${follower.actor_uri}:`, err.message);
        }
    }
  }

  private async sendSignedRequest(inboxUrl: string, activity: any, privateKey: string, actorUri: string) {
    const url = new URL(inboxUrl);
    const date = new Date().toUTCString();
    const digest = crypto.createHash('sha256').update(JSON.stringify(activity)).digest('base64');

    const stringToSign = `(request-target): post ${url.pathname}\nhost: ${url.host}\ndate: ${date}\ndigest: SHA-256=${digest}`;

    const signer = crypto.createSign('RSA-SHA256');
    signer.update(stringToSign);
    const signature = signer.sign(privateKey, 'base64');

    const signatureHeader = `keyId="${actorUri}#main-key",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="${signature}"`;

    await axios.post(inboxUrl, activity, {
        headers: {
            'Host': url.host,
            'Date': date,
            'Digest': `SHA-256=${digest}`,
            'Signature': signatureHeader,
            'Content-Type': 'application/activity+json',
            'Accept': 'application/activity+json'
        }
    });
  }
}
