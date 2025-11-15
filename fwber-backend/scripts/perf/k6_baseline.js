import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: __ENV.VUS ? parseInt(__ENV.VUS) : 20,
  duration: __ENV.DURATION || '2m',
  thresholds: {
    http_req_failed: ['rate<0.01'], // <1% errors
    http_req_duration: ['p(95)<500'], // 95% under 500ms
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const TOKEN = __ENV.TOKEN || '';

export default function () {
  // Liveness
  let res = http.get(`${BASE_URL}/health/liveness`);
  check(res, {
    'liveness is 200': (r) => r.status === 200,
  });

  // Readiness
  res = http.get(`${BASE_URL}/health/readiness`);
  check(res, {
    'readiness is 200': (r) => r.status === 200,
  });

  const headers = TOKEN ? { Authorization: `Bearer ${TOKEN}` } : {};

  // Dashboard
  res = http.get(`${BASE_URL}/api/dashboard/stats`, { headers });
  check(res, { 'dashboard 2xx': (r) => r.status >= 200 && r.status < 300 });

  // Matches
  res = http.get(`${BASE_URL}/api/matches`, { headers });
  check(res, { 'matches 2xx': (r) => r.status >= 200 && r.status < 300 });

  // Profile
  res = http.get(`${BASE_URL}/api/user`, { headers });
  check(res, { 'profile 2xx': (r) => r.status >= 200 && r.status < 300 });

  sleep(1);
}
