import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { PrismaClient, UserRole, Tier } from '@prisma/client';
import { z } from 'zod';
const prisma = new PrismaClient();
const registerSchema = z.object({
    name: z.string().min(2),
    email: z.string().email(),
    password: z.string().min(8),
    referral_code: z.string().optional(),
});
const loginSchema = z.object({
    email: z.string().email(),
    password: z.string(),
});
export class AuthController {
    generateToken(user) {
        return jwt.sign({ id: user.id, email: user.email, role: user.role }, process.env.JWT_SECRET || 'secret', { expiresIn: '30d' });
    }
    async hydrateUser(user) {
        const referralsCount = await prisma.user.count({
            where: { referrer_id: user.id }
        });
        // We'll add more counts later as needed
        return {
            ...user,
            referrals_count: referralsCount,
            vouches_count: 0, // Placeholder
        };
    }
    register = async (req, res) => {
        try {
            const validated = registerSchema.parse(req.body);
            const existingUser = await prisma.user.findUnique({
                where: { email: validated.email }
            });
            if (existingUser) {
                return res.status(400).json({ message: 'User already exists' });
            }
            const hashedPassword = await bcrypt.hash(validated.password, 10);
            const user = await prisma.user.create({
                data: {
                    name: validated.name,
                    email: validated.email,
                    password: hashedPassword,
                    role: UserRole.USER,
                    tier: Tier.FREE,
                    referral_code: Math.random().toString(36).substring(2, 8).toUpperCase(),
                },
                include: {
                    profile: true
                }
            });
            // Initialize profile
            await prisma.userProfile.create({
                data: {
                    user_id: user.id,
                    display_name: user.name,
                }
            });
            const token = this.generateToken(user);
            res.status(201).json({
                access_token: token,
                token_type: 'Bearer',
                user: await this.hydrateUser(user),
            });
        }
        catch (error) {
            if (error instanceof z.ZodError) {
                return res.status(400).json({ errors: error.issues });
            }
            res.status(500).json({ message: error.message });
        }
    };
    login = async (req, res) => {
        try {
            const { email, password } = loginSchema.parse(req.body);
            const user = await prisma.user.findUnique({
                where: { email },
                include: { profile: true }
            });
            if (!user) {
                return res.status(401).json({ message: 'Invalid credentials' });
            }
            const isPasswordValid = await bcrypt.compare(password, user.password);
            // Porting Decoy Logic
            const isDecoyAuth = !isPasswordValid && user.decoy_password && await bcrypt.compare(password, user.decoy_password);
            if (!isPasswordValid && !isDecoyAuth) {
                return res.status(401).json({ message: 'Invalid credentials' });
            }
            let finalUser = user;
            if (isDecoyAuth && user.decoy_user_id) {
                const decoyUser = await prisma.user.findUnique({
                    where: { id: user.decoy_user_id },
                    include: { profile: true }
                });
                if (decoyUser) {
                    finalUser = decoyUser;
                }
            }
            const token = this.generateToken(finalUser);
            res.json({
                access_token: token,
                token_type: 'Bearer',
                user: await this.hydrateUser(finalUser),
            });
        }
        catch (error) {
            if (error instanceof z.ZodError) {
                return res.status(400).json({ errors: error.issues });
            }
            res.status(500).json({ message: error.message });
        }
    };
    me = async (req, res) => {
        res.json(await this.hydrateUser(req.user));
    };
}
//# sourceMappingURL=AuthController.js.map