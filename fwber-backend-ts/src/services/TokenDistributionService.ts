import prisma from '../lib/prisma.js';
import { Decimal } from '@prisma/client/runtime/library';

export class TokenDistributionService {
  private static SIGNUP_BONUS_BASE = 100;
  private static REFERRAL_BONUS = 50;
  private static EARLY_ADOPTER_MULTIPLIER_DECAY = 0.0001;

  async generateReferralCode(): Promise<string> {
    let code: string;
    let exists = true;

    while (exists) {
      code = Math.random().toString(36).substring(2, 10).toUpperCase();
      const user = await prisma.users.findUnique({ where: { referral_code: code } });
      if (!user) return code;
    }
    return ''; // Should not reach
  }

  async ensureReferralCode(userId: bigint): Promise<string> {
    const user = await prisma.users.findUnique({ where: { id: userId } });
    if (!user) throw new Error('User not found');
    if (user.referral_code) return user.referral_code;

    const code = await this.generateReferralCode();
    await prisma.users.update({
      where: { id: userId },
      data: { referral_code: code }
    });

    return code;
  }

  async processSignupBonus(userId: bigint, referrerCode?: string): Promise<void> {
    await prisma.$transaction(async (tx) => {
      const user = await tx.users.findUnique({ where: { id: userId } });
      if (!user) return;

      await this.ensureReferralCode(user.id);

      // 1. Calculate Early Adopter Bonus
      const userCount = await tx.users.count();
      const multiplier = 1 / (1 + (userCount * TokenDistributionService.EARLY_ADOPTER_MULTIPLIER_DECAY));
      const amount = Number((TokenDistributionService.SIGNUP_BONUS_BASE * multiplier).toFixed(2));

      await this.awardTokens(userId, amount, 'signup_bonus', 'Early Adopter Signup Bonus', {}, tx);

      // 2. Process Referral
      if (referrerCode) {
        const referrer = await tx.users.findUnique({ where: { referral_code: referrerCode } });
        if (referrer) {
          await tx.users.update({
            where: { id: userId },
            data: { referrer_id: referrer.id }
          });

          // Check for Golden Ticket
          if (referrer.golden_tickets_remaining > 0) {
            await tx.users.update({
              where: { id: referrer.id },
              data: { golden_tickets_remaining: { decrement: 1 } }
            });

            // Grant 3 Days of Premium (Gold Tier)
            const expiresAt = new Date();
            expiresAt.setDate(expiresAt.getDate() + 3);

            await tx.users.update({
              where: { id: userId },
              data: {
                tier: 'gold',
                tier_expires_at: expiresAt
              }
            });
          }

          // Award Referrer
          await this.awardTokens(referrer.id, TokenDistributionService.REFERRAL_BONUS, 'referral_bonus', `Referral Bonus for user ${userId}`, {}, tx);

          // Award Referee
          await this.awardTokens(userId, TokenDistributionService.REFERRAL_BONUS, 'referral_accepted_bonus', 'Bonus for using referral code', {}, tx);
        }
      }
    });
  }

  async awardTokens(userId: bigint, amount: number, type: string, description: string, metadata: any = {}, tx?: any): Promise<void> {
    const client = tx || prisma;

    await client.users.update({
      where: { id: userId },
      data: { token_balance: { increment: amount } }
    });

    await client.token_transactions.create({
      data: {
        user_id: userId,
        amount: new Decimal(amount),
        type,
        description,
        metadata
      }
    });

    console.log(`[TokenService] Awarded ${amount} tokens to user ${userId}: ${description}`);
  }

  async spendTokens(userId: bigint, amount: number, description: string): Promise<void> {
    await prisma.$transaction(async (tx) => {
      const user = await tx.users.findUnique({ where: { id: userId } });
      if (!user || Number(user.token_balance) < amount) {
        throw new Error('Insufficient token balance.');
      }

      await tx.users.update({
        where: { id: userId },
        data: { token_balance: { decrement: amount } }
      });

      await tx.token_transactions.create({
        data: {
          user_id: userId,
          amount: new Decimal(-amount),
          type: 'spend',
          description
        }
      });
    });
  }
}
