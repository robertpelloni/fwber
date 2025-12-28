import aiohttp
import asyncio
import random
import json
import time
import os
import sys
from datetime import datetime

# --- Configuration ---
# API Base URL - Default to localhost for testing, but can be overridden
BASE_URL = os.environ.get("API_BASE_URL", "http://localhost:8000/api")
EMAIL = os.environ.get("TEST_USER_EMAIL", "test@example.com")
PASSWORD = os.environ.get("TEST_USER_PASSWORD", "password")

# Number of concurrent users to simulate
CONCURRENT_USERS = int(os.environ.get("CONCURRENT_USERS", 5))

# Duration of the test in seconds (0 for infinite)
DURATION = int(os.environ.get("TEST_DURATION", 60))

# Request delay range (min, max) in seconds
DELAY_RANGE = (0.5, 3.0)

# Endpoints and their weights (higher weight = more frequent)
ENDPOINTS = [
    {
        "method": "GET",
        "url": "/leaderboard",
        "weight": 5,
        "name": "Leaderboard (Cached)",
    },
    {"method": "GET", "url": "/matches", "weight": 3, "name": "Matches (DB Intensive)"},
    {
        "method": "GET",
        "url": "/messages/unread-count",
        "weight": 4,
        "name": "Unread Count",
    },
    {
        "method": "GET",
        "url": "/chatrooms/popular",
        "weight": 2,
        "name": "Popular Chatrooms",
    },
    {"method": "GET", "url": "/recommendations/feed", "weight": 3, "name": "Feed"},
    # Slow requests (wildcard search or heavy aggregation if available)
    {
        "method": "GET",
        "url": "/chatrooms/search?q=a",
        "weight": 1,
        "name": "Search (Wildcard/Slow)",
    },
    # Write operations
    {
        "method": "POST",
        "url": "/feedback",
        "data": {"message": "Load test feedback", "category": "bug"},
        "weight": 1,
        "name": "Feedback (Job Trigger)",
    },
]


class LoadTester:
    def __init__(self):
        self.token = None
        self.user_id = None
        self.stats = {"requests": 0, "errors": 0, "latencies": []}

    async def login(self, session):
        """Authenticates the user and retrieves a token."""
        login_url = f"{BASE_URL}/auth/login"
        payload = {
            "email": EMAIL,
            "password": PASSWORD,
            "device_name": "load-test-script",
        }

        try:
            print(
                f"[{datetime.now().strftime('%H:%M:%S')}] Attempting login to {login_url}..."
            )
            async with session.post(login_url, json=payload) as response:
                if response.status == 200:
                    data = await response.json()
                    self.token = data.get("token") or data.get(
                        "access_token"
                    )  # Adapt to actual response structure
                    # Try to get user ID if available
                    if "user" in data:
                        self.user_id = data["user"].get("id")

                    if self.token:
                        print(
                            f"[{datetime.now().strftime('%H:%M:%S')}] Login successful! Token acquired."
                        )
                        return True
                    else:
                        print(
                            f"[{datetime.now().strftime('%H:%M:%S')}] Login failed: No token in response. Response: {data}"
                        )
                        return False
                else:
                    text = await response.text()
                    print(
                        f"[{datetime.now().strftime('%H:%M:%S')}] Login failed with status {response.status}: {text}"
                    )
                    return False
        except Exception as e:
            print(f"[{datetime.now().strftime('%H:%M:%S')}] Login exception: {str(e)}")
            return False

    async def simulate_user(self, session, user_id):
        """Simulates a single user session looping through endpoints."""
        start_time = time.time()

        while DURATION == 0 or (time.time() - start_time) < DURATION:
            # Pick an endpoint based on weight
            endpoint = random.choices(
                ENDPOINTS, weights=[e["weight"] for e in ENDPOINTS], k=1
            )[0]

            full_url = f"{BASE_URL}{endpoint['url']}"
            headers = {
                "Authorization": f"Bearer {self.token}",
                "Accept": "application/json",
                "User-Agent": f"LoadTest/1.0 User-{user_id}",
            }

            req_start = time.time()
            try:
                if endpoint["method"] == "GET":
                    async with session.get(full_url, headers=headers) as response:
                        await response.read()  # Read body to complete request
                        status = response.status
                elif endpoint["method"] == "POST":
                    data = endpoint.get("data", {})
                    # Add timestamps to make data unique-ish
                    if "message" in data:
                        data["message"] = f"Load test message {time.time()}"

                    async with session.post(
                        full_url, json=data, headers=headers
                    ) as response:
                        await response.read()
                        status = response.status

                latency = (time.time() - req_start) * 1000  # ms
                self.stats["requests"] += 1
                self.stats["latencies"].append(latency)

                status_symbol = "✅" if 200 <= status < 300 else "❌"
                # print(f"[{datetime.now().strftime('%H:%M:%S')}] User-{user_id} {endpoint['method']} {endpoint['url']} - {status} {status_symbol} ({latency:.1f}ms)")

                if status >= 400:
                    self.stats["errors"] += 1

            except Exception as e:
                self.stats["errors"] += 1
                print(
                    f"[{datetime.now().strftime('%H:%M:%S')}] Request failed: {str(e)}"
                )

            # Random delay between requests
            delay = random.uniform(*DELAY_RANGE)
            await asyncio.sleep(delay)

    async def run(self):
        print(
            f"Starting load test with {CONCURRENT_USERS} concurrent users for {DURATION} seconds."
        )
        print(f"Target: {BASE_URL}")

        async with aiohttp.ClientSession() as session:
            # 1. Login once to get token (simulating one logged-in user behaving as many,
            # or ideally we'd have multiple accounts, but reusing token is simpler for load gen)
            if not await self.login(session):
                print("Aborting: Could not authenticate.")
                return

            # 2. Launch concurrent user tasks
            tasks = []
            for i in range(CONCURRENT_USERS):
                tasks.append(self.simulate_user(session, i))

            await asyncio.gather(*tasks)

        # 3. Report
        self.report()

    def report(self):
        print("\n--- Load Test Report ---")
        total_reqs = self.stats["requests"]
        if total_reqs == 0:
            print("No requests completed.")
            return

        avg_latency = sum(self.stats["latencies"]) / total_reqs
        max_latency = max(self.stats["latencies"])
        min_latency = min(self.stats["latencies"])
        error_rate = (self.stats["errors"] / total_reqs) * 100

        print(f"Total Requests: {total_reqs}")
        print(f"Error Rate:     {error_rate:.2f}% ({self.stats['errors']} errors)")
        print(f"Avg Latency:    {avg_latency:.2f} ms")
        print(f"Min Latency:    {min_latency:.2f} ms")
        print(f"Max Latency:    {max_latency:.2f} ms")
        print("------------------------")


if __name__ == "__main__":
    tester = LoadTester()
    try:
        asyncio.run(tester.run())
    except KeyboardInterrupt:
        print("\nTest interrupted by user.")
        tester.report()
