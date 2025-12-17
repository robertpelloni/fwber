from playwright.sync_api import sync_playwright
import time

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    print("Navigating to wallet page...")
    # Wait for server to start (simple retry)
    for i in range(60):
        try:
            page.goto("http://localhost:3000/wallet")
            break
        except Exception as e:
            time.sleep(1)
            if i % 5 == 0:
                print(f"Waiting for server... {i} {e}")

    # Check for Wallet Dashboard text
    try:
        page.wait_for_selector("text=Wallet & Crypto", timeout=60000)
        print("Found Wallet Dashboard!")
    except:
        print("Timeout waiting for selector. Taking screenshot anyway.")

    page.screenshot(path="verification/verification.png")
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
