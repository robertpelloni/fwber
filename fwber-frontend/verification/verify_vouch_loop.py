from playwright.sync_api import sync_playwright, expect
import time
import os

def verify_vouch_loop():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Log requests
        page.on("request", lambda request: print(f"Request: {request.url}"))
        page.on("response", lambda response: print(f"Response: {response.url} {response.status}"))

        try:
            print("Navigating to vouch landing page...")
            # Use 127.0.0.1:3007 or localhost:3007
            page.goto("http://localhost:3007/vouch/VIRALKING", timeout=60000)

            # Wait for content
            print("Checking for vouch header...")
            header = page.get_by_text("Vouch for Viral King")
            expect(header).to_be_visible(timeout=30000)

            print("Checking buttons...")
            safe_btn = page.get_by_text("Trustworthy & Safe")
            expect(safe_btn).to_be_visible()

            print("Clicking 'Trustworthy'...")
            # Verify backend API call
            with page.expect_response("**/public/vouch") as response_info:
                safe_btn.click()

            response = response_info.value
            print(f"Vouch API status: {response.status}")
            if response.status != 200 and response.status != 429:
                raise Exception(f"Vouch API failed with status {response.status}")

            print("Checking for success state...")
            success_msg = page.get_by_text("Vouch Recorded!")
            expect(success_msg).to_be_visible()

            print("Checking for CTA...")
            cta = page.get_by_role("link", name="Claim Reward & Join")
            expect(cta).to_be_visible()

            # Verify URL param preservation
            href = cta.get_attribute("href")
            if "ref=VIRALKING" not in href:
                raise Exception(f"CTA link missing ref param: {href}")

            print("Vouch landing page verified successfully!")

        except Exception as e:
            print(f"Verification failed: {e}")
            if not os.path.exists("fwber-frontend/verification"):
                os.makedirs("fwber-frontend/verification")
            page.screenshot(path="fwber-frontend/verification/vouch_error.png")
            raise e
        finally:
            browser.close()

if __name__ == "__main__":
    verify_vouch_loop()
