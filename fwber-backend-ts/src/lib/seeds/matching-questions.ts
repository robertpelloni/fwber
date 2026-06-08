import prisma from '../prisma.js';

const matchingQuestions = [
  // ============================================================
  // LIFESTYLE & DAILY HABITS
  // ============================================================
  { text: "How tidy is your living space?", category: "lifestyle", options: ["Spotless", "Messy but I know where everything is", "Somewhat tidy", "Disaster zone"] },
  { text: "Could you date someone who is very messy?", category: "lifestyle", options: ["Yes", "No"] },
  { text: "How often do you brush your teeth?", category: "lifestyle", options: ["Twice or more daily", "Once daily", "Occasionally", "Rarely"] },
  { text: "How important is it that your partner is adventurous?", category: "lifestyle", options: ["Very important", "Somewhat important", "Not important"] },
  { text: "Do you prefer to plan everything or go with the flow?", category: "lifestyle", options: ["I plan everything", "I go with the flow", "A bit of both"] },
  { text: "If the power went out for 3 days, what would you do?", category: "lifestyle", options: ["Go camping / enjoy nature", "Catch up on reading", "Sleep and relax", "Panic"] },
  { text: "Where do you prefer to spend a rainy evening?", category: "lifestyle", options: ["Out at a bar or club", "Home with a movie", "Exploring somewhere new", "Gaming or online"] },
  { text: "How do you feel about tattoos and piercings on a partner?", category: "lifestyle", options: ["Love them — the more the better", "A few are fine", "Prefer none", "No preference"] },
  { text: "Are you a morning person or a night owl?", category: "lifestyle", options: ["Early bird", "Night owl", "It depends on the day"] },
  { text: "How important is it that you and your partner have similar eating habits?", category: "lifestyle", options: ["Very important", "Somewhat important", "Not important"] },
  { text: "Do you cook for yourself or mostly eat out / order in?", category: "lifestyle", options: ["I cook most meals", "Mix of cooking and takeout", "Almost entirely takeout"] },
  { text: "How do you feel about pets in the home?", category: "lifestyle", options: ["I need pets — they're family", "I like pets but don't need them", "I'm indifferent", "I'd rather not have pets"] },
  { text: "Do you want kids someday?", category: "lifestyle", options: ["Yes, definitely", "Maybe someday", "No", "I already have kids"] },
  { text: "How important is physical fitness to you?", category: "lifestyle", options: ["Very — I work out regularly", "Moderately — I stay active", "Not very — I'm more sedentary"] },
  { text: "What's your relationship with alcohol?", category: "lifestyle", options: ["I drink regularly / socially", "I drink occasionally", "I rarely drink", "I don't drink at all"] },
  { text: "What's your relationship with smoking / vaping?", category: "lifestyle", options: ["I smoke / vape", "I don't but I don't mind if others do", "I'd rather not be around it", "It's a dealbreaker"] },
  { text: "How important is travel to you?", category: "lifestyle", options: ["Essential — I travel as much as possible", "I enjoy occasional trips", "I prefer staying close to home"] },
  { text: "How do you manage money?", category: "lifestyle", options: ["Strict budgeter — every dollar is tracked", "Generally responsible but flexible", "I tend to spend freely", "What money?"] },
  { text: "Could you date someone with significantly different income or spending habits?", category: "lifestyle", options: ["Yes, money isn't that important to me", "It would depend on the person", "No, financial compatibility matters a lot"] },
  { text: "What's your ideal living situation with a partner?", category: "lifestyle", options: ["Living together", "Living separately but close", "Living separately — I value my space", "No strong preference"] },

  // ============================================================
  // ROMANCE & RELATIONSHIPS
  // ============================================================
  { text: "Is there such a thing as too much sex in a relationship?", category: "romance", options: ["Yes", "No"] },
  { text: "Could you date someone who is very quiet?", category: "romance", options: ["Yes", "No"] },
  { text: "What is your stance on open relationships?", category: "romance", options: ["I'm open to them", "I prefer monogamy", "I'm currently in one"] },
  { text: "What's your love language?", category: "romance", options: ["Physical touch", "Words of affirmation", "Acts of service", "Quality time", "Receiving gifts"] },
  { text: "How do you feel about public displays of affection?", category: "romance", options: ["Love it — hold my hand everywhere", "I'm okay with light PDA", "I prefer keeping affection private"] },
  { text: "How soon is too soon to say 'I love you'?", category: "romance", options: ["Weeks — if it feels right, say it", "A few months", "At least 6 months", "Whenever it's genuinely felt"] },
  { text: "How often do you want to text / communicate when dating someone?", category: "romance", options: ["All day, every day", "A few times a day", "Once a day is fine", "I prefer less frequent check-ins"] },
  { text: "How do you handle jealousy?", category: "romance", options: ["I rarely get jealous", "I get a little jealous but handle it", "I can be pretty jealous", "Jealousy is a red flag for me"] },
  { text: "How important is physical chemistry to you?", category: "romance", options: ["Essential — without it, nothing works", "Important but not everything", "Less important than emotional connection"] },
  { text: "Could you be happy in a long-distance relationship?", category: "romance", options: ["Yes, if the connection is strong", "Only temporarily", "No, I need physical proximity"] },
  { text: "How do you feel about your partner having close friends of the opposite sex?", category: "romance", options: ["Totally fine — trust is essential", "I'm okay with it with boundaries", "It would bother me"] },
  { text: "What's your ideal pace for a new relationship?", category: "romance", options: ["Fast — I fall hard and quick", "Moderate — steady progress", "Slow — I take my time opening up"] },
  { text: "Do you believe in love at first sight?", category: "romance", options: ["Yes", "No — real love takes time", "Maybe — I'm open to the idea"] },
  { text: "How soon after meeting someone would you want to be exclusive?", category: "romance", options: ["Right away if the spark is there", "After a few weeks of dating", "After a couple months", "I don't believe in forced timelines"] },
  { text: "How important is it that your partner gets along with your friends and family?", category: "romance", options: ["Very important", "Somewhat important", "Not important — they're separate parts of my life"] },
  { text: "When you're upset with your partner, what do you typically do?", category: "romance", options: ["Talk it out immediately", "Take some space and then discuss", "Shut down and hope it passes", "Write it out or text my feelings"] },
  { text: "How do you feel about couples counseling?", category: "romance", options: ["I'm open to it — relationships take work", "Only as a last resort", "I don't believe in it"] },

  // ============================================================
  // ETHICS, VALUES & WORLDVIEW
  // ============================================================
  { text: "Do you believe in fate, or do you think we create our own paths?", category: "ethics", options: ["I believe in fate", "I believe in free choice", "A bit of both"] },
  { text: "Could you date someone who follows a religion you don't share?", category: "ethics", options: ["Yes, easily", "Maybe, if they're not too devout", "Probably not", "It depends on the religion"] },
  { text: "Is total honesty always required in a relationship?", category: "ethics", options: ["Yes — no exceptions", "White lies are okay to protect feelings", "Some privacy is healthy even in love"] },
  { text: "How important are shared political views in a partner?", category: "ethics", options: ["Very — I couldn't date someone with opposite views", "Somewhat — we need to agree on the big stuff", "Not important — different perspectives keep things interesting"] },
  { text: "Do you believe climate change is a serious issue that requires personal action?", category: "ethics", options: ["Yes, and I try to do my part", "Yes, but I don't change my lifestyle much", "I'm skeptical", "No"] },
  { text: "How do you feel about animal rights and vegetarianism / veganism?", category: "ethics", options: ["I'm vegan / vegetarian for ethical reasons", "I eat meat but respect the choice", "I think it's unnecessary", "I don't have strong feelings either way"] },
  { text: "Is it ever okay to lie to protect someone's feelings?", category: "ethics", options: ["Yes, kindness matters more than blunt truth", "Rarely, and only about small things", "No — honesty is always the best policy"] },
  { text: "Do you think people can truly change, or are core traits fixed?", category: "ethics", options: ["People can change significantly", "People change a little but stay mostly the same", "Core traits are basically fixed"] },
  { text: "How do you feel about gun ownership?", category: "ethics", options: ["I own guns / support gun rights", "I'm okay with responsible ownership", "I'm uncomfortable with guns", "I'm strongly against them"] },
  { text: "How important is social justice to you?", category: "ethics", options: ["Very — I'm actively involved", "I care but don't actively participate", "I care about fairness but dislike politics", "Not a priority for me"] },
  { text: "Could you date someone with a criminal record?", category: "ethics", options: ["It depends on what they did and when", "Probably not", "Everyone deserves a second chance", "Absolutely not"] },
  { text: "Do you think cheating is ever forgivable?", category: "ethics", options: ["Never — it's an instant dealbreaker", "Maybe, depending on the circumstances", "Yes, people make mistakes"] },
  { text: "How important is loyalty to you compared to personal freedom?", category: "ethics", options: ["Loyalty comes first — always", "They're equally important", "Personal freedom is more important", "It depends on the situation"] },

  // ============================================================
  // PERSONALITY & EMOTIONAL STYLE
  // ============================================================
  { text: "Are you more introverted or extroverted?", category: "personality", options: ["Very introverted", "Somewhat introverted", "Somewhat extroverted", "Very extroverted"] },
  { text: "What kind of humor do you connect with most?", category: "personality", options: ["Sarcastic / dry", "Goofy / silly", "Dark / edgy", "Wholesome / dad jokes"] },
  { text: "How emotionally expressive are you?", category: "personality", options: ["Very — I wear my heart on my sleeve", "Moderately — I open up with the right person", "Not very — I keep things inside"] },
  { text: "How do you recharge after a long day?", category: "personality", options: ["Being around people", "Alone time", "A mix — depends on my mood", "Physical activity / exercise"] },
  { text: "How ambitious are you?", category: "personality", options: ["Very — I'm driven and goal-oriented", "Moderately — I have goals but value balance", "Not very — I prefer a relaxed life"] },
  { text: "How do you handle stress?", category: "personality", options: ["I tackle the problem head-on", "I exercise or stay active", "I withdraw and think it through", "I distract myself until it passes"] },
  { text: "Do you cry easily?", category: "personality", options: ["Yes, and I'm not ashamed of it", "Occasionally, when something really hits me", "Rarely", "Almost never"] },
  { text: "Are you more of a leader or a follower?", category: "personality", options: ["Natural leader", "I can lead when needed", "I prefer following", "It completely depends on the context"] },
  { text: "How do you make big life decisions?", category: "personality", options: ["I follow my gut", "I carefully analyze every option", "I talk it through with people I trust", "I procrastinate until the decision makes itself"] },
  { text: "How comfortable are you with vulnerability?", category: "personality", options: ["Very — I believe it strengthens connections", "I'm working on it", "I find it very difficult", "I only show vulnerability with very close people"] },
  { text: "Do you hold grudges?", category: "personality", options: ["No — I forgive and move on quickly", "I try to forgive but sometimes struggle", "Yes — I have a hard time letting go"] },
  { text: "Are you more of an optimist or a pessimist?", category: "personality", options: ["Optimist — things usually work out", "Realist — I see things as they are", "Pessimist — I expect the worst", "It shifts depending on the situation"] },
  { text: "How do you feel about small talk?", category: "personality", options: ["I enjoy it — it's how connections start", "I tolerate it but prefer deep conversations", "I hate it — let's skip to what matters"] },
  { text: "How competitive are you?", category: "personality", options: ["Very — I play to win", "Moderately — I like friendly competition", "Not very — I'm just here for fun"] },
  { text: "Do you tend to overthink things?", category: "personality", options: ["Constantly — my brain never stops", "Sometimes — on big decisions", "Rarely — I go with my instincts"] },

  // ============================================================
  // SEX & INTIMACY
  // ============================================================
  { text: "How important is sexual compatibility in a relationship?", category: "intimacy", options: ["Very — it's foundational", "Important but not the whole picture", "Less important than emotional connection"] },
  { text: "How do you feel about sexting or sending flirty messages?", category: "intimacy", options: ["I enjoy it — it keeps the spark alive", "I'm open to it with the right person", "Not really my thing"] },
  { text: "How important is physical affection (hugging, cuddling) to you?", category: "intimacy", options: ["Very — I need it daily", "Somewhat — I like it but don't need it constantly", "Not very — I'm more of a personal space person"] },
  { text: "How do you feel about pornography in a relationship?", category: "intimacy", options: ["I'm fine with it — it's normal", "I'm okay with it as long as it doesn't affect us", "I'm uncomfortable with it", "It would be a problem for me"] },
  { text: "What's your comfort level with discussing sexual preferences openly?", category: "intimacy", options: ["Very comfortable — communication is key", "Somewhat comfortable — it takes me a bit to open up", "I find it awkward but I try", "I'd rather not discuss it"] },
  { text: "How soon into dating is it appropriate to have sex?", category: "intimacy", options: ["Whenever it feels right — no timeline", "After a few dates", "After we're exclusive", "I prefer to wait a while"] },

  // ============================================================
  // COMMUNICATION & CONFLICT
  // ============================================================
  { text: "When you and your partner disagree, what's your instinct?", category: "communication", options: ["Talk it through calmly right away", "Take some space and revisit later", "Avoid the conflict and hope it resolves", "Stand my ground until we reach a compromise"] },
  { text: "Do you prefer to resolve arguments the same day, or is sleeping on it okay?", category: "communication", options: ["Same day — I can't rest until it's settled", "Sleeping on it is fine — cooler heads prevail", "It depends on the severity"] },
  { text: "How do you feel about the silent treatment?", category: "communication", options: ["It's toxic — I'd never do it", "I've done it but I know it's unhealthy", "Sometimes silence is necessary", "I need space and quiet to process"] },
  { text: "Is it important that your partner is a good texter?", category: "communication", options: ["Very — I need responsive communication", "Somewhat — just don't leave me on read for days", "Not important — I prefer calls or in-person"] },
  { text: "How do you prefer to communicate difficult feelings?", category: "communication", options: ["In person, face to face", "Over text — it gives me time to think", "In a letter or long message", "Through actions rather than words"] },
  { text: "Would you ever go to couples therapy?", category: "communication", options: ["Yes, proactively — relationships take work", "Only if things got really bad", "No, I don't believe in therapy"] },

  // ============================================================
  // DEALBREAKERS & BOUNDARIES
  // ============================================================
  { text: "Which of these is the biggest dealbreaker for you?", category: "dealbreakers", options: ["Dishonesty", "Lack of ambition", "Different political views", "Messy lifestyle"] },
  { text: "Could you date someone who still lives with their ex?", category: "dealbreakers", options: ["Yes, if it's truly platonic", "Probably not — too much drama", "Absolutely not", "It depends on the situation"] },
  { text: "How do you feel about dating someone who is still close with their ex?", category: "dealbreakers", options: ["Fine — I trust my partner", "I'd be a little uncomfortable", "Not okay with it", "It depends on the context"] },
  { text: "Could you date someone with a lot of debt?", category: "dealbreakers", options: ["Yes — money isn't everything", "It depends on how they manage it", "No — financial stability matters to me"] },
  { text: "Is it a dealbreaker if your partner doesn't want kids and you do (or vice versa)?", category: "dealbreakers", options: ["Yes — this is non-negotiable", "I might compromise or revisit later", "No — we could work around it"] },
  { text: "How do you feel about your partner having opposite-sex friendships?", category: "dealbreakers", options: ["Totally fine", "Fine with boundaries", "I'd be uncomfortable", "It's a dealbreaker"] },
  { text: "Could you date someone who doesn't get along with your family?", category: "dealbreakers", options: ["Yes — my partner and family are separate", "It would be tough but I'd try", "No — family harmony is important to me"] },
  { text: "How many serious relationships is too many for a potential partner?", category: "dealbreakers", options: ["No such thing — past is past", "More than 5 makes me wonder", "More than 10 is a lot", "I don't care about the number"] },

  // ============================================================
  // INTERESTS & CULTURE
  // ============================================================
  { text: "How important is it that your partner shares your taste in music?", category: "interests", options: ["Very — music is a big part of who I am", "Somewhat — overlap is nice but not required", "Not important — different tastes are fine"] },
  { text: "Do you enjoy going to concerts and live music events?", category: "interests", options: ["Love them — I go whenever I can", "Occasionally — for artists I really like", "Not my scene"] },
  { text: "How do you feel about reality TV?", category: "interests", options: ["Guilty pleasure — I love it", "I watch occasionally", "I can't stand it", "No strong opinion"] },
  { text: "Are you more of a reader or a watcher?", category: "interests", options: ["Big reader — books over screens", "Mix of both", "Definitely a watcher — movies and shows", "I prefer podcasts or audiobooks"] },
  { text: "How important is it that your partner shares your hobbies?", category: "interests", options: ["Very — I want us to do things together", "Having some overlap is nice", "Independent hobbies are healthy", "Not important at all"] },
  { text: "Do you play video games?", category: "interests", options: ["A lot — it's a main hobby", "Casually — a few hours a week", "Rarely", "Never"] },
  { text: "How do you feel about your partner playing video games regularly?", category: "interests", options: ["Totally fine — I play too", "Fine as long as it doesn't take over", "I'd rather they didn't", "Dealbreaker"] },
  { text: "How important is it that you and your partner have the same sense of humor?", category: "interests", options: ["Very — if we don't laugh together, it won't work", "Somewhat — we need to find the same things funny sometimes", "Not important — different humor keeps things interesting"] },
  { text: "How do you feel about social media — for yourself and in a relationship?", category: "interests", options: ["I'm very active and enjoy sharing my life", "I use it casually but don't post much", "I barely use it", "I actively avoid social media"] },
  { text: "How do you feel about your partner posting about your relationship on social media?", category: "interests", options: ["I love it — share the love!", "Fine with occasional posts", "I'd rather keep our relationship private", "It would bother me"] },
];

export async function seedMatchingQuestions() {
  console.log('[Seed] Starting matching questions seeding...');
  for (const q of matchingQuestions) {
    try {
      // Check if question exists first to be idempotent
      const existing = await prisma.matching_questions.findFirst({
        where: { text: q.text }
      });
      if (existing) {
        console.log(`[Seed] Question already exists: "${q.text.substring(0, 30)}..."`);
        continue;
      }
      const question = await prisma.matching_questions.create({
        data: {
          text: q.text,
          category: q.category,
          is_active: true,
        }
      });
      for (const optText of q.options) {
        await prisma.matching_options.create({
          data: { question_id: question.id, text: optText }
        });
      }
    } catch (err: any) {
      console.error(`[Seed] Failed to seed question "${q.text}":`, err.message);
    }
  }
  console.log('[Seed] Matching questions seeded.');
}

if (process.argv[1]?.includes('matching-questions')) {
  seedMatchingQuestions()
    .then(() => {
      console.log('[Seed] Seeding completed successfully.');
      process.exit(0);
    })
    .catch((err) => {
      console.error('[Seed] Seeding failed:', err);
      process.exit(1);
    });
}
