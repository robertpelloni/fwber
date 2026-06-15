import prisma from '../lib/prisma.js';
import { pushNotification } from '../socket.js';
import { AutonomousService } from './AutonomousService.js';

export class ActivityNotificationService {
  /**
   * Create an in-app notification and push it via WebSocket.
   */
  static async notify(userId: bigint, title: string, body: string, metadata: any = {}) {
    try {
      const notification = await prisma.notifications.create({
        data: {
          user_id: userId,
          title,
          body,
          created_at: new Date()
        }
      });

      // Push real-time notification
      pushNotification(Number(userId), {
        id: Number(notification.id),
        title,
        body,
        createdAt: notification.created_at,
        metadata
      });

      await AutonomousService.logAction('Activity Notification', 'Completed', { userId: Number(userId), type: metadata.type });
    } catch (err: any) {
      console.error('[ActivityNotificationService] Failed to notify user:', err.message);
      await AutonomousService.logAction('Activity Notification', 'Failed', { userId: Number(userId), error: err.message });
    }
  }

  /**
   * Specifically handle ActivityPub "Follow" notifications.
   */
  static async notifyFollow(targetUserId: bigint, actorName: string, actorDomain: string) {
    const title = 'New Federated Follower';
    const body = `${actorName}@${actorDomain} is now following you.`;
    await this.notify(targetUserId, title, body, { type: 'follow', actor: `${actorName}@${actorDomain}` });
  }

  /**
   * Specifically handle ActivityPub "Like" notifications.
   */
  static async notifyLike(targetUserId: bigint, actorName: string, actorDomain: string, objectTitle: string) {
    const title = 'New Interaction';
    const body = `${actorName}@${actorDomain} liked your post: "${objectTitle}"`;
    await this.notify(targetUserId, title, body, { type: 'like', actor: `${actorName}@${actorDomain}` });
  }

  /**
   * Specifically handle ActivityPub "Announce" (Boost) notifications.
   */
  static async notifyBoost(targetUserId: bigint, actorName: string, actorDomain: string, objectTitle: string) {
    const title = 'New Boost';
    const body = `${actorName}@${actorDomain} boosted your post: "${objectTitle}"`;
    await this.notify(targetUserId, title, body, { type: 'boost', actor: `${actorName}@${actorDomain}` });
  }

  /**
   * Specifically handle ActivityPub "Mention" notifications.
   */
  static async notifyMention(targetUserId: bigint, actorName: string, actorDomain: string, content: string) {
    const title = 'You were mentioned';
    const body = `${actorName}@${actorDomain} mentioned you in a post.`;
    await this.notify(targetUserId, title, body, {
        type: 'mention',
        actor: `${actorName}@${actorDomain}`,
        preview: content.substring(0, 100)
    });
  }
}
