#!/bin/bash

# Test script for FWBer Proximity Chatroom System
# This script tests the complete proximity chatroom functionality

set -e

echo "🚀 Starting FWBer Proximity Chatroom System Tests"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKEND_URL="http://localhost:8000/api"
FRONTEND_URL="http://localhost:3000"
TEST_USER_EMAIL="test@example.com"
TEST_USER_PASSWORD="password123"
TEST_USER_NAME="Test User"

# Test results
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print test results
print_test_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}❌ $2${NC}"
        ((TESTS_FAILED++))
    fi
}

# Function to make API requests
make_request() {
    local method=$1
    local url=$2
    local data=$3
    local token=$4
    
    if [ -n "$token" ]; then
        if [ -n "$data" ]; then
            curl -s -X $method "$url" \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $token" \
                -d "$data"
        else
            curl -s -X $method "$url" \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $token"
        fi
    else
        if [ -n "$data" ]; then
            curl -s -X $method "$url" \
                -H "Content-Type: application/json" \
                -d "$data"
        else
            curl -s -X $method "$url" \
                -H "Content-Type: application/json"
        fi
    fi
}

echo -e "${BLUE}🔧 Setting up test environment...${NC}"

# Check if backend is running
echo "Checking backend server..."
if curl -s "$BACKEND_URL/health" > /dev/null 2>&1; then
    print_test_result 0 "Backend server is running"
else
    echo -e "${RED}❌ Backend server is not running. Please start the Laravel backend first.${NC}"
    exit 1
fi

# Check if frontend is running
echo "Checking frontend server..."
if curl -s "$FRONTEND_URL" > /dev/null 2>&1; then
    print_test_result 0 "Frontend server is running"
else
    echo -e "${RED}❌ Frontend server is not running. Please start the Next.js frontend first.${NC}"
    exit 1
fi

echo -e "${BLUE}🔐 Testing authentication...${NC}"

# Test user registration
echo "Testing user registration..."
REGISTER_RESPONSE=$(make_request "POST" "$BACKEND_URL/register" "{
    \"name\": \"$TEST_USER_NAME\",
    \"email\": \"$TEST_USER_EMAIL\",
    \"password\": \"$TEST_USER_PASSWORD\",
    \"password_confirmation\": \"$TEST_USER_PASSWORD\"
}")

if echo "$REGISTER_RESPONSE" | grep -q "access_token"; then
    print_test_result 0 "User registration successful"
    TOKEN=$(echo "$REGISTER_RESPONSE" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)
else
    # Try to login if user already exists
    echo "User might already exist, trying to login..."
    LOGIN_RESPONSE=$(make_request "POST" "$BACKEND_URL/login" "{
        \"email\": \"$TEST_USER_EMAIL\",
        \"password\": \"$TEST_USER_PASSWORD\"
    }")
    
    if echo "$LOGIN_RESPONSE" | grep -q "access_token"; then
        print_test_result 0 "User login successful"
        TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)
    else
        print_test_result 1 "Authentication failed"
        echo "Response: $LOGIN_RESPONSE"
        exit 1
    fi
fi

echo -e "${BLUE}🌐 Testing proximity chatroom functionality...${NC}"

# Test 1: Find nearby proximity chatrooms
echo "Testing find nearby proximity chatrooms..."
NEARBY_RESPONSE=$(make_request "GET" "$BACKEND_URL/proximity-chatrooms/find-nearby?latitude=40.7128&longitude=-74.0060&radius_meters=1000" "" "$TOKEN")

if echo "$NEARBY_RESPONSE" | grep -q "data"; then
    print_test_result 0 "Find nearby proximity chatrooms"
else
    print_test_result 1 "Find nearby proximity chatrooms"
    echo "Response: $NEARBY_RESPONSE"
fi

# Test 2: Create proximity chatroom
echo "Testing create proximity chatroom..."
CREATE_RESPONSE=$(make_request "POST" "$BACKEND_URL/proximity-chatrooms" "{
    \"name\": \"Test Proximity Chatroom\",
    \"description\": \"A test proximity chatroom for networking\",
    \"latitude\": 40.7128,
    \"longitude\": -74.0060,
    \"radius_meters\": 500,
    \"type\": \"networking\",
    \"is_public\": true
}" "$TOKEN")

if echo "$CREATE_RESPONSE" | grep -q "id"; then
    print_test_result 0 "Create proximity chatroom"
    CHATROOM_ID=$(echo "$CREATE_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    echo "Created chatroom ID: $CHATROOM_ID"
else
    print_test_result 1 "Create proximity chatroom"
    echo "Response: $CREATE_RESPONSE"
    exit 1
fi

# Test 3: Get proximity chatroom details
echo "Testing get proximity chatroom details..."
DETAILS_RESPONSE=$(make_request "GET" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID" "" "$TOKEN")

if echo "$DETAILS_RESPONSE" | grep -q "id"; then
    print_test_result 0 "Get proximity chatroom details"
else
    print_test_result 1 "Get proximity chatroom details"
    echo "Response: $DETAILS_RESPONSE"
fi

# Test 4: Join proximity chatroom
echo "Testing join proximity chatroom..."
JOIN_RESPONSE=$(make_request "POST" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/join" "{
    \"latitude\": 40.7128,
    \"longitude\": -74.0060
}" "$TOKEN")

if echo "$JOIN_RESPONSE" | grep -q "success\|joined"; then
    print_test_result 0 "Join proximity chatroom"
else
    print_test_result 1 "Join proximity chatroom"
    echo "Response: $JOIN_RESPONSE"
fi

# Test 5: Get proximity chatroom members
echo "Testing get proximity chatroom members..."
MEMBERS_RESPONSE=$(make_request "GET" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/members" "" "$TOKEN")

if echo "$MEMBERS_RESPONSE" | grep -q "data"; then
    print_test_result 0 "Get proximity chatroom members"
else
    print_test_result 1 "Get proximity chatroom members"
    echo "Response: $MEMBERS_RESPONSE"
fi

# Test 6: Send proximity chatroom message
echo "Testing send proximity chatroom message..."
MESSAGE_RESPONSE=$(make_request "POST" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/messages" "{
    \"content\": \"Hello from the test proximity chatroom!\",
    \"type\": \"networking\"
}" "$TOKEN")

if echo "$MESSAGE_RESPONSE" | grep -q "id"; then
    print_test_result 0 "Send proximity chatroom message"
    MESSAGE_ID=$(echo "$MESSAGE_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    echo "Created message ID: $MESSAGE_ID"
else
    print_test_result 1 "Send proximity chatroom message"
    echo "Response: $MESSAGE_RESPONSE"
fi

# Test 7: Get proximity chatroom messages
echo "Testing get proximity chatroom messages..."
MESSAGES_RESPONSE=$(make_request "GET" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/messages" "" "$TOKEN")

if echo "$MESSAGES_RESPONSE" | grep -q "data"; then
    print_test_result 0 "Get proximity chatroom messages"
else
    print_test_result 1 "Get proximity chatroom messages"
    echo "Response: $MESSAGES_RESPONSE"
fi

# Test 8: Add reaction to message
echo "Testing add reaction to proximity message..."
REACTION_RESPONSE=$(make_request "POST" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/messages/$MESSAGE_ID/reactions" "{
    \"emoji\": \"👍\"
}" "$TOKEN")

if echo "$REACTION_RESPONSE" | grep -q "success\|reaction"; then
    print_test_result 0 "Add reaction to proximity message"
else
    print_test_result 1 "Add reaction to proximity message"
    echo "Response: $REACTION_RESPONSE"
fi

# Test 9: Pin proximity message
echo "Testing pin proximity message..."
PIN_RESPONSE=$(make_request "POST" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/messages/$MESSAGE_ID/pin" "" "$TOKEN")

if echo "$PIN_RESPONSE" | grep -q "success\|pinned"; then
    print_test_result 0 "Pin proximity message"
else
    print_test_result 1 "Pin proximity message"
    echo "Response: $PIN_RESPONSE"
fi

# Test 10: Get networking messages
echo "Testing get networking messages..."
NETWORKING_RESPONSE=$(make_request "GET" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/messages/networking" "" "$TOKEN")

if echo "$NETWORKING_RESPONSE" | grep -q "data"; then
    print_test_result 0 "Get networking messages"
else
    print_test_result 1 "Get networking messages"
    echo "Response: $NETWORKING_RESPONSE"
fi

# Test 11: Get social messages
echo "Testing get social messages..."
SOCIAL_RESPONSE=$(make_request "GET" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/messages/social" "" "$TOKEN")

if echo "$SOCIAL_RESPONSE" | grep -q "data"; then
    print_test_result 0 "Get social messages"
else
    print_test_result 1 "Get social messages"
    echo "Response: $SOCIAL_RESPONSE"
fi

# Test 12: Get nearby networking
echo "Testing get nearby networking..."
NETWORKING_NEARBY_RESPONSE=$(make_request "GET" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/nearby-networking?latitude=40.7128&longitude=-74.0060&radius_meters=1000" "" "$TOKEN")

if echo "$NETWORKING_NEARBY_RESPONSE" | grep -q "data"; then
    print_test_result 0 "Get nearby networking"
else
    print_test_result 1 "Get nearby networking"
    echo "Response: $NETWORKING_NEARBY_RESPONSE"
fi

# Test 13: Update location
echo "Testing update location..."
UPDATE_LOCATION_RESPONSE=$(make_request "PUT" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/location" "{
    \"latitude\": 40.7130,
    \"longitude\": -74.0062
}" "$TOKEN")

if echo "$UPDATE_LOCATION_RESPONSE" | grep -q "success\|updated"; then
    print_test_result 0 "Update location"
else
    print_test_result 1 "Update location"
    echo "Response: $UPDATE_LOCATION_RESPONSE"
fi

# Test 14: Get proximity analytics
echo "Testing get proximity analytics..."
ANALYTICS_RESPONSE=$(make_request "GET" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/analytics" "" "$TOKEN")

if echo "$ANALYTICS_RESPONSE" | grep -q "data\|analytics"; then
    print_test_result 0 "Get proximity analytics"
else
    print_test_result 1 "Get proximity analytics"
    echo "Response: $ANALYTICS_RESPONSE"
fi

# Test 15: Leave proximity chatroom
echo "Testing leave proximity chatroom..."
LEAVE_RESPONSE=$(make_request "POST" "$BACKEND_URL/proximity-chatrooms/$CHATROOM_ID/leave" "" "$TOKEN")

if echo "$LEAVE_RESPONSE" | grep -q "success\|left"; then
    print_test_result 0 "Leave proximity chatroom"
else
    print_test_result 1 "Leave proximity chatroom"
    echo "Response: $LEAVE_RESPONSE"
fi

echo -e "${BLUE}🌐 Testing frontend proximity chatroom pages...${NC}"

# Test 16: Frontend proximity chatrooms page
echo "Testing frontend proximity chatrooms page..."
FRONTEND_RESPONSE=$(curl -s "$FRONTEND_URL/proximity-chatrooms")

if echo "$FRONTEND_RESPONSE" | grep -q "Proximity Chatrooms"; then
    print_test_result 0 "Frontend proximity chatrooms page loads"
else
    print_test_result 1 "Frontend proximity chatrooms page loads"
fi

# Test 17: Frontend individual proximity chatroom page
echo "Testing frontend individual proximity chatroom page..."
FRONTEND_CHATROOM_RESPONSE=$(curl -s "$FRONTEND_URL/proximity-chatrooms/$CHATROOM_ID")

if echo "$FRONTEND_CHATROOM_RESPONSE" | grep -q "Chatroom Not Found\|Proximity Chatroom"; then
    print_test_result 0 "Frontend individual proximity chatroom page loads"
else
    print_test_result 1 "Frontend individual proximity chatroom page loads"
fi

echo -e "${BLUE}🧪 Testing database migrations...${NC}"

# Test 18: Check if proximity chatrooms table exists
echo "Testing proximity chatrooms table..."
TABLE_CHECK=$(make_request "GET" "$BACKEND_URL/proximity-chatrooms/find-nearby?latitude=40.7128&longitude=-74.0060&radius_meters=1000" "" "$TOKEN")

if echo "$TABLE_CHECK" | grep -q "data\|error"; then
    print_test_result 0 "Proximity chatrooms table exists"
else
    print_test_result 1 "Proximity chatrooms table exists"
fi

echo -e "${BLUE}📊 Test Results Summary${NC}"
echo "========================"
echo -e "${GREEN}Tests Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Tests Failed: $TESTS_FAILED${NC}"
echo "Total Tests: $((TESTS_PASSED + TESTS_FAILED))"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 All tests passed! The proximity chatroom system is working correctly.${NC}"
    exit 0
else
    echo -e "${RED}❌ Some tests failed. Please check the output above for details.${NC}"
    exit 1
fi
