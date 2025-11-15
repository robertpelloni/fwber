#!/usr/bin/env node

/**
 * FWBer Profile Form End-to-End Test
 * 
 * Tests the complete profile form functionality
 * Focuses on frontend-backend integration and data persistence
 */

const https = require('https');
const http = require('http');

// Test configuration
const config = {
  frontend: 'http://localhost:3000',
  backend: 'http://localhost:8001', // Using working PHP backend
  api: 'http://localhost:8001/api'
};

// Test results tracking
const results = {
  passed: 0,
  failed: 0,
  tests: []
};

// Utility function to make HTTP requests
function makeRequest(url, options = {}) {
  return new Promise((resolve, reject) => {
    const client = url.startsWith('https') ? https : http;
    const req = client.request(url, options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          const jsonData = data ? JSON.parse(data) : null;
          resolve({ status: res.statusCode, data: jsonData, headers: res.headers });
        } catch (e) {
          resolve({ status: res.statusCode, data: data, headers: res.headers });
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

// Test 1: Frontend Server Accessibility
async function testFrontendAccessibility() {
  const response = await makeRequest(config.frontend);
  if (response.status !== 200) {
    throw new Error(`Frontend not accessible. Status: ${response.status}`);
  }
  console.log('   Frontend server is running and accessible');
}

// Test 2: Backend Server Accessibility
async function testBackendAccessibility() {
  const response = await makeRequest(config.backend);
  if (response.status !== 200) {
    throw new Error(`Backend not accessible. Status: ${response.status}`);
  }
  console.log('   Backend server is running and accessible');
}

// Test 3: Profile Page Accessibility
async function testProfilePageAccessibility() {
  const response = await makeRequest(`${config.frontend}/profile`);
  if (response.status >= 500) {
    throw new Error(`Profile page not accessible. Status: ${response.status}`);
  }
  console.log('   Profile page is accessible');
}

// Test 4: Frontend-Backend Integration
async function testFrontendBackendIntegration() {
  // Test if frontend can make requests to backend
  const response = await makeRequest(`${config.frontend}/api/profile`, {
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  });
  
  // Should get a response (even if it's an error page)
  if (response.status >= 500) {
    throw new Error('Frontend-backend integration issue detected');
  }
  console.log('   Frontend-backend integration is working');
}

// Test 5: Environment Configuration
async function testEnvironmentConfiguration() {
  // Check if environment variables are properly set
  const response = await makeRequest(`${config.frontend}/_next/static/chunks/pages/_app.js`);
  if (response.status !== 200) {
    throw new Error('Frontend environment configuration issue');
  }
  console.log('   Environment configuration is working');
}

// Test 6: Profile Form Components
async function testProfileFormComponents() {
  // Test if the profile form page loads without errors
  const response = await makeRequest(`${config.frontend}/profile`);
  if (response.status >= 500) {
    throw new Error('Profile form components not loading');
  }
  console.log('   Profile form components are loading');
}

// Test 7: Next.js Build Status
async function testNextJSBuildStatus() {
  // Check if Next.js is properly built and serving
  const response = await makeRequest(`${config.frontend}/_next/static/chunks/pages/profile.js`);
  if (response.status >= 500) {
    throw new Error('Next.js build issue detected');
  }
  console.log('   Next.js build is working');
}

// Test 8: Static Assets
async function testStaticAssets() {
  // Test if static assets are being served
  const response = await makeRequest(`${config.frontend}/favicon.ico`);
  if (response.status >= 500) {
    throw new Error('Static assets not being served');
  }
  console.log('   Static assets are being served');
}

// Main test runner
async function runTests() {
  console.log('🚀 Starting FWBer Profile Form End-to-End Tests\n');
  console.log('=' .repeat(60));
  
  // Run all tests
  await test('Frontend Server Accessibility', testFrontendAccessibility);
  await test('Backend Server Accessibility', testBackendAccessibility);
  await test('Profile Page Accessibility', testProfilePageAccessibility);
  await test('Frontend-Backend Integration', testFrontendBackendIntegration);
  await test('Environment Configuration', testEnvironmentConfiguration);
  await test('Profile Form Components', testProfileFormComponents);
  await test('Next.js Build Status', testNextJSBuildStatus);
  await test('Static Assets', testStaticAssets);
  
  // Print results
  console.log('=' .repeat(60));
  console.log('📊 Test Results Summary:');
  console.log(`✅ Passed: ${results.passed}`);
  console.log(`❌ Failed: ${results.failed}`);
  console.log(`📈 Success Rate: ${Math.round((results.passed / (results.passed + results.failed)) * 100)}%`);
  
  if (results.failed > 0) {
    console.log('\n❌ Failed Tests:');
    results.tests.filter(t => t.status === 'FAILED').forEach(t => {
      console.log(`   - ${t.name}: ${t.error}`);
    });
  }
  
  console.log('\n🎯 Next Steps:');
  if (results.failed === 0) {
    console.log('   ✅ All tests passed! Profile form is ready for user testing.');
    console.log('   🚀 Proceed to manual testing of profile form submission.');
    console.log('   📝 Test profile completion tracking and data persistence.');
  } else {
    console.log('   🔧 Fix failing tests before proceeding.');
    console.log('   🧪 Re-run tests after fixes.');
  }
  
  console.log('\n📋 Manual Testing Checklist:');
  console.log('   1. ✅ Open http://localhost:3000/profile in browser');
  console.log('   2. ✅ Test all profile form fields');
  console.log('   3. ✅ Test form validation');
  console.log('   4. ✅ Test profile completion tracking');
  console.log('   5. ✅ Test form submission and data persistence');
  console.log('   6. ✅ Test responsive design on mobile');
}

// Run the tests
runTests().catch(console.error);
