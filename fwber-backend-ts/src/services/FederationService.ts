import prisma from '../lib/prisma.js';
import axios from 'axios';
import crypto from 'crypto';
import { AutonomousService } from './AutonomousService.js';
import { ActivityNotificationService } from './ActivityNotificationService.js';

export class FederationService {
  /**
   * Resolves a remote WebFinger handle (e.g. @user@domain.com) to an ActivityPub actor URI.
   */
  async resolveWebFinger(handle: string): Promise<string | null> {
    const cleanHandle = handle.startsWith('@') ? handle.substring(1) : handle;
    const [username, domain] = cleanHandle.split('@');

    if (!username || !domain) {
      console.error(`[Federation] Invalid handle for WebFinger: ${handle}`);
      return null;
    }

    const webfingerUrl = `https://${domain}/.well-known/webfinger?resource=acct:${username}@${domain}`;
    console.log(`[Federation] Resolving WebFinger: ${webfingerUrl}`);

    if (['localhost', '127.0.0.1'].includes(domain) || domain.startsWith('10.') || domain.startsWith('192.168.')) {
      console.warn(`[Federation] Blocked SSRF attempt during WebFinger: ${domain}`);
      return null;
    }

    try {
      const res = await axios.get(webfingerUrl, {
        headers: { Accept: 'application/jrd+json, application/json' },
        timeout: 5000
      });

      const links = res.data.links || [];
      const selfLink = links.find((l: any) => l.rel === 'self' && (l.type === 'application/activity+json' || l.type === 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'));

      if (selfLink && selfLink.href) {
        console.log(`[Federation] Resolved ${handle} to ${selfLink.href}`);
        return selfLink.href;
      }

      console.warn(`[Federation] No ActivityPub self-link found for ${handle}`);
      return null;
    } catch (err: any) {
      console.error(`[Federation] WebFinger resolution failed for ${handle}:`, err.message);
      return null;
    }
  }

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
        await this.handleCreate(activity, targetUserId);
        break;
      case 'Like':
        await this.handleLike(activity, targetUserId);
        break;
      case 'Announce':
        await this.handleAnnounce(activity, targetUserId);
        break;
      default:
        console.log(`[Federation] Unhandled activity type: ${activity.type}`);
    }
  }

  /**
   * Processes incoming Create activities (remote posts).
   * Note: In a production environment, this would often be filtered by following status.
   */
  private async handleCreate(activity: any, targetUserId: bigint) {
    const actorUri = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    const object = activity.object;

    if (!object || !object.id) {
        console.warn(`[Federation] Received malformed Create activity from ${actorUri}`);
        return;
    }

    console.log(`[Federation] Received Create activity from ${actorUri} for user ${targetUserId}`);

    // Note: Persistent storage of the activity itself happens in the route handler.
    // This service method is for logic-level processing (e.g. notifications on mentions).
    if (object.type === 'Note') {
        const content = object.content || '';
        if (content.includes(`@${targetUserId}`)) {
            // Placeholder for mention notification logic
            console.log(`[Federation] User ${targetUserId} was mentioned by ${actorUri}`);
        }
    }
  }

  private async handleLike(activity: any, targetUserId: bigint) {
    const actorUri = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    const objectUri = typeof activity.object === 'string' ? activity.object : activity.object.id;

    console.log(`[Federation] ${actorUri} liked ${objectUri} for user ${targetUserId}`);

    if (objectUri.includes('api.fwber.me')) {
        const outboxItem = await prisma.federation_outbox.findUnique({
            where: { activity_id: objectUri }
        });

        if (outboxItem) {
            try {
              const actorRes = await axios.get(actorUri, { headers: { Accept: 'application/activity+json' }});
              const actorName = actorRes.data.preferredUsername || 'Someone';
              const actorDomain = new URL(actorUri).hostname;
              const meta = outboxItem.payload as any;
              const objectTitle = meta?.object?.content || 'your post';

              await ActivityNotificationService.notifyLike(targetUserId, actorName, actorDomain, objectTitle);
            } catch (err) {
              await ActivityNotificationService.notifyLike(targetUserId, 'Someone', 'remote', 'your post');
            }
        }
    }
  }

  private async handleAnnounce(activity: any, targetUserId: bigint) {
    const actorUri = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    const objectUri = typeof activity.object === 'string' ? activity.object : activity.object.id;

    console.log(`[Federation] ${actorUri} announced (boosted) ${objectUri} for user ${targetUserId}`);

    if (objectUri.includes('api.fwber.me')) {
        const outboxItem = await prisma.federation_outbox.findUnique({
            where: { activity_id: objectUri }
        });

        if (outboxItem) {
            try {
              const actorRes = await axios.get(actorUri, { headers: { Accept: 'application/activity+json' }});
              const actorName = actorRes.data.preferredUsername || 'Someone';
              const actorDomain = new URL(actorUri).hostname;
              const meta = outboxItem.payload as any;
              const objectTitle = meta?.object?.content || 'your post';

              await ActivityNotificationService.notifyBoost(targetUserId, actorName, actorDomain, objectTitle);
            } catch (err) {
              await ActivityNotificationService.notifyBoost(targetUserId, 'Someone', 'remote', 'your post');
            }
        }
    }
  }

  private async handleFollow(activity: any, targetUserId: bigint) {
    const actorUri = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    const apiDomain = process.env.API_DOMAIN || 'api.fwber.me';
    const targetUri = `https://${apiDomain}/api/federation/actors/${targetUserId}`;

    console.log(`[Federation] ${actorUri} wants to follow local user ${targetUserId}`);

    try {
        const user = await prisma.users.findUnique({
            where: { id: targetUserId },
            select: { private_key: true }
        });

        let remoteUser = await prisma.users.findUnique({
            where: { actor_uri: actorUri }
        });

        if (!remoteUser) {
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

      await prisma.federation_follows.create({
        data: {
          actor_uri: actorUri,
          target_uri: targetUri,
          status: 'accepted'
        }
      });

      const actorDomain = new URL(actorUri).hostname;
      await ActivityNotificationService.notifyFollow(targetUserId, remoteUser.name, actorDomain);

      if (user?.private_key) {
          const acceptActivity = {
              '@context': 'https://www.w3.org/ns/activitystreams',
              id: `https://${apiDomain}/api/federation/activities/accept-${Date.now()}`,
              type: 'Accept',
              actor: targetUri,
              object: activity
          };

          try {
              const actorRes = await axios.get(actorUri, { headers: { Accept: 'application/activity+json' }});
              const inbox = actorRes.data.inbox;
              if (inbox) {
                  await this.sendSignedRequest(inbox, acceptActivity, user.private_key, targetUri);
              }
          } catch (err: any) {}
      }
    } catch (e) {
      console.error('[Federation] Error recording follow:', e);
    }
  }

  private async handleUndoFollow(activity: any, targetUserId: bigint) {
    const actorUri = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    const apiDomain = process.env.API_DOMAIN || 'api.fwber.me';
    const targetUri = `https://${apiDomain}/api/federation/actors/${targetUserId}`;
    try {
        await prisma.federation_follows.deleteMany({
            where: { actor_uri: actorUri, target_uri: targetUri }
        });
    } catch (err) {}
  }

  private async handleAccept(activity: any, targetUserId: bigint) {
    const actorUri = typeof activity.actor === 'string' ? activity.actor : activity.actor.id;
    const apiDomain = process.env.API_DOMAIN || 'api.fwber.me';
    const targetUri = `https://${apiDomain}/api/federation/actors/${targetUserId}`;
    try {
        await prisma.federation_follows.updateMany({
            where: { actor_uri: targetUri, target_uri: actorUri },
            data: { status: 'accepted' }
        });
    } catch (err) {}
  }

  async broadcastUpdate(userId: bigint, objectPayload: any, activityType: string = 'Create') {
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { private_key: true, name: true }
    });

    if (!user || !user.private_key) return;

    const apiDomain = process.env.API_DOMAIN || 'api.fwber.me';
    const actorUri = `https://${apiDomain}/api/federation/actors/${userId}`;
    const followers = await prisma.federation_follows.findMany({
        where: { target_uri: actorUri, status: 'accepted' }
    });

    if (followers.length === 0) return;

    const activity = {
        '@context': 'https://www.w3.org/ns/activitystreams',
        id: `https://${apiDomain}/api/federation/activities/${activityType.toLowerCase()}-${Date.now()}`,
        type: activityType,
        actor: actorUri,
        object: objectPayload
    };

    const taskLabel = `ActivityPub ${activityType} Broadcast (User ${userId})`;
    await AutonomousService.logAction(taskLabel, 'Started', { follower_count: followers.length, object_type: objectPayload.type });

    let successCount = 0;
    for (const follower of followers) {
        try {
            const actorRes = await axios.get(follower.actor_uri, { headers: { Accept: 'application/activity+json' }});
            const inbox = actorRes.data.inbox;

            if (inbox) {
                await this.sendSignedRequest(inbox, activity, user.private_key, actorUri);
                successCount++;
            }
        } catch (err: any) {}
    }

    await AutonomousService.logAction(taskLabel, 'Completed', {
        success_count: successCount,
        total_count: followers.length
    });
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
