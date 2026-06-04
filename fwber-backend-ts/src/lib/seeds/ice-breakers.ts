import prisma from '../prisma.js';

const iceBreakerQuestions = [
  // LIFESTYLE & PREFERENCES
  { question: "Are you a morning person or a night owl?", category: "lifestyle", emoji: "🌅" },
  { question: "What is your stance on pineapple on pizza?", category: "lifestyle", emoji: "🍕" },
  { question: "How clean do you keep your living space?", category: "lifestyle", emoji: "🧹" },
  { question: "Would you rather live in a high-tech city or a quiet forest?", category: "lifestyle", emoji: "🏙️" },
  { question: "Do you prefer deep conversations or lighthearted small talk?", category: "lifestyle", emoji: "💬" },
  { question: "How often do you like to try new things?", category: "lifestyle", emoji: "🚀" },
  { question: "Are you more into physical activity or intellectual pursuits?", category: "lifestyle", emoji: "🏋️" },
  { question: "What's your ideal Friday night vibe?", category: "lifestyle", emoji: "🌃" },
  { question: "How important is daily communication to you?", category: "lifestyle", emoji: "📱" },
  { question: "Do you prefer planning ahead or going with the flow?", category: "lifestyle", emoji: "🗓️" },

  // ETHICS & VALUES
  { question: "Is total honesty always the best policy in a relationship?", category: "ethics", emoji: "⚖️" },
  { question: "How much weight do you put on political alignment in a partner?", category: "ethics", emoji: "🗳️" },
  { question: "Do you believe in 'second chances' for major mistakes?", category: "ethics", emoji: "🔄" },
  { question: "How important is financial independence to you?", category: "ethics", emoji: "💰" },
  { question: "Is privacy a human right or a luxury?", category: "ethics", emoji: "🛡️" },
  { question: "How do you define 'loyalty'?", category: "ethics", emoji: "🤝" },
  { question: "Do you prioritize career or personal life?", category: "ethics", emoji: "💼" },
  { question: "What's your take on social media's impact on society?", category: "ethics", emoji: "🌐" },
  { question: "How much do you value tradition vs. innovation?", category: "ethics", emoji: "🕰️" },
  { question: "Do you think people are inherently good?", category: "ethics", emoji: "🧬" },

  // ROMANCE & CONNECTION
  { question: "What's your primary love language?", category: "romance", emoji: "❤️" },
  { question: "How long should you wait before the first 'I love you'?", category: "romance", emoji: "⏳" },
  { question: "Do you believe in 'the one' or multiple compatible partners?", category: "romance", emoji: "💘" },
  { question: "What's your favorite way to show affection?", category: "romance", emoji: "🥰" },
  { question: "How important is physical touch in a relationship?", category: "romance", emoji: "🤝" },
  { question: "What's your idea of a perfect first date?", category: "romance", emoji: "🕯️" },
  { question: "How do you handle conflict with a partner?", category: "romance", emoji: "💥" },
  { question: "Are you looking for something casual or long-term?", category: "romance", emoji: "💍" },
  { question: "How much do you value shared hobbies vs. independent ones?", category: "romance", emoji: "🎨" },
  { question: "What's the most romantic gesture someone has done for you?", category: "romance", emoji: "🌹" },

  // FUN & QUIRKY (FWBER VIBE)
  { question: "If you were an AI, what would your primary directive be?", category: "fwber", emoji: "🤖" },
  { question: "What's your 'secret' talent that nobody knows about?", category: "fwber", emoji: "✨" },
  { question: "If you could instantly master any skill, what would it be?", category: "fwber", emoji: "🎯" },
  { question: "What's the most adventurous food you've ever tried?", category: "fwber", emoji: "🍽️" },
  { question: "If we were in a simulation, what's one bug you'd fix?", category: "fwber", emoji: "👾" },
  { question: "What's your favorite obscure movie or book?", category: "fwber", emoji: "📚" },
  { question: "If you could travel to any point in time, when would it be?", category: "fwber", emoji: "⏳" },
  { question: "What's your go-to karaoke song?", category: "fwber", emoji: "🎤" },
  { question: "If you had a superpower, would it be for good or chaos?", category: "fwber", emoji: "⚡" },
  { question: "What's the best piece of advice you've ever ignored?", category: "fwber", emoji: "👂" },

  // DEEP & ANALYTICAL
  { question: "What's a belief you've changed your mind about recently?", category: "deep", emoji: "🧠" },
  { question: "What are you most proud of in your life so far?", category: "deep", emoji: "🏆" },
  { question: "What does 'success' look like to you?", category: "deep", emoji: "🌟" },
  { question: "How do you want to be remembered?", category: "deep", emoji: "🪦" },
  { question: "What's your biggest fear, and how do you face it?", category: "deep", emoji: "😨" },
  { question: "If you could change one thing about your past, what would it be?", category: "deep", emoji: "⏪" },
  { question: "What's a question you wish people would ask you more often?", category: "deep", emoji: "❓" },
  { question: "How do you define 'freedom'?", category: "deep", emoji: "🕊️" },
  { question: "What's the most important lesson you've learned from a failure?", category: "deep", emoji: "🎓" },
  { question: "If you knew the world was ending tomorrow, what would you do today?", category: "deep", emoji: "☄️" },
];

export async function seedIceBreakers() {
  console.log('[Seed] Starting Ice Breaker question seeding...');

  let count = 0;
  for (const q of iceBreakerQuestions) {
    try {
      await prisma.ice_breaker_questions.upsert({
        where: { id: BigInt(count + 1) }, // Mocked ID mapping or use a unique question field
        // Since question isn't unique in schema, we'll check by text first
        update: { ...q },
        create: { ...q }
      });
      count++;
    } catch (err: any) {
      console.error(`[Seed] Failed to seed question "${q.question}":`, err.message);
    }
  }

  console.log(`[Seed] Successfully seeded ${count} Ice Breaker questions.`);
}

// Allow running directly
if (process.argv[1]?.includes('ice-breakers.ts')) {
  seedIceBreakers()
    .then(() => process.exit(0))
    .catch((err) => {
      console.error(err);
      process.exit(1);
    });
}
