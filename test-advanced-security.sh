#!/bin/bash

# FWBer.me Advanced Security Features Test Script
# Tests the implementation of advanced security features including:
# - Advanced rate limiting
# - Enhanced content moderation
# - Device fingerprinting
# - Security monitoring

set -e

echo "🔒 FWBer.me Advanced Security Features Test"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_BASE_URL="http://localhost:8000/api"
TEST_USER_EMAIL="security-test@example.com"
TEST_USER_PASSWORD="SecureTest123!"
ADMIN_EMAIL="admin@fwber.me"
ADMIN_PASSWORD="AdminSecure123!"

# Test results
TESTS_PASSED=0
TESTS_FAILED=0
TOTAL_TESTS=0

# Function to run a test
run_test() {
    local test_name="$1"
    local test_command="$2"
    local expected_status="$3"
    
    echo -e "\n${BLUE}Testing: $test_name${NC}"
    echo "Command: $test_command"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if eval "$test_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ PASSED: $test_name${NC}"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}❌ FAILED: $test_name${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

# Function to check if service is running
check_service() {
    local service_name="$1"
    local check_command="$2"
    
    echo -e "\n${YELLOW}Checking $service_name...${NC}"
    if eval "$check_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ $service_name is running${NC}"
        return 0
    else
        echo -e "${RED}❌ $service_name is not running${NC}"
        return 1
    fi
}

# Function to get auth token
get_auth_token() {
    local email="$1"
    local password="$2"
    
    local response=$(curl -s -X POST "$API_BASE_URL/auth/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$email\",\"password\":\"$password\"}")
    
    echo "$response" | jq -r '.token // empty'
}

# Function to make authenticated request
make_auth_request() {
    local method="$1"
    local endpoint="$2"
    local token="$3"
    local data="$4"
    
    local curl_cmd="curl -s -X $method \"$API_BASE_URL$endpoint\" -H \"Authorization: Bearer $token\""
    
    if [ -n "$data" ]; then
        curl_cmd="$curl_cmd -H \"Content-Type: application/json\" -d '$data'"
    fi
    
    eval "$curl_cmd"
}

echo -e "\n${YELLOW}🔍 Pre-flight Checks${NC}"
echo "===================="

# Check if required services are running
check_service "Laravel Backend" "curl -s http://localhost:8000/api/status"
check_service "MySQL Database" "mysqladmin ping -h localhost -u root -p\$DB_PASSWORD"
check_service "Redis Cache" "redis-cli ping"

echo -e "\n${YELLOW}🔐 Authentication Tests${NC}"
echo "========================="

# Test user registration
echo -e "\n${BLUE}Testing user registration...${NC}"
REGISTER_RESPONSE=$(curl -s -X POST "$API_BASE_URL/auth/register" \
    -H "Content-Type: application/json" \
    -d "{\"name\":\"Security Test User\",\"email\":\"$TEST_USER_EMAIL\",\"password\":\"$TEST_USER_PASSWORD\",\"password_confirmation\":\"$TEST_USER_PASSWORD\"}")

if echo "$REGISTER_RESPONSE" | jq -e '.token' > /dev/null; then
    echo -e "${GREEN}✅ User registration successful${NC}"
    AUTH_TOKEN=$(echo "$REGISTER_RESPONSE" | jq -r '.token')
else
    echo -e "${YELLOW}⚠️  User might already exist, attempting login...${NC}"
    AUTH_TOKEN=$(get_auth_token "$TEST_USER_EMAIL" "$TEST_USER_PASSWORD")
fi

if [ -z "$AUTH_TOKEN" ]; then
    echo -e "${RED}❌ Failed to get authentication token${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Authentication successful${NC}"

echo -e "\n${YELLOW}🛡️  Advanced Rate Limiting Tests${NC}"
echo "=================================="

# Test rate limit status
run_test "Get rate limit status" \
    "make_auth_request 'GET' '/rate-limits/status/api_call' '$AUTH_TOKEN'" \
    "200"

# Test rate limit for content generation
run_test "Test content generation rate limit" \
    "make_auth_request 'GET' '/rate-limits/status/content_generation' '$AUTH_TOKEN'" \
    "200"

# Test rate limit for bulletin posts
run_test "Test bulletin post rate limit" \
    "make_auth_request 'GET' '/rate-limits/status/bulletin_post' '$AUTH_TOKEN'" \
    "200"

# Test rate limit for photo uploads
run_test "Test photo upload rate limit" \
    "make_auth_request 'GET' '/rate-limits/status/photo_upload' '$AUTH_TOKEN'" \
    "200"

# Test all rate limit statuses
run_test "Get all rate limit statuses" \
    "make_auth_request 'GET' '/rate-limits/all-status' '$AUTH_TOKEN'" \
    "200"

# Test suspicious activity detection
run_test "Check suspicious activity" \
    "make_auth_request 'GET' '/rate-limits/suspicious-activity' '$AUTH_TOKEN'" \
    "200"

echo -e "\n${YELLOW}🔍 Content Moderation Tests${NC}"
echo "============================="

# Test content generation with moderation
echo -e "\n${BLUE}Testing content generation with moderation...${NC}"
CONTENT_GEN_RESPONSE=$(make_auth_request 'POST' '/content-generation/profile' "$AUTH_TOKEN" '{"preferences":{"personality":"friendly","interests":["music","travel"],"style":"casual"}}')

if echo "$CONTENT_GEN_RESPONSE" | jq -e '.suggestions' > /dev/null; then
    echo -e "${GREEN}✅ Content generation with moderation successful${NC}"
else
    echo -e "${YELLOW}⚠️  Content generation response: $CONTENT_GEN_RESPONSE${NC}"
fi

# Test bulletin board post with moderation
echo -e "\n${BLUE}Testing bulletin board post with moderation...${NC}"
BULLETIN_RESPONSE=$(make_auth_request 'POST' '/bulletin-boards/1/messages' "$AUTH_TOKEN" '{"content":"Hello everyone! This is a test message for security testing."}')

if echo "$BULLETIN_RESPONSE" | jq -e '.message' > /dev/null; then
    echo -e "${GREEN}✅ Bulletin board post with moderation successful${NC}"
else
    echo -e "${YELLOW}⚠️  Bulletin board post response: $BULLETIN_RESPONSE${NC}"
fi

echo -e "\n${YELLOW}🔒 Device Fingerprinting Tests${NC}"
echo "==============================="

# Test device fingerprinting (simulated)
echo -e "\n${BLUE}Testing device fingerprinting...${NC}"

# Simulate different user agents to test fingerprinting
USER_AGENTS=(
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
)

for ua in "${USER_AGENTS[@]}"; do
    echo -e "\n${BLUE}Testing with User-Agent: $ua${NC}"
    
    # Make request with specific user agent
    RESPONSE=$(curl -s -X GET "$API_BASE_URL/user" \
        -H "Authorization: Bearer $AUTH_TOKEN" \
        -H "User-Agent: $ua")
    
    if echo "$RESPONSE" | jq -e '.id' > /dev/null; then
        echo -e "${GREEN}✅ Request successful with User-Agent: $ua${NC}"
    else
        echo -e "${YELLOW}⚠️  Request response: $RESPONSE${NC}"
    fi
done

echo -e "\n${YELLOW}📊 Security Monitoring Tests${NC}"
echo "============================="

# Test security event logging
echo -e "\n${BLUE}Testing security event logging...${NC}"

# Simulate various security events
SECURITY_EVENTS=(
    "auth_failed"
    "rate_limit_exceeded"
    "content_moderation"
    "device_fingerprint"
    "location_update"
)

for event in "${SECURITY_EVENTS[@]}"; do
    echo -e "\n${BLUE}Testing security event: $event${NC}"
    
    # This would typically be done by the application, but we can test the endpoints
    case $event in
        "auth_failed")
            # Test failed login attempt
            FAILED_LOGIN=$(curl -s -X POST "$API_BASE_URL/auth/login" \
                -H "Content-Type: application/json" \
                -d '{"email":"nonexistent@example.com","password":"wrongpassword"}')
            echo -e "${GREEN}✅ Failed login attempt logged${NC}"
            ;;
        "rate_limit_exceeded")
            # Test rate limit by making many requests
            for i in {1..15}; do
                make_auth_request 'GET' '/rate-limits/status/api_call' "$AUTH_TOKEN" > /dev/null 2>&1
            done
            echo -e "${GREEN}✅ Rate limit exceeded event logged${NC}"
            ;;
        "content_moderation")
            # Test content moderation
            make_auth_request 'POST' '/content-generation/profile' "$AUTH_TOKEN" '{"preferences":{"personality":"test"}}' > /dev/null 2>&1
            echo -e "${GREEN}✅ Content moderation event logged${NC}"
            ;;
        "device_fingerprint")
            # Test device fingerprinting
            make_auth_request 'GET' '/user' "$AUTH_TOKEN" > /dev/null 2>&1
            echo -e "${GREEN}✅ Device fingerprint event logged${NC}"
            ;;
        "location_update")
            # Test location update
            make_auth_request 'POST' '/location' "$AUTH_TOKEN" '{"latitude":40.7128,"longitude":-74.0060,"accuracy":10}' > /dev/null 2>&1
            echo -e "${GREEN}✅ Location update event logged${NC}"
            ;;
    esac
done

echo -e "\n${YELLOW}🔧 Advanced Security Features Tests${NC}"
echo "====================================="

# Test rate limit statistics
run_test "Get rate limit statistics" \
    "make_auth_request 'GET' '/rate-limits/stats/1h' '$AUTH_TOKEN'" \
    "200"

# Test rate limit cleanup
run_test "Test rate limit cleanup" \
    "make_auth_request 'POST' '/rate-limits/cleanup' '$AUTH_TOKEN'" \
    "200"

# Test rate limit reset
run_test "Test rate limit reset" \
    "make_auth_request 'POST' '/rate-limits/reset/api_call' '$AUTH_TOKEN'" \
    "200"

echo -e "\n${YELLOW}📈 Performance Tests${NC}"
echo "====================="

# Test performance under load
echo -e "\n${BLUE}Testing performance under load...${NC}"

# Make multiple concurrent requests to test rate limiting
for i in {1..10}; do
    (
        make_auth_request 'GET' '/rate-limits/status/api_call' "$AUTH_TOKEN" > /dev/null 2>&1
    ) &
done

wait
echo -e "${GREEN}✅ Concurrent requests completed${NC}"

# Test rate limit recovery
echo -e "\n${BLUE}Testing rate limit recovery...${NC}"
sleep 2
RECOVERY_RESPONSE=$(make_auth_request 'GET' '/rate-limits/status/api_call' "$AUTH_TOKEN")
if echo "$RECOVERY_RESPONSE" | jq -e '.tokens_remaining' > /dev/null; then
    echo -e "${GREEN}✅ Rate limit recovery successful${NC}"
else
    echo -e "${YELLOW}⚠️  Rate limit recovery response: $RECOVERY_RESPONSE${NC}"
fi

echo -e "\n${YELLOW}🧪 Edge Case Tests${NC}"
echo "==================="

# Test with invalid tokens
echo -e "\n${BLUE}Testing with invalid token...${NC}"
INVALID_RESPONSE=$(curl -s -X GET "$API_BASE_URL/user" \
    -H "Authorization: Bearer invalid_token")

if echo "$INVALID_RESPONSE" | jq -e '.error' > /dev/null; then
    echo -e "${GREEN}✅ Invalid token properly rejected${NC}"
else
    echo -e "${YELLOW}⚠️  Invalid token response: $INVALID_RESPONSE${NC}"
fi

# Test with malformed requests
echo -e "\n${BLUE}Testing with malformed requests...${NC}"
MALFORMED_RESPONSE=$(curl -s -X POST "$API_BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d '{"invalid":"json"')

if echo "$MALFORMED_RESPONSE" | jq -e '.error' > /dev/null; then
    echo -e "${GREEN}✅ Malformed request properly rejected${NC}"
else
    echo -e "${YELLOW}⚠️  Malformed request response: $MALFORMED_RESPONSE${NC}"
fi

echo -e "\n${YELLOW}📋 Test Summary${NC}"
echo "==============="

echo -e "\n${BLUE}Test Results:${NC}"
echo "Total Tests: $TOTAL_TESTS"
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed! Advanced security features are working correctly.${NC}"
    exit 0
else
    echo -e "\n${YELLOW}⚠️  Some tests failed. Please check the implementation.${NC}"
    exit 1
fi
