import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: __ENV.VUS ? parseInt(__ENV.VUS) : 20,
  duration: __ENV.DURATION || '2m',
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests must complete below 500ms
    http_req_failed: ['rate<0.01'], // http errors should be less than 1%
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const TOKEN = __ENV.TOKEN || '';

export default function () {
  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${TOKEN}`,
      'Accept': 'application/json',
    },
  };

  // 1. Health Check
  const healthRes = http.get(`${BASE_URL}/api/health`, { headers: { 'Accept': 'application/json' } });
  check(healthRes, {
    'health status is 200': (r) => r.status === 200,
  });

  // Only proceed with authenticated requests if we have a token
  if (TOKEN) {
    // 2. Dashboard Stats
    const dashboardRes = http.get(`${BASE_URL}/api/dashboard/stats`, params);
    check(dashboardRes, {
      'dashboard status is 200': (r) => r.status === 200,
    });

    // 3. Matches
    const matchesRes = http.get(`${BASE_URL}/api/matches`, params);
    check(matchesRes, {
      'matches status is 200': (r) => r.status === 200,
    });

    // 4. Messages
    const messagesRes = http.get(`${BASE_URL}/api/messages`, params);
    check(messagesRes, {
      'messages status is 200': (r) => r.status === 200,
    });
  }

  sleep(1);
}
