import { PrismaClient } from '@prisma/client';
import axios from 'axios';

const prisma = new PrismaClient();

export class ContactSyncService {
    /**
     * Entry point for a background worker/cron to sync contacts for all users
     * who have active integrations.
     */
    static async syncAllActiveIntegrations() {
        const integrations = await prisma.userIntegration.findMany({
            where: {
                tokenExpiresAt: {
                    gt: new Date() // Only try if we have a valid token (or we'd need to refresh it first)
                }
            }
        });

        for (const integration of integrations) {
            try {
                await this.syncContactsForIntegration(integration.id);
            } catch (err: any) {
                console.error(`Failed to sync integration ${integration.id}:`, err.message);
                // Optionally implement refresh token logic here if the failure was a 401
            }
        }
    }

    /**
     * Synchronize contacts for a specific integration.
     */
    static async syncContactsForIntegration(integrationId: string) {
        const integration = await prisma.userIntegration.findUnique({
            where: { id: integrationId }
        });

        if (!integration) throw new Error('Integration not found');

        let rawContacts: any[] = [];

        if (integration.provider === 'google') {
            rawContacts = await this.fetchGoogleContacts(integration.accessToken);
        } else if (integration.provider === 'microsoft') {
            rawContacts = await this.fetchMicrosoftContacts(integration.accessToken);
        } else if (integration.provider === 'facebook') {
            rawContacts = await this.fetchFacebookFriends(integration.accessToken);
        }

        await this.storeSyncedContacts(integrationId, integration.provider, rawContacts);
    }

    private static async fetchGoogleContacts(accessToken: string): Promise<any[]> {
        const contacts: any[] = [];
        let nextPageToken: string | undefined = undefined;

        do {
            const url: string = nextPageToken
                ? `https://people.googleapis.com/v1/people/me/connections?personFields=names,emailAddresses,phoneNumbers&pageToken=${nextPageToken}`
                : 'https://people.googleapis.com/v1/people/me/connections?personFields=names,emailAddresses,phoneNumbers';

            const response = await axios.get(url, {
                headers: { Authorization: `Bearer ${accessToken}` }
            });

            if (response.data.connections) {
                contacts.push(...response.data.connections);
            }
            nextPageToken = response.data.nextPageToken;

        } while (nextPageToken);

        return contacts;
    }

    private static async fetchMicrosoftContacts(accessToken: string): Promise<any[]> {
        const contacts: any[] = [];
        let nextLink = 'https://graph.microsoft.com/v1.0/me/contacts?$select=displayName,givenName,surname,emailAddresses,businessPhones,mobilePhone';

        do {
            const response = await axios.get(nextLink, {
                headers: { Authorization: `Bearer ${accessToken}` }
            });

            if (response.data.value) {
                contacts.push(...response.data.value);
            }
            nextLink = response.data['@odata.nextLink'];

        } while (nextLink);

        return contacts;
    }

    private static async fetchFacebookFriends(accessToken: string): Promise<any[]> {
        // Note: For Facebook, we can only fetch friends who ALSO use the app.
        const response = await axios.get('https://graph.facebook.com/v18.0/me/friends', {
            params: { access_token: accessToken }
        });

        return response.data.data || [];
    }

    private static async storeSyncedContacts(integrationId: string, provider: string, rawContacts: any[]) {
        for (const raw of rawContacts) {
            let firstName = null;
            let lastName = null;
            const emails: string[] = [];
            const phones: string[] = [];

            if (provider === 'google') {
                firstName = raw.names?.[0]?.givenName || null;
                lastName = raw.names?.[0]?.familyName || null;
                raw.emailAddresses?.forEach((e: any) => { if (e.value) emails.push(e.value) });
                raw.phoneNumbers?.forEach((p: any) => { if (p.value) phones.push(p.value) });
            } else if (provider === 'microsoft') {
                firstName = raw.givenName || raw.displayName || null;
                lastName = raw.surname || null;
                raw.emailAddresses?.forEach((e: any) => { if (e.address) emails.push(e.address) });
                if (raw.mobilePhone) phones.push(raw.mobilePhone);
                raw.businessPhones?.forEach((p: string) => phones.push(p));
            } else if (provider === 'facebook') {
                // Facebook friends data usually just has 'name' and 'id'
                const nameParts = (raw.name || '').split(' ');
                firstName = nameParts[0] || null;
                lastName = nameParts.slice(1).join(' ') || null;
                // Emails and phones are generally not available via friends API for privacy reasons
            }

            // Upsert based on integration ID and some unique identifier in the raw data?
            // Since we don't have a reliable primary key from the provider in this normalized schema
            // we will create them and potentially deduplicate later, or just clear and recreate.
            // For MVP, we'll just insert. A robust implementation would delete existing ones first or match by provider ID.

            await prisma.syncedContact.create({
                data: {
                    userIntegrationId: integrationId,
                    firstName,
                    lastName,
                    emails,
                    phones,
                    rawProviderData: raw
                }
            });
        }
    }
}
