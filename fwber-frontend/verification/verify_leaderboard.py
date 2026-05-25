from playwright.sync_api import sync_playwright, expect
import os

def verify_leaderboard():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Mock API responses
        # 1. Mock Auth (User)
        page.route("**/api/auth/me", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body='{"id": 1, "name": "TestUser", "email": "test@example.com", "referral_code": "TEST", "token_balance": 1000}'
        ))

        # Set localStorage for Auth Bypass
        page.add_init_script("""
            localStorage.setItem('auth_token', 'dev');
            localStorage.setItem('fwber_token', 'mock-token');
            localStorage.setItem('fwber_user', JSON.stringify({
                id: 1,
                name: 'Test User',
                email: 'test@example.com',
                onboarding_completed_at: new Date().toISOString()
            }));
        """)

        # 2. Mock Leaderboard Data
        page.route("**/api/leaderboard", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body='''{
                "top_holders": [{"name": "Ali***", "balance": "1000", "joined": "1 day ago"}],
                "top_referrers": [{"name": "Bob***", "referrals": 10}],
                "top_wingmen": [{"name": "Cha***", "assists": 5}],
                "top_vouched": [
                    {"name": "Dav***", "vouches": 50},
                    {"name": "Eve***", "vouches": 30}
                ]
            }'''
        ))

        try:
            print("Navigating to leaderboard...")
            page.goto("http://localhost:3000/leaderboard")

            # Wait for "Most Vouched" text
            print("Waiting for Most Vouched...")
            expect(page.get_by_text("Most Vouched")).to_be_visible(timeout=15000)

            # Wait for data
            expect(page.get_by_text("Dav***")).to_be_visible()
            expect(page.get_by_text("50")).to_be_visible()

            # Take screenshot
            os.makedirs("/home/jules/verification", exist_ok=True)
            page.screenshot(path="/home/jules/verification/leaderboard.png")
            print("Verification successful. Screenshot saved.")

        except Exception as e:
            print(f"Verification failed: {e}")
            # Take screenshot of failure
            os.makedirs("/home/jules/verification", exist_ok=True)
            page.screenshot(path="/home/jules/verification/leaderboard_fail.png")
        finally:
            browser.close()

if __name__ == "__main__":
    verify_leaderboard()
