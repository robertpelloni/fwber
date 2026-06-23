import express from 'express';
import bodyParser from 'body-parser';
import crypto from 'crypto';

const app = express();
const port = 8082; // Remote mock port

app.use(bodyParser.json({ type: ['application/json', 'application/activity+json'] }));

// Store received activities
export const receivedActivities: any[] = [];

// Generate remote keys
export const { publicKey, privateKey } = crypto.generateKeyPairSync('rsa', {
  modulusLength: 2048,
  publicKeyEncoding: { type: 'spki', format: 'pem' },
  privateKeyEncoding: { type: 'pkcs8', format: 'pem' }
});

// Mock WebFinger
app.get('/.well-known/webfinger', (req, res) => {
  res.json({
    subject: req.query.resource,
    links: [
      {
        rel: 'self',
        type: 'application/activity+json',
        href: `http://localhost:${port}/actor/mockuser`
      }
    ]
  });
});

// Mock Actor
app.get('/actor/mockuser', (req, res) => {
  res.json({
    '@context': 'https://www.w3.org/ns/activitystreams',
    id: `http://localhost:${port}/actor/mockuser`,
    type: 'Person',
    preferredUsername: 'mockuser',
    inbox: `http://localhost:${port}/actor/mockuser/inbox`,
    publicKey: {
      id: `http://localhost:${port}/actor/mockuser#main-key`,
      owner: `http://localhost:${port}/actor/mockuser`,
      publicKeyPem: publicKey
    }
  });
});

// Mock Inbox
app.post('/actor/mockuser/inbox', (req, res) => {
  const signature = req.headers.signature;
  if (!signature) {
    return res.status(401).send('Missing signature');
  }

  receivedActivities.push(req.body);
  res.status(202).send('Accepted');
});

export const startMockServer = () => {
    return new Promise((resolve) => {
        const server = app.listen(port, () => {
            console.log(`Mock Mastodon actor running on http://localhost:${port}`);
            resolve(server);
        });
    });
};
