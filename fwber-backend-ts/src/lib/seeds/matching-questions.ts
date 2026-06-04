import prisma from '../prisma.js';

const matchingQuestions = [
  // LIFESTYLE & HABITS
  {
    text: "In the shadow-ops of daily life, how do you manage your tactical base (living space)?",
    category: "lifestyle",
    options: ["Absolute Zero (Spotless)", "Controlled Chaos (Messy but organized)", "Deep Protocol (Somewhat tidy)", "System Failure (Disaster zone)"],
  },
  {
    text: "Would you initiate a neural-link with someone whose physical base is in a state of System Failure (very messy)?",
    category: "lifestyle",
    options: ["Protocol Accepted (Yes)", "Handshake Denied (No)"],
  },
  {
    text: "System maintenance: How frequently do you run your dental hygiene subroutines?",
    category: "lifestyle",
    options: ["Full Sync (Twice+ daily)", "Standard Patch (Once daily)", "Intermittent (Occasionally)", "Offline (Never)"],
  },
  {
    text: "In the high-intensity data exchange of romance, is there such a thing as 'Bandwidth Overload' (too much sex)?",
    category: "romance",
    options: ["Limit Reached (Yes)", "Infinite Stream (No)"],
  },
  {
    text: "Could you maintain a high-fidelity connection with a silent-mode operator (someone really quiet)?",
    category: "romance",
    options: ["Audio Optional (Yes)", "Frequency Required (No)"],
  },
  // ETHICS & VIRTUAL REALITY
  {
    text: "Do you believe in the Hard-Coded Fate of the simulation, or is the source code open?",
    category: "ethics",
    options: ["Pre-Compiled (Yes, fate)", "Just-In-Time (No, choice)"],
  },
  {
    text: "Priority check: How critical is it that your partner possesses high-risk exploration protocols (adventurous)?",
    category: "lifestyle",
    options: ["Mission Critical (Yes)", "Low Priority (No)"],
  },
  {
    text: "Operating mode: Do you prefer precise roadmap execution or real-time event-driven processing?",
    category: "lifestyle",
    options: ["Batch Process (Plan everything)", "Async Flow (Go with the flow)", "Hybrid Mode (A bit of both)"],
  },
  {
    text: "Would you link with a node that strictly follows legacy religious firmware?",
    category: "ethics",
    options: ["Compatibility Possible (Yes)", "Protocol Conflict (No)", "Awaiting Data (Maybe)"],
  },
  {
    text: "Distributed networks: What is your stance on multi-node relationship architectures (open relationships)?",
    category: "romance",
    options: ["Multiplexing Enabled (Open)", "Single-Threaded (Monogamous)", "Active Cluster (Currently in one)"],
  },
  // ADDING MORE FOR CYBER-NOIR DEPTH
  {
    text: "If the global grid went dark for 72 hours, what's your primary contingency?",
    category: "lifestyle",
    options: ["Analog Survival", "Data Salvage", "Neural Rest", "Panic Mode"],
  },
  {
    text: "How much of your identity is stored in the cloud vs. the physical meat-space?",
    category: "ethics",
    options: ["Fully Uploaded", "Hybrid Entity", "Meat-Space Purist", "Encrypted/Hidden"],
  },
  {
    text: "When the synth-rain falls, where is your optimal recharging station?",
    category: "lifestyle",
    options: ["High-Density Hub (Club)", "The Quiet Terminal (Home)", "Abandoned Sector (Solo exploration)", "Virtual Sim (Gaming)"],
  },
  {
    text: "Is total data transparency (honesty) required for a stable relationship uplink?",
    category: "ethics",
    options: ["Unencrypted Only (Yes)", "Private Keys Matter (No)"],
  },
  {
    text: "What level of bio-hacking (body mods/piercings/tattoos) do you find compatible?",
    category: "lifestyle",
    options: ["Full Cyberpunk", "Minor Augments", "Stock Hardware Only", "No Preference"],
  }
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
          data: {
            question_id: question.id,
            text: optText
          }
        });
      }
    } catch (err: any) {
      console.error(`[Seed] Failed to seed question "${q.text}":`, err.message);
    }
  }

  console.log('[Seed] Matching questions seeded.');
}

if (process.argv[1]?.includes('matching-questions.ts')) {
  seedMatchingQuestions()
    .then(() => process.exit(0))
    .catch((err) => {
      console.error(err);
      process.exit(1);
    });
}
