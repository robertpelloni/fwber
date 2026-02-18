from playwright.sync_api import sync_playwright, expect
import time
import os

def run():
    # Ensure verification directory exists
    os.makedirs("/home/jules/verification", exist_ok=True)

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # mock API
        page.route("**/api/health", lambda route: route.fulfill(status=200, body='{"status":"ok"}'))
        page.route("**/auth/referral/**", lambda route: route.fulfill(status=404, body='{"valid":false}'))

        print("Navigating to Help Center...")
        try:
            page.goto("http://localhost:3000/help")
            page.wait_for_load_state("networkidle")

            # Verify Merchant Features
            print("Checking Merchant Features...")
            expect(page.get_by_text("Merchant Features")).to_be_visible()
            expect(page.get_by_text("Introduction for Merchants")).to_be_visible()

            # Screenshot Help Center
            page.screenshot(path="/home/jules/verification/help_center.png")
            print("Help Center screenshot saved.")
        except Exception as e:
            print(f"Help Center verification failed: {e}")

        # Navigate to Register
        print("Navigating to Register...")
        try:
            page.goto("http://localhost:3000/register")
            page.wait_for_load_state("networkidle")

            # Find the specific tooltip trigger for Nickname
            # Strategy: Find the label, go to parent, find the HelpCircle SVG inside that parent.
            # Using xpath to get parent of label
            nickname_label = page.get_by_text("Nickname (or first name)")

            # We want the 'svg' that is a sibling of the label (or in the same container)
            # The structure is <div class="flex..."><label>...</label><button>...</button></div>
            # Let's target the SVG with class 'lucide-circle-question-mark' that is near the label.

            # We can select the container first.
            container = page.locator("div.flex.items-center").filter(has=nickname_label).first

            trigger = container.locator("svg.lucide-circle-question-mark").first

            print("Hovering over tooltip...")
            trigger.hover()

            # Wait for tooltip content
            expect(page.get_by_text("This is how you will be seen by others")).to_be_visible()

            time.sleep(0.5) # Allow animation

            page.screenshot(path="/home/jules/verification/register_tooltip.png")
            print("Register Tooltip screenshot saved.")
        except Exception as e:
            print(f"Register verification failed: {e}")
            page.screenshot(path="/home/jules/verification/register_failed.png")

        browser.close()

if __name__ == "__main__":
    run()
