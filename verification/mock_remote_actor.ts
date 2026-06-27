import http from 'http';
import { URL } from 'url';

const PORT = 5000;

const server = http.createServer((req, res) => {
    const url = new URL(req.url || '', `http://localhost:${PORT}`);
    console.log(`[Mock Remote] ${req.method} ${url.pathname}`);

    if (url.pathname === '/.well-known/webfinger') {
        const resource = url.searchParams.get('resource');
        if (resource === 'acct:bob@localhost:5000') {
            res.writeHead(200, { 'Content-Type': 'application/jrd+json' });
            res.end(JSON.stringify({
                subject: 'acct:bob@localhost:5000',
                links: [{
                    rel: 'self',
                    type: 'application/activity+json',
                    href: `http://localhost:${PORT}/actors/bob`
                }]
            }));
        } else {
            res.writeHead(404);
            res.end();
        }
    } else if (url.pathname === '/actors/bob') {
        res.writeHead(200, { 'Content-Type': 'application/activity+json' });
        res.end(JSON.stringify({
            '@context': ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1'],
            id: `http://localhost:${PORT}/actors/bob`,
            type: 'Person',
            preferredUsername: 'bob',
            inbox: `http://localhost:${PORT}/actors/bob/inbox`,
            publicKey: {
                id: `http://localhost:${PORT}/actors/bob#main-key`,
                owner: `http://localhost:${PORT}/actors/bob`,
                publicKeyPem: '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA3f2f...-----END PUBLIC KEY-----'
            }
        }));
    } else if (url.pathname === '/actors/bob/inbox') {
        res.writeHead(202);
        res.end();
    } else {
        res.writeHead(404);
        res.end();
    }
});

server.listen(PORT, () => {
    console.log(`Mock remote actor listening on port ${PORT}`);
});
