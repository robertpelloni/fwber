/**
 * OpenAI-powered wingman service.
 * All profile novelty tools use GPT to generate dynamic, personalized content.
 */

import OpenAI from 'openai';

const openai = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });

/** Fetch a user's profile as a plain summary string for prompts */
async function getProfileSummary(userId: bigint): Promise<string> {
  const prisma = (await import('../lib/prisma.js')).default;
  const profile = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
  if (!profile) return 'No profile data available.';

  const prefs = typeof profile.preferences === 'string' ? JSON.parse(profile.preferences) : profile.preferences;
  const lookingFor = typeof profile.looking_for === 'string' ? JSON.parse(profile.looking_for) : profile.looking_for;
  const interests = typeof profile.interests === 'string' ? JSON.parse(profile.interests) : profile.interests;

  const parts: string[] = [];
  if (profile.display_name) parts.push(`Name: ${profile.display_name}`);
  if (profile.gender) parts.push(`Gender: ${profile.gender}`);
  if (profile.bio) parts.push(`Bio: ${profile.bio}`);
  if (profile.date_of_birth || profile.birthdate) {
    const dob = profile.date_of_birth || profile.birthdate;
    const age = dob ? Math.floor((Date.now() - new Date(dob as string | number).getTime()) / (365.25 * 24 * 60 * 60 * 1000)) : null;
    if (age) parts.push(`Age: ${age}`);
    parts.push(`Age: ${age}`);
  }
  if (profile.location_description) parts.push(`Location: ${profile.location_description}`);
  if (profile.occupation) parts.push(`Occupation: ${profile.occupation}`);
  if (profile.education) parts.push(`Education: ${profile.education}`);
  if (profile.height_cm) parts.push(`Height: ${profile.height_cm}cm`);
  if (profile.body_type) parts.push(`Body type: ${profile.body_type}`);
  if (profile.religion) parts.push(`Religion: ${profile.religion}`);
  if (profile.political_views) parts.push(`Politics: ${profile.political_views}`);
  if (profile.sexual_orientation) parts.push(`Orientation: ${profile.sexual_orientation}`);
  if (profile.relationship_style) parts.push(`Relationship style: ${profile.relationship_style}`);
  if (profile.drinking_status) parts.push(`Drinking: ${profile.drinking_status}`);
  if (profile.smoking_status) parts.push(`Smoking: ${profile.smoking_status}`);
  if (profile.cannabis_status) parts.push(`Cannabis: ${profile.cannabis_status}`);
  if (profile.zodiac_sign) parts.push(`Zodiac: ${profile.zodiac_sign}`);
  if (Array.isArray(interests) && interests.length) parts.push(`Interests: ${interests.join(', ')}`);
  if (Array.isArray(lookingFor) && lookingFor.length) parts.push(`Looking for: ${lookingFor.join(', ')}`);
  if (prefs?.hobbies?.length) parts.push(`Hobbies: ${prefs.hobbies.join(', ')}`);
  if (prefs?.occupation) parts.push(`Occupation preference: ${prefs.occupation}`);

  return parts.join('\n');
}

/** Generic OpenAI chat completion helper */
async function ask(system: string, user: string, temperature = 0.9): Promise<string> {
  const resp = await openai.chat.completions.create({
    model: 'gpt-4o-mini',
    messages: [
      { role: 'system', content: system },
      { role: 'user', content: user },
    ],
    temperature,
    max_tokens: 800,
  });
  return resp.choices[0]?.message?.content?.trim() || '';
}

// ─── Roast ──────────────────────────────────────────────────────────────────

export async function generateRoast(userId: bigint, mode: 'roast' | 'hype'): Promise<string> {
  const profile = await getProfileSummary(userId);
  const isRoast = mode === 'roast';

  const system = isRoast
    ? `You are a savage, witty dating profile roaster. Be brutally funny, creative, and specific to their profile. Use clever metaphors and pop culture references. Keep it under 4 sentences. Never be mean-spirited — roast with love. Be unique every time.`
    : `You are an enthusiastic, hyped-up dating profile cheerleader. Be over-the-top positive, creative, and specific to their profile. Use superlatives, emojis, and energy. Keep it under 4 sentences. Be unique every time.`;

  return ask(system, `Here's the dating profile:\n\n${profile}\n\n${isRoast ? 'Roast them!' : 'Hype them up!'}`);
}

export async function generatePublicRoast(name: string, job: string, trait: string, mode: 'roast' | 'hype'): Promise<string> {
  const isRoast = mode === 'roast';
  const system = isRoast
    ? `You are a savage, witty dating profile roaster. Be brutally funny and creative. Keep it under 4 sentences. Never be mean-spirited. Be unique every time.`
    : `You are an enthusiastic hyped-up profile cheerleader. Be over-the-top positive with emojis and energy. Keep it under 4 sentences. Be unique every time.`;

  return ask(system, `Name: ${name}, Job: ${job}, Trait: ${trait}\n\n${isRoast ? 'Roast them!' : 'Hype them up!'}`);
}

// ─── Vibe Check ─────────────────────────────────────────────────────────────

export async function generateVibeCheck(userId: bigint): Promise<{ green_flags: string[]; red_flags: string[] }> {
  const profile = await getProfileSummary(userId);
  const result = await ask(
    `You are a witty dating profile analyst. Given a dating profile, generate 3-5 specific green flags and 2-4 specific red flags based on their actual profile data. Be clever, funny, and insightful. Each flag should be 3-8 words. Respond in JSON: { "green_flags": [...], "red_flags": [...] }`,
    `Profile:\n${profile}`,
    0.85
  );
  try {
    const cleaned = result.replace(/```json\n?/g, '').replace(/```/g, '').trim();
    return JSON.parse(cleaned);
  } catch {
    return {
      green_flags: ['Has a profile (that\'s a start)', 'Bold enough to be here', 'Gives off main character energy'],
      red_flags: ['Profile might be haunted', 'Could be a catfish', 'Suspiciously vague'],
    };
  }
}

// ─── Quirk Check ────────────────────────────────────────────────────────────

export async function generateQuirkCheck(quirk: string): Promise<{ flag_type: string; reason: string; emoji: string }> {
  const result = await ask(
    `You are a witty dating profile analyst. Given a personal quirk, classify it as "Green Flag", "Red Flag", or "Beige Flag" and explain why in a funny, clever way (1-2 sentences). Respond in JSON: { "flag_type": "...", "reason": "...", "emoji": "..." }`,
    `Quirk: "${quirk}"`,
    0.9
  );
  try {
    const cleaned = result.replace(/```json\n?/g, '').replace(/```/g, '').trim();
    return JSON.parse(cleaned);
  } catch {
    return { flag_type: 'Beige Flag', reason: "It's not a red flag, it's not a green flag... it just *is*.", emoji: '🤷' };
  }
}

// ─── Profile Analysis ───────────────────────────────────────────────────────

export async function generateProfileAnalysis(userId: bigint): Promise<{
  score: number;
  strengths: string[];
  weaknesses: string[];
  tips: string[];
}> {
  const profile = await getProfileSummary(userId);
  const result = await ask(
    `You are a dating profile expert. Analyze the profile and give a score 0-100, 2-3 strengths, 2-3 weaknesses, and 2-3 actionable tips. Be specific to their actual profile data. Respond in JSON: { "score": number, "strengths": [...], "weaknesses": [...], "tips": [...] }`,
    `Profile:\n${profile}`,
    0.7
  );
  try {
    const cleaned = result.replace(/```json\n?/g, '').replace(/```/g, '').trim();
    return JSON.parse(cleaned);
  } catch {
    return {
      score: 50,
      strengths: ['Profile exists'],
      weaknesses: ['Could use more details'],
      tips: ['Add more photos and a longer bio'],
    };
  }
}

// ─── Cosmic Match ───────────────────────────────────────────────────────────

export async function generateCosmicMatch(userId: bigint): Promise<{
  best_match: string;
  best_reason: string;
  worst_match: string;
  worst_reason: string;
}> {
  const profile = await getProfileSummary(userId);
  const result = await ask(
    `You are a mystical dating oracle with cosmic energy. Given someone's profile, predict their best and worst match personality types. Be creative, funny, specific to their profile. Respond in JSON: { "best_match": "...", "best_reason": "...", "worst_match": "...", "worst_reason": "..." }`,
    `Profile:\n${profile}`,
    0.95
  );
  try {
    const cleaned = result.replace(/```json\n?/g, '').replace(/```/g, '').trim();
    return JSON.parse(cleaned);
  } catch {
    return {
      best_match: 'A creative soul who shares your curiosity',
      best_reason: 'Your combined energy creates a magnetic connection.',
      worst_match: 'Someone who fears spontaneity',
      worst_reason: 'Your free spirit would clash with rigidity.',
    };
  }
}

// ─── Nemesis ────────────────────────────────────────────────────────────────

export async function generateNemesis(userId: bigint): Promise<{
  nemesis_type: string;
  clashing_traits: string[];
  why_it_would_fail: string;
  scientific_explanation: string;
}> {
  const profile = await getProfileSummary(userId);
  const result = await ask(
    `You are a dating profile analyst with a dark sense of humor. Given someone's profile, describe their dating nemesis — the exact type of person who would be their worst match. Be specific, witty, and reference their actual traits. Respond in JSON: { "nemesis_type": "...", "clashing_traits": ["...", "..."], "why_it_would_fail": "...", "scientific_explanation": "..." }`,
    `Profile:\n${profile}`,
    0.95
  );
  try {
    const cleaned = result.replace(/```json\n?/g, '').replace(/```/g, '').trim();
    return JSON.parse(cleaned);
  } catch {
    return {
      nemesis_type: 'The Chronically Basic',
      clashing_traits: ['Orders pumpkin spice lattes unironically', 'Thinks hiking is a personality trait'],
      why_it_would_fail: 'You need depth; they need a filter.',
      scientific_explanation: 'Opposites attract, but only in magnetism — not in conversation.',
    };
  }
}

// ─── Dating Fortune ─────────────────────────────────────────────────────────

export async function generateFortune(userId: bigint): Promise<{ fortune: string }> {
  const profile = await getProfileSummary(userId);
  const result = await ask(
    `You are a mystical dating fortune teller. Given someone's profile, generate a creative, personalized dating fortune — a prediction about their love life. Be specific to their profile, witty, and optimistic. 2-3 sentences max.`,
    `Profile:\n${profile}`,
    0.95
  );
  return { fortune: result || 'The stars are aligning in your favor. A surprise encounter awaits.' };
}
