import asyncio
from playwright.async_api import async_playwright
import json

async def run():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            viewport={'width': 1280, 'height': 800}
        )
        page = await context.new_page()

        # Mock authentication
        await page.add_init_script("""
            window.localStorage.setItem('fwber_token', 'fake-token');
            window.localStorage.setItem('fwber_user', JSON.stringify({
                id: 1,
                name: 'Admin',
                is_moderator: true
            }));
        """)

        # Mock monitoring data API
        await page.route("**/api/monitoring/autonomous", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body=json.dumps({
                "is_active": True,
                "current_loop": "Monitoring",
                "last_action_at": "2026-05-24T12:00:00Z",
                "tasks_completed_today": 125,
                "success_rate": 98.5,
                "system_integrity": "Optimal",
                "metrics": {
                    "daily_started": 130,
                    "daily_completed": 125,
                    "daily_failed": 2,
                    "performance": {
                        "User Registration": { "avg_ms": 145.2, "count": 25 },
                        "User Login": { "avg_ms": 85.5, "count": 100 },
                        "Location Indexing": { "avg_ms": 12.3, "count": 1200 },
                        "ActivityPub Broadcast": { "avg_ms": 450.8, "count": 50 }
                    }
                },
                "recent_actions": [
                    { "id": 1, "task": "User Login", "status": "Completed", "timestamp": "2026-05-24T11:55:00Z" },
                    { "id": 2, "task": "Location Indexing", "status": "Completed", "timestamp": "2026-05-24T11:50:00Z" }
                ],
                "automated_adjustments": [
                    { "key": "auto_lint_fix", "label": "Auto-Fix Lint Errors", "enabled": True },
                    { "key": "strict_mode", "label": "Strict Protocol Enforcement", "enabled": False }
                ]
            })
        ))

        # Navigate to monitoring page
        try:
            print("Navigating to http://localhost:3005/admin/monitoring...")
            await page.goto("http://localhost:3005/admin/monitoring", wait_until="domcontentloaded", timeout=60000)
            print("Waiting for 'Latency Breakdown' text...")
            await page.wait_for_selector("text=Latency Breakdown", timeout=20000)
            # Give it a second to settle
            await asyncio.sleep(2)
            await page.screenshot(path="verification/monitoring_dashboard.png")
            print("Screenshot saved to verification/monitoring_dashboard.png")
        except Exception as e:
            print(f"Failed to load page: {e}")
            await page.screenshot(path="verification/failed_load.png")

        await browser.close()

if __name__ == "__main__":
    asyncio.run(run())
