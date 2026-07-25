import prisma from '../lib/prisma.js';
import { generateText } from '../lib/wingman-ai.js';
import { AutonomousService } from './AutonomousService.js';

export class SentimentAnalysisService {
  /**
   * Calculates the aggregated 'Group Aura' for a list of users, useful for chatrooms and groups.
   */
  static async calculateGroupAura(userIds: bigint[]) {
    try {
      if (userIds.length === 0) return 'standard';

      const profiles = await prisma.user_profiles.findMany({
        where: { user_id: { in: userIds } },
        select: { current_emotion: true }
      });

      const emotionCounts: Record<string, number> = {};
      profiles.forEach(p => {
        const e = (p.current_emotion || 'Neutral').toLowerCase();
        emotionCounts[e] = (emotionCounts[e] || 0) + 1;
      });

      // Simple heuristic based on majority or significant presence
      const total = profiles.length;

      if ((emotionCounts['happy'] || 0) + (emotionCounts['excited'] || 0) > total * 0.4) return 'warm';
      if ((emotionCounts['excited'] || 0) > total * 0.3) return 'electric';
      if ((emotionCounts['thoughtful'] || 0) > total * 0.4) return 'contemplative';
      if ((emotionCounts['cynical'] || 0) + (emotionCounts['mysterious'] || 0) > total * 0.3) return 'noir';
      if ((emotionCounts['melancholic'] || 0) > total * 0.3) return 'moody';

      return 'standard';
    } catch (err: any) {
      console.error('[SentimentService] calculateGroupAura failed:', err.message);
      return 'standard';
    }
  }

  /**
   * Analyzes recent activity for a user to determine their current emotional state.
   */
  static async analyzeUserSentiment(userId: bigint) {
    try {
      // 1. Fetch recent proximity artifacts and messages
      const [artifacts, messagesSent] = await Promise.all([
        prisma.proximity_artifacts.findMany({
          where: { user_id: userId },
          orderBy: { created_at: 'desc' },
          take: 5
        }),
        prisma.messages.findMany({
          where: { sender_id: userId },
          orderBy: { sent_at: 'desc' },
          take: 10
        })
      ]);

      if (artifacts.length === 0 && messagesSent.length === 0) {
        return 'Neutral';
      }

      const content = [
        ...artifacts.map(a => `Post: ${a.content}`),
        ...messagesSent.map(m => `Message: ${m.content}`)
      ].join('\n');

      const systemPrompt = `
        You are an emotional intelligence engine for a social app.
        Based on the user's recent posts and messages, identify their current dominant emotion.
        Choose from: Happy, Excited, Thoughtful, Cynical, Mysterious, Melancholic, or Neutral.
        Respond with ONLY the emotion word.
      `;

      const emotion = await generateText(systemPrompt, content, 0.7);
      const cleanEmotion = (emotion || 'Neutral').split('\n')[0]?.trim() || 'Neutral';

      // 2. Update user profile
      await prisma.user_profiles.updateMany({
        where: { user_id: userId },
        data: {
          current_emotion: cleanEmotion,
          emotion_updated_at: new Date()
        }
      });

      await AutonomousService.logAction('Emotional State Update', 'Completed', { userId: userId.toString(), emotion: cleanEmotion });

      return cleanEmotion;
    } catch (err: any) {
      console.error('[SentimentService] Analysis failed:', err.message);
      return 'Neutral';
    }
  }

  /**
   * Analyzes neighborhood sentiment based on aggregated proximity artifacts.
   */
  static async analyzeNeighborhoodSentiment(lat: number, lng: number, radiusM: number = 5000) {
    try {
      const artifacts = await prisma.proximity_artifacts.findMany({
        where: {
          location_lat: { gte: lat - 0.05, lte: lat + 0.05 },
          location_lng: { gte: lng - 0.05, lte: lng + 0.05 },
          created_at: { gte: new Date(Date.now() - 24 * 60 * 60 * 1000) } // Last 24h
        },
        take: 20
      });

      if (artifacts.length === 0) return { vibe: 'Neutral', sentiment: 0.5, keywords: [], summary: 'No recent activity' };

      const content = artifacts.map(a => a.content).join('\n');
      const systemPrompt = `
        Analyze the collective mood of this neighborhood based on these local posts.
        Return a JSON object with:
        - vibe: a single vibe word (Energetic, Chill, Gloomy, Tense, Vibrant)
        - sentiment: a float from 0.0 (negative) to 1.0 (positive)
        - keywords: an array of 3 trending keywords/topics
        - summary: a short 1-sentence description of the neighborhood mood
      `;

      const aiResponse = await generateText(systemPrompt, content, 0.6);
      try {
        const jsonMatch = aiResponse.match(/{[\s\S]*}/);
        if (jsonMatch) {
            return JSON.parse(jsonMatch[0]);
        }
      } catch (e) {}

      return { vibe: (aiResponse || 'Neutral').split('\n')[0]?.trim() || 'Neutral', sentiment: 0.5, keywords: [], summary: 'Mood analyzed via baseline' };
    } catch (err: any) {
      return { vibe: 'Neutral', sentiment: 0.5, keywords: [], summary: 'Analysis service unavailable' };
    }
  }
}
