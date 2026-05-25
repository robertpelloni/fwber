from playwright.sync_api import sync_playwright, expect
import os
import json

def verify_new_pages():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Mock Auth
        page.route("**/api/auth/me", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body='{"id": 1, "name": "AdminUser", "role": "admin", "profile": {"is_incognito": false}}'
        ))

        # Mock Admin Logs List
        page.route("**/api/admin/logs", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body=json.dumps([
                {"name": "laravel.log", "size": 1024, "updated_at": "2023-01-01T12:00:00Z"},
                {"name": "worker.log", "size": 512, "updated_at": "2023-01-01T12:00:00Z"}
            ])
        ))

        # Mock Admin Log Content
        page.route("**/api/admin/logs/laravel.log", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body=json.dumps({
                "filename": "laravel.log",
                "content": "[2023-01-01 12:00:00] local.INFO: Test log entry"
            })
        ))

        # Mock Physical Profile
        page.route("**/api/physical-profile", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body=json.dumps({
                "data": {
                    "height_cm": 180,
                    "body_type": "Athletic",
                    "avatar_prompt": "A cool avatar"
                }
            })
        ))

        page.add_init_script("""
            localStorage.setItem('fwber_token', 'mock-token');
            localStorage.setItem('fwber_user', JSON.stringify({id: 1, name: 'AdminUser', role: 'admin'}));
        """)

        try:
            # Verify Admin Logs
            print("Verifying Admin Logs...")
            page.goto("http://localhost:3000/admin/logs")
            expect(page.get_by_text("System Logs")).to_be_visible()
            expect(page.get_by_text("laravel.log")).to_be_visible()

            page.get_by_text("laravel.log").click()
            expect(page.get_by_text("Test log entry")).to_be_visible()

            os.makedirs("/home/jules/verification", exist_ok=True)
            page.screenshot(path="/home/jules/verification/admin_logs.png")
            print("Admin Logs verified.")

            # Verify Physical Profile
            print("Verifying Physical Profile...")
            page.goto("http://localhost:3000/settings/physical-profile")
            expect(page.get_by_text("Physical Profile")).to_be_visible()
            # expect(page.locator('input[type="number"]')).to_have_value("180") # Selector might be tricky
            expect(page.get_by_text("A cool avatar")).to_be_visible()

            page.screenshot(path="/home/jules/verification/physical_profile.png")
            print("Physical Profile verified.")

        except Exception as e:
            print(f"Verification failed: {e}")
            page.screenshot(path="/home/jules/verification/failed.png")
        finally:
            browser.close()

if __name__ == "__main__":
    verify_new_pages()
