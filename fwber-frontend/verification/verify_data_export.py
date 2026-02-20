from playwright.sync_api import sync_playwright, expect
import time
import os

def run():
    os.makedirs("/home/jules/verification", exist_ok=True)

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # mock authentication and export
        page.add_init_script("""
            localStorage.setItem('auth-storage', JSON.stringify({
                state: {
                    user: { id: 1, email: 'test@example.com' },
                    token: 'mock-token',
                    isAuthenticated: true
                },
                version: 0
            }));
        """)

        # Mock export endpoint
        page.route("**/profile/export", lambda route: route.fulfill(
            status=200,
            body='{"user": "data"}',
            headers={"Content-Type": "application/json"}
        ))

        print("Navigating to Account Settings...")
        try:
            page.goto("http://localhost:3000/settings/account")
            page.wait_for_load_state("networkidle")

            # Verify Export Button
            export_btn = page.get_by_role("button", name="Download My Data")
            expect(export_btn).to_be_visible()

            # Check Tooltip
            print("Checking tooltip...")
            # Structure: <div><h2>Your Data</h2><Tooltip>...</div>
            # Find the HelpCircle

            # Find the section header "Your Data"
            header = page.get_by_text("Your Data")
            # Get parent container
            container = page.locator("div.flex.items-center.justify-between").filter(has=header).first
            # Find tooltip trigger inside
            trigger = container.locator("svg.cursor-help").first # using class we added in previous steps to other tooltips, hoping this one matches
            # The code has <HelpCircle className="w-5 h-5 text-gray-400 cursor-help" />

            if trigger.count() == 0:
                 # Fallback locator
                 trigger = page.locator("button[data-state='closed']").filter(has=page.locator("svg.lucide-circle-question-mark")).first

            trigger.hover()
            expect(page.get_by_text("Download a JSON file containing")).to_be_visible()

            page.screenshot(path="/home/jules/verification/data_export.png")
            print("Export UI verified.")

        except Exception as e:
            print(f"Verification failed: {e}")
            page.screenshot(path="/home/jules/verification/export_failed.png")

        browser.close()

if __name__ == "__main__":
    run()
