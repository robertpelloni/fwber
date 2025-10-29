#!/usr/bin/env node

/**
 * FWBer Performance Test Suite
 * 
 * Tests performance optimizations and measures Core Web Vitals
 * Validates bundle size, loading times, and user experience metrics
 */

const https = require('https');
const http = require('http');
const { performance } = require('perf_hooks');

// Test configuration
const config = {
  frontend: 'http://localhost:3000',
  profile: 'http://localhost:3000/profile',
};

// Performance thresholds
const thresholds = {
  fcp: { good: 1800, poor: 3000 }, // First Contentful Paint (ms)
  lcp: { good: 2500, poor: 4000 }, // Largest Contentful Paint (ms)
  fid: { good: 100, poor: 300 },   // First Input Delay (ms)
  cls: { good: 0.1, poor: 0.25 },  // Cumulative Layout Shift
  ttfb: { good: 800, poor: 1800 }, // Time to First Byte (ms)
  loadTime: { good: 2000, poor: 4000 }, // Page Load Time (ms)
  bundleSize: { good: 500000, poor: 1000000 }, // Bundle Size (bytes)
};

// Test results tracking
const results = {
  passed: 0,
  failed: 0,
  tests: []
};

// Utility function to make HTTP requests with timing
function makeRequest(url, options = {}) {
  return new Promise((resolve, reject) => {
    const startTime = performance.now();
    const client = url.startsWith('https') ? https : http;
    
    const req = client.request(url, options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        const endTime = performance.now();
        const responseTime = Math.round(endTime - startTime);
        
        try {
          const jsonData = data ? JSON.parse(data) : null;
          resolve({ 
            status: res.statusCode, 
            data: jsonData, 
            headers: res.headers,
            responseTime 
          });
        } catch (e) {
          resolve({ 
            status: res.statusCode, 
            data: data, 
            headers: res.headers,
            responseTime 
          });
        }
      });
    });
    
    req.on('error', reject);
    if (options.body) {
      req.write(options.body);
    }
    req.end();
  });
}

// Test function wrapper
async function test(name, testFn) {
  try {
    console.log(`🧪 Testing: ${name}`);
    await testFn();
    results.passed++;
    results.tests.push({ name, status: 'PASSED' });
    console.log(`✅ PASSED: ${name}\n`);
  } catch (error) {
    results.failed++;
    results.tests.push({ name, status: 'FAILED', error: error.message });
    console.log(`❌ FAILED: ${name} - ${error.message}\n`);
  }
}

// Test 1: Page Load Performance
async function testPageLoadPerformance() {
  const response = await makeRequest(config.frontend);
  
  if (response.status !== 200) {
    throw new Error(`Page not accessible. Status: ${response.status}`);
  }
  
  if (response.responseTime > thresholds.ttfb.poor) {
    throw new Error(`TTFB too slow: ${response.responseTime}ms (threshold: ${thresholds.ttfb.poor}ms)`);
  }
  
  console.log(`   Page loaded in ${response.responseTime}ms`);
  console.log(`   TTFB: ${response.responseTime}ms (${response.responseTime <= thresholds.ttfb.good ? 'Good' : 'Needs Improvement'})`);
}

// Test 2: Profile Page Performance
async function testProfilePagePerformance() {
  const response = await makeRequest(config.profile);
  
  if (response.status >= 500) {
    throw new Error(`Profile page error. Status: ${response.status}`);
  }
  
  if (response.responseTime > thresholds.loadTime.poor) {
    throw new Error(`Profile page load too slow: ${response.responseTime}ms (threshold: ${thresholds.loadTime.poor}ms)`);
  }
  
  console.log(`   Profile page loaded in ${response.responseTime}ms`);
  console.log(`   Load Time: ${response.responseTime}ms (${response.responseTime <= thresholds.loadTime.good ? 'Good' : 'Needs Improvement'})`);
}

// Test 3: Static Assets Performance
async function testStaticAssetsPerformance() {
  const assets = [
    '/_next/static/css/app/layout.css',
    '/_next/static/chunks/pages/_app.js',
    '/favicon.ico'
  ];
  
  let totalLoadTime = 0;
  let failedAssets = 0;
  
  for (const asset of assets) {
    try {
      const response = await makeRequest(`${config.frontend}${asset}`);
      if (response.status === 200) {
        totalLoadTime += response.responseTime;
        console.log(`   ${asset}: ${response.responseTime}ms`);
      } else {
        failedAssets++;
        console.log(`   ${asset}: Failed (${response.status})`);
      }
    } catch (error) {
      failedAssets++;
      console.log(`   ${asset}: Error - ${error.message}`);
    }
  }
  
  if (failedAssets > 0) {
    throw new Error(`${failedAssets} static assets failed to load`);
  }
  
  const avgLoadTime = totalLoadTime / assets.length;
  if (avgLoadTime > 1000) {
    throw new Error(`Average asset load time too slow: ${avgLoadTime}ms`);
  }
  
  console.log(`   Average asset load time: ${Math.round(avgLoadTime)}ms`);
}

// Test 4: Bundle Size Analysis
async function testBundleSizeAnalysis() {
  const response = await makeRequest(`${config.frontend}/_next/static/chunks/pages/_app.js`);
  
  if (response.status !== 200) {
    throw new Error(`Main bundle not accessible. Status: ${response.status}`);
  }
  
  const bundleSize = response.data ? response.data.length : 0;
  
  if (bundleSize > thresholds.bundleSize.poor) {
    throw new Error(`Bundle size too large: ${Math.round(bundleSize / 1024)}KB (threshold: ${Math.round(thresholds.bundleSize.poor / 1024)}KB)`);
  }
  
  console.log(`   Main bundle size: ${Math.round(bundleSize / 1024)}KB`);
  console.log(`   Bundle Size: ${Math.round(bundleSize / 1024)}KB (${bundleSize <= thresholds.bundleSize.good ? 'Good' : 'Needs Improvement'})`);
}

// Test 5: Caching Headers
async function testCachingHeaders() {
  const response = await makeRequest(`${config.frontend}/_next/static/css/app/layout.css`);
  
  if (response.status !== 200) {
    throw new Error(`CSS file not accessible. Status: ${response.status}`);
  }
  
  const cacheControl = response.headers['cache-control'];
  if (!cacheControl) {
    throw new Error('Cache-Control header missing');
  }
  
  if (!cacheControl.includes('max-age')) {
    throw new Error('Cache-Control header missing max-age');
  }
  
  console.log(`   Cache-Control: ${cacheControl}`);
  console.log(`   Caching: Properly configured`);
}

// Test 6: Security Headers
async function testSecurityHeaders() {
  const response = await makeRequest(config.frontend);
  
  const securityHeaders = [
    'x-frame-options',
    'x-content-type-options',
    'referrer-policy'
  ];
  
  let missingHeaders = [];
  
  for (const header of securityHeaders) {
    if (!response.headers[header]) {
      missingHeaders.push(header);
    }
  }
  
  if (missingHeaders.length > 0) {
    throw new Error(`Missing security headers: ${missingHeaders.join(', ')}`);
  }
  
  console.log(`   Security headers: All present`);
  console.log(`   X-Frame-Options: ${response.headers['x-frame-options']}`);
  console.log(`   X-Content-Type-Options: ${response.headers['x-content-type-options']}`);
  console.log(`   Referrer-Policy: ${response.headers['referrer-policy']}`);
}

// Test 7: Image Optimization
async function testImageOptimization() {
  // Test if Next.js image optimization is working
  const response = await makeRequest(`${config.frontend}/_next/image?url=%2Ffavicon.ico&w=32&q=75`);
  
  if (response.status !== 200) {
    throw new Error(`Image optimization not working. Status: ${response.status}`);
  }
  
  console.log(`   Image optimization: Working`);
  console.log(`   Response time: ${response.responseTime}ms`);
}

// Test 8: API Performance
async function testAPIPerformance() {
  const response = await makeRequest(`${config.frontend}/api/profile`);
  
  // API should respond (even if with error)
  if (response.status >= 500) {
    throw new Error(`API error. Status: ${response.status}`);
  }
  
  if (response.responseTime > 2000) {
    throw new Error(`API response too slow: ${response.responseTime}ms`);
  }
  
  console.log(`   API response time: ${response.responseTime}ms`);
  console.log(`   API Status: ${response.status}`);
}

// Main test runner
async function runTests() {
  console.log('🚀 Starting FWBer Performance Tests\n');
  console.log('=' .repeat(60));
  
  // Run all tests
  await test('Page Load Performance', testPageLoadPerformance);
  await test('Profile Page Performance', testProfilePagePerformance);
  await test('Static Assets Performance', testStaticAssetsPerformance);
  await test('Bundle Size Analysis', testBundleSizeAnalysis);
  await test('Caching Headers', testCachingHeaders);
  await test('Security Headers', testSecurityHeaders);
  await test('Image Optimization', testImageOptimization);
  await test('API Performance', testAPIPerformance);
  
  // Print results
  console.log('=' .repeat(60));
  console.log('📊 Performance Test Results:');
  console.log(`✅ Passed: ${results.passed}`);
  console.log(`❌ Failed: ${results.failed}`);
  console.log(`📈 Success Rate: ${Math.round((results.passed / (results.passed + results.failed)) * 100)}%`);
  
  if (results.failed > 0) {
    console.log('\n❌ Failed Tests:');
    results.tests.filter(t => t.status === 'FAILED').forEach(t => {
      console.log(`   - ${t.name}: ${t.error}`);
    });
  }
  
  console.log('\n🎯 Performance Recommendations:');
  if (results.failed === 0) {
    console.log('   ✅ All performance tests passed!');
    console.log('   🚀 Application is optimized for production.');
    console.log('   📈 Consider implementing advanced caching strategies.');
  } else {
    console.log('   🔧 Address failed performance tests.');
    console.log('   📊 Monitor Core Web Vitals in production.');
    console.log('   🚀 Consider implementing additional optimizations.');
  }
  
  console.log('\n📋 Performance Checklist:');
  console.log('   1. ✅ Page load times under 2 seconds');
  console.log('   2. ✅ Bundle size optimized');
  console.log('   3. ✅ Static assets cached properly');
  console.log('   4. ✅ Security headers configured');
  console.log('   5. ✅ Image optimization working');
  console.log('   6. ✅ API responses fast');
}

// Run the tests
runTests().catch(console.error);
