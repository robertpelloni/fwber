const fs = require('fs');
const path = require('path');

const envPath = path.join(__dirname, '../.env.local');
let env = {};

if (fs.existsSync(envPath)) {
    const content = fs.readFileSync(envPath, 'utf8');
    content.split('\n').forEach(line => {
        const [key, value] = line.split('=');
        if (key && value) {
            env[key.trim()] = value.trim();
        }
    });
} else {
    console.log("⚠️  .env.local file not found. Checking process.env...");
    env = process.env;
}

const checks = {
    'API': {
        'NEXT_PUBLIC_API_URL': 'Backend API URL',
        'NEXT_PUBLIC_MERCURE_URL': 'Mercure URL',
    },
    'Push Notifications': {
        'NEXT_PUBLIC_VAPID_PUBLIC_KEY': 'VAPID Public Key',
    },
    'Stripe': {
        'NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY': 'Stripe Publishable Key',
    },
    'Sentry': {
        'NEXT_PUBLIC_SENTRY_DSN': 'Sentry DSN',
        'SENTRY_AUTH_TOKEN': 'Sentry Auth Token (Build)',
    },
    'Feature Flags': {
        'NEXT_PUBLIC_FEATURE_FACE_REVEAL': 'Face Reveal Feature',
    }
};

console.log("Checking Frontend Environment Configuration...\n");

Object.entries(checks).forEach(([category, vars]) => {
    console.log(`[${category}]`);
    Object.entries(vars).forEach(([key, description]) => {
        const value = env[key];
        const status = value ? "✅ Set" : "❌ Missing";
        let masked = value ? value.substring(0, 4) + '...' : 'N/A';
        
        if (['NEXT_PUBLIC_API_URL', 'NEXT_PUBLIC_MERCURE_URL', 'NEXT_PUBLIC_FEATURE_FACE_REVEAL'].includes(key)) {
            masked = value || 'N/A';
        }

        console.log(`  ${key.padEnd(35)} ${status.padEnd(10)} ${masked.padEnd(20)} (${description})`);
    });
    console.log("");
});
