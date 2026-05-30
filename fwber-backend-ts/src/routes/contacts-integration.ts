import { Router, type Request, type Response } from 'express';
import prisma from '../lib/prisma.js';
import axios from 'axios';
import { authenticate, type AuthRequest } from '../middleware/auth.js';
import jwt from 'jsonwebtoken';

const router = Router();

/**
 * Endpoint 1: Redirect to OAuth Provider
 * Example: GET /api/integrations/contacts/auth/:provider
 */
router.get('/auth/:provider', authenticate, (req: AuthRequest, res: Response) => {
    const { provider } = req.params;
    let url = '';

    // Encode the userId into the state parameter to recover it in the callback
    const state = jwt.sign({ userId: req.user.id }, process.env.JWT_SECRET || 'secret', { expiresIn: '15m' });
    const redirectUri = `${process.env.APP_URL}/api/integrations/contacts/callback/${provider}`;

    // SECURE: Encode the userId into a signed state parameter to recover it in the callback
    const state = jwt.sign({ userId: req.user!.id }, process.env.JWT_SECRET || 'secret', { expiresIn: '15m' });

    if (provider === 'google') {
        const scopes = 'https://www.googleapis.com/auth/contacts.readonly https://www.googleapis.com/auth/userinfo.profile';
        url = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${process.env.GOOGLE_CLIENT_ID}&redirect_uri=${encodeURIComponent(redirectUri)}&response_type=code&scope=${encodeURIComponent(scopes)}&access_type=offline&prompt=consent&state=${state}`;
    } else if (provider === 'microsoft') {
        const scopes = 'Contacts.Read User.Read offline_access';
        url = `https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id=${process.env.MICROSOFT_CLIENT_ID}&redirect_uri=${encodeURIComponent(redirectUri)}&response_type=code&scope=${encodeURIComponent(scopes)}&state=${state}`;
    } else if (provider === 'facebook') {
        const scopes = 'public_profile,email,user_friends';
        url = `https://www.facebook.com/v18.0/dialog/oauth?client_id=${process.env.FACEBOOK_APP_ID}&redirect_uri=${encodeURIComponent(redirectUri)}&scope=${encodeURIComponent(scopes)}&state=${state}`;
    } else {
        return res.status(400).json({ error: 'Unsupported provider' });
    }

    res.redirect(url);

    res.json({ url });
});

/**
 * Endpoint 2: OAuth Callback
 * Example: GET /api/integrations/contacts/callback/:provider

 */
router.get('/callback/:provider', async (req: Request, res: Response) => {
    const { provider } = req.params;
    const { code, state } = req.query;

    if (!code || typeof code !== 'string') {
        return res.status(400).json({ error: 'Authorization code missing' });
    }

    // Recover userId from state
    let userId: bigint;
    try {
        const decoded = jwt.verify(state as string, process.env.JWT_SECRET || 'secret') as { userId: number };
        userId = BigInt(decoded.userId);
    } catch (err) {

    // SECURE: Recover userId from signed state
        if (!state || typeof state !== 'string') throw new Error('State missing');
        const decoded = jwt.verify(state, process.env.JWT_SECRET || 'secret') as { userId: number };
        console.error('[OAuth] State verification failed:', err);
        return res.status(400).json({ error: 'Invalid or expired state' });
    }

    const redirectUri = `${process.env.APP_URL}/api/integrations/contacts/callback/${provider}`;
    let tokenData: any = {};
    let providerUserId = '';

    try {
        if (provider === 'google') {
            const tokenRes = await axios.post('https://oauth2.googleapis.com/token', {
                code,
                client_id: process.env.GOOGLE_CLIENT_ID,
                client_secret: process.env.GOOGLE_CLIENT_SECRET,
                redirect_uri: redirectUri,
                grant_type: 'authorization_code'
            });
            tokenData = tokenRes.data;

            const userRes = await axios.get('https://www.googleapis.com/oauth2/v2/userinfo', {
                headers: { Authorization: `Bearer ${tokenData.access_token}` }
            providerUserId = userRes.data.id;

        } else if (provider === 'microsoft') {
            const params = new URLSearchParams({
                client_id: process.env.MICROSOFT_CLIENT_ID!,
                scope: 'Contacts.Read User.Read offline_access',
                grant_type: 'authorization_code',
                client_secret: process.env.MICROSOFT_CLIENT_SECRET!
            const tokenRes = await axios.post('https://login.microsoftonline.com/common/oauth2/v2.0/token', params.toString(), {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }

            const userRes = await axios.get('https://graph.microsoft.com/v1.0/me', {

        } else if (provider === 'facebook') {
            const tokenRes = await axios.get(`https://graph.facebook.com/v18.0/oauth/access_token`, {
                params: {
                    client_id: process.env.FACEBOOK_APP_ID,
                    client_secret: process.env.FACEBOOK_APP_SECRET,
                    code

                redirect_uri: redirectUri

            const tokenRes = await axios.get('https://graph.facebook.com/v18.0/oauth/access_token', {
                    client_secret: process.env.FACEBOOK_CLIENT_SECRET,
                }
            });
            tokenData = tokenRes.data;

            const userRes = await axios.get('https://graph.facebook.com/me', {
                params: { access_token: tokenData.access_token }
            });
            providerUserId = userRes.data.id;
        }

        const expiresAt = new Date();
        expiresAt.setSeconds(expiresAt.getSeconds() + (tokenData.expires_in || 3600));

        await (prisma as any).userIntegration.upsert({
            where: {
                userId_provider: {
                    userId: userId,
                    provider: provider

        await prisma.userIntegration.upsert({
                    provider: provider as string as string
                }
            },
            update: {
                accessToken: tokenData.access_token,
                refreshToken: tokenData.refresh_token || null,
                tokenExpiresAt: expiresAt,
                providerUserId: providerUserId
            },
            create: {
                userId: userId,
                provider: provider,
                providerUserId: providerUserId,

                providerUserId: providerUserId as string as string
                provider: provider as string as string,
                providerUserId: providerUserId as string as string,
                accessToken: tokenData.access_token,
                refreshToken: tokenData.refresh_token || null,
                tokenExpiresAt: expiresAt
            }
        });

        res.send(`Successfully authenticated with ${provider}. You can close this window.`);
    } catch (err: any) {
        console.error('OAuth Exchange Error:', err.response?.data || err.message);
        res.status(500).json({ error: 'Failed to exchange token' });

        res.status(500).send('Authentication failed');
    }
});

export default router;
