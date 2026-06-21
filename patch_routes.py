import re

with open('fwber-backend-ts/src/routes/quests.ts', 'r') as f:
    content = f.read()

replacement = """import crypto from 'crypto';

// POST /api/quests/:id/complete — Mark quest as completed
router.post('/:id/complete', authenticate, async (req: any, res) => {
    try {
      const userId = BigInt(req.user.id);
      const questId = BigInt(req.params.id);
      const { proof } = req.body;

      const uq = await prisma.user_quests.findUnique({
        where: { user_id_quest_id: { user_id: userId, quest_id: questId } },
        include: { quests: true }
      });

      if (!uq || uq.status !== 'active') return res.status(400).json({ error: 'Quest not active' });

      // Verification Step
      if (uq.quests.verification_secret) {
        if (!proof) {
          return res.status(400).json({ error: 'ZK/NFC Proof required for this quest' });
        }

        // Compute expected proof hash: sha256(questId + userId + secret)
        const expectedProof = crypto
          .createHash('sha256')
          .update(`${questId}${userId}${uq.quests.verification_secret}`)
          .digest('hex');

        if (proof !== expectedProof) {
          return res.status(403).json({ error: 'Invalid ZK/NFC Proof' });
        }
      }

      // Grant rewards
      await tokenService.awardTokens(
        userId,
        Number(uq.quests.token_reward),
        'quest_reward',
        `Completed Quest: ${uq.quests.title}`
      );

      const updated = await prisma.user_quests.update({
        where: { id: uq.id },
        data: {
          status: 'claimed',
          completed_at: new Date()
        }
      });

      res.json({ success: true, reward: Number(uq.quests.token_reward) });
    } catch (error: any) {
      res.status(500).json({ error: 'Failed to complete quest' });
    }
  });"""

content = re.sub(r'// POST /api/quests/:id/complete.*?\}\);', replacement, content, flags=re.DOTALL)

with open('fwber-backend-ts/src/routes/quests.ts', 'w') as f:
    f.write(content)
