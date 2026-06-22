import prisma from '../lib/prisma.js';
import { generateText } from '../lib/wingman-ai.js';
import { AutonomousService } from './AutonomousService.js';
import { ActivityNotificationService } from './ActivityNotificationService.js';

export class WingmanService {
  /**
   * Analyzes the conversation between two users and generates a proactive nudge
   * if the timing is right (e.g., they've been chatting for a while).
   */
  static async analyzeAndNudge(senderId: bigint, receiverId: bigint) {
    try {
      // 1. Fetch recent messages
      const messages = await prisma.messages.findMany({
        where: {
          OR: [
            { sender_id: senderId, receiver_id: receiverId },
            { sender_id: receiverId, receiver_id: senderId },
          ],
        },
        orderBy: { sent_at: 'desc' },
        take: 10,
      });

      // Only nudge if there's some history (e.g., > 5 messages) but not too many recently
      if (messages.length < 5) return null;

      // 2. Fetch profiles for context
      const senderProfile = await prisma.user_profiles.findFirst({ where: { user_id: senderId } });
      const receiverProfile = await prisma.user_profiles.findFirst({ where: { user_id: receiverId } });

      const context = `
        Sender (${senderProfile?.display_name}): ${senderProfile?.bio || 'No bio'}
        Receiver (${receiverProfile?.display_name}): ${receiverProfile?.bio || 'No bio'}
        Recent Messages:
        ${messages.reverse().map(m => `${m.sender_id === senderId ? 'Sender' : 'Receiver'}: ${m.content}`).join('\n')}
      `;

      const systemPrompt = `
        You are a proactive AI Wingman for a dating app called "fwber".
        Your goal is to help users move from chatting to meeting in real life.
        Analyze the conversation context and generate a "proactive nudge" — a suggestion for the sender to move the conversation forward.
        If they share an interest, suggest a related venue in the Detroit area.
        If they've been chatting for a few days, suggest asking for a date.

        Respond in JSON: { "should_nudge": boolean, "nudge_text": "...", "reason": "..." }
        Only set should_nudge to true if it feels like a natural progression.
      `;

      const result = await generateText(systemPrompt, context, 0.8);
      const parsed = JSON.parse(result.replace(/```json\n?/g, '').replace(/```/g, '').trim());

      if (parsed.should_nudge) {
        await AutonomousService.logAction('Proactive Wingman Nudge', 'Completed', {
            sender_id: senderId.toString(),
            receiver_id: receiverId.toString(),
            nudge: parsed.nudge_text
        });

        // Send an in-app notification to the sender as a "Wingman Nudge"
        await ActivityNotificationService.notify(
          senderId,
          'Wingman Nudge',
          parsed.nudge_text,
          { type: 'wingman_nudge', targetId: receiverId.toString() }
        );

        return parsed.nudge_text;
      }

      return null;
    } catch (err: any) {
      console.error('[WingmanService] Analysis error:', err.message);
      return null;
    }
  }

  /**
   * Generates ice breakers or reply suggestions based on the conversation history.
   */
  static async getSuggestions(userId: bigint, matchPartnerId: bigint, mode: 'ice-breaker' | 'reply') {
      try {
          const messages = await prisma.messages.findMany({
              where: {
                  OR: [
                      { sender_id: userId, receiver_id: matchPartnerId },
                      { sender_id: matchPartnerId, receiver_id: userId },
                  ],
              },
              orderBy: { sent_at: 'desc' },
              take: 10,
          });

          const partnerProfile = await prisma.user_profiles.findFirst({ where: { user_id: matchPartnerId } });

          const systemPrompt = `
            You are a witty AI Wingman. Generate 3 creative ${mode} suggestions for the user.
            Context: Chatting with ${partnerProfile?.display_name || 'a match'}.
            Partner Bio: ${partnerProfile?.bio || 'Not provided'}
            ${messages.length > 0 ? `Recent Messages:\n${messages.reverse().map(m => `${m.sender_id === userId ? 'User' : 'Partner'}: ${m.content}`).join('\n')}` : 'No messages yet.'}

            Respond in JSON array of strings: ["suggestion1", "suggestion2", "suggestion3"]
          `;

          const result = await generateText(systemPrompt, 'Generate suggestions.', 0.9);
          const suggestions = JSON.parse(result.replace(/```json\n?/g, '').replace(/```/g, '').trim());

          return Array.isArray(suggestions) ? suggestions : [];
      } catch (err: any) {
          console.error('[WingmanService] Suggestion error:', err.message);
          return [];
      }
  }
}
