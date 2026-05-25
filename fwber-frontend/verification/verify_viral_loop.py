from playwright.sync_api import sync_playwright, expect
import time
import os

def verify_viral_loop():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Log all requests to see what's happening
        page.on("request", lambda request: print(f"Request: {request.url}"))
        page.on("response", lambda response: print(f"Response: {response.url} {response.status}"))
        page.on("console", lambda msg: print(f"Console: {msg.text}"))

        # Mock the referral check API
        # The frontend calls: {NEXT_PUBLIC_API_URL}/auth/referral/{refCode}
        # e.g. http://127.0.0.1:8000/api/auth/referral/VIRALKING
        page.route("**/api/auth/referral/VIRALKING", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body='{"valid": true, "referrer_name": "Viral King", "referrer_avatar": null}'
        ))

        try:
            print("Navigating to viral landing page...")
            # Use 127.0.0.1 just in case localhost is ambiguous
            page.goto("http://localhost:3007/?ref=VIRALKING", timeout=60000)

            # Wait for banner
            print("Checking for referral banner...")
            # We look for the text.
            # Note: The component renders "You've been invited by {referrer.name}!"
            banner = page.get_by_text("You've been invited by Viral King!", exact=False)
            expect(banner).to_be_visible(timeout=30000)

            print("Checking for CTA...")
            cta = page.get_by_role("link", name="Claim Reward")
            expect(cta).to_be_visible()

            # Verify URL param preservation in CTA
            href = cta.get_attribute("href")
            if "ref=VIRALKING" not in href:
                raise Exception(f"CTA link missing ref param: {href}")

            print("Viral landing page verified successfully!")

            # Screenshot
            if not os.path.exists("fwber-frontend/verification"):
                os.makedirs("fwber-frontend/verification")
            page.screenshot(path="fwber-frontend/verification/viral_landing.png")

        except Exception as e:
            print(f"Verification failed: {e}")
            if not os.path.exists("fwber-frontend/verification"):
                os.makedirs("fwber-frontend/verification")
            page.screenshot(path="fwber-frontend/verification/viral_error.png")
            raise e
        finally:
            browser.close()

if __name__ == "__main__":
    verify_viral_loop()
