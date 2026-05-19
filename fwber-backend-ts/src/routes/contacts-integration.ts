import { Router, Request, Response } from 'express';
import { PrismaClient } from '@prisma/client';
import axios from 'axios';

const router = Router();
const prisma = new PrismaClient();

// Simulated middleware for auth
const authenticateToken = (req: Request, res: Response, next: any) => {
    // Basic mock auth context
    if (!req.user) {
        req.user = { id: 'mock-user-uuid' };
    }
    next();
};

/**
 * Endpoint 1: Redirect to OAuth Provider
 * Example: GET /api/integrations/contacts/auth/:provider
 */
router.get('/auth/:provider', authenticateToken, (req: Request, res: Response) => {
    const { provider } = req.params;
    let url = '';

    const redirectUri = `${process.env.APP_URL}/api/integrations/contacts/callback/${provider}`;

    if (provider === 'google') {
        const scopes = 'https://www.googleapis.com/auth/contacts.readonly https://www.googleapis.com/auth/userinfo.profile';
        url = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${process.env.GOOGLE_CLIENT_ID}&redirect_uri=${encodeURIComponent(redirectUri)}&response_type=code&scope=${encodeURIComponent(scopes)}&access_type=offline&prompt=consent`;
    } else if (provider === 'microsoft') {
        const scopes = 'Contacts.Read User.Read offline_access';
        url = `https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id=${process.env.MICROSOFT_CLIENT_ID}&redirect_uri=${encodeURIComponent(redirectUri)}&response_type=code&scope=${encodeURIComponent(scopes)}`;
    } else if (provider === 'facebook') {
        const scopes = 'public_profile,email,user_friends';
        url = `https://www.facebook.com/v18.0/dialog/oauth?client_id=${process.env.FACEBOOK_APP_ID}&redirect_uri=${encodeURIComponent(redirectUri)}&scope=${encodeURIComponent(scopes)}`;
    } else {
        return res.status(400).json({ error: 'Unsupported provider' });
    }

    res.redirect(url);
});

/**
 * Endpoint 2: OAuth Callback
 * Example: GET /api/integrations/contacts/callback/:provider
 */
router.get('/callback/:provider', async (req: Request, res: Response) => {
    const { provider } = req.params;
    const { code } = req.query;

    if (!code || typeof code !== 'string') {
        return res.status(400).json({ error: 'Authorization code missing' });
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

            // Get user info to find providerUserId
            const userRes = await axios.get('https://www.googleapis.com/oauth2/v2/userinfo', {
                headers: { Authorization: `Bearer ${tokenData.access_token}` }
            });
            providerUserId = userRes.data.id;

        } else if (provider === 'microsoft') {
            const params = new URLSearchParams({
                client_id: process.env.MICROSOFT_CLIENT_ID!,
                scope: 'Contacts.Read User.Read offline_access',
                code,
                redirect_uri: redirectUri,
                grant_type: 'authorization_code',
                client_secret: process.env.MICROSOFT_CLIENT_SECRET!
            });
            const tokenRes = await axios.post('https://login.microsoftonline.com/common/oauth2/v2.0/token', params.toString(), {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            tokenData = tokenRes.data;

            const userRes = await axios.get('https://graph.microsoft.com/v1.0/me', {
                headers: { Authorization: `Bearer ${tokenData.access_token}` }
            });
            providerUserId = userRes.data.id;

        } else if (provider === 'facebook') {
            const tokenRes = await axios.get(`https://graph.facebook.com/v18.0/oauth/access_token`, {
                params: {
                    client_id: process.env.FACEBOOK_APP_ID,
                    redirect_uri: redirectUri,
                    client_secret: process.env.FACEBOOK_APP_SECRET,
                    code
                }
            });
            tokenData = tokenRes.data;
            // Facebook doesn't use refresh tokens in the standard OAuth2 sense, they use long-lived tokens

            const userRes = await axios.get('https://graph.facebook.com/me', {
                params: { access_token: tokenData.access_token }
            });
            providerUserId = userRes.data.id;
        }

        // Store integration in database
        const expiresAt = new Date();
        expiresAt.setSeconds(expiresAt.getSeconds() + (tokenData.expires_in || 3600));

        // Note: Using a hardcoded UUID for the user since req.user is lost in the callback redirect
        // In a real implementation, you'd use a secure state parameter or session cookies


        const integration = await prisma.userIntegration.upsert({
            where: {
                userId_provider: {
                    userId: BigInt((req.user as any)?.id || 1), // In a real OAuth flow with state, we would parse the user ID from the state parameter. For now we use the authenticated user if session exists or fallback to 1
                    provider: provider
                }
            },
            update: {
                accessToken: tokenData.access_token,
                refreshToken: tokenData.refresh_token || null,
                tokenExpiresAt: expiresAt,
                providerUserId: providerUserId
            },
            create: {
                userId: BigInt((req.user as any)?.id || 1), // In a real OAuth flow with state, we would parse the user ID from the state parameter. For now we use the authenticated user if session exists or fallback to 1
                provider: provider,
                providerUserId: providerUserId,
                accessToken: tokenData.access_token,
                refreshToken: tokenData.refresh_token || null,
                tokenExpiresAt: expiresAt
            }
        });

        // Trigger sync asynchronously
        // syncContacts(integration.id);

        res.send(`Successfully authenticated with ${provider}. You can close this window.`);
    } catch (err: any) {
        console.error('OAuth Exchange Error:', err.response?.data || err.message);
        res.status(500).json({ error: 'Failed to exchange token' });
    }
});

export default router;
