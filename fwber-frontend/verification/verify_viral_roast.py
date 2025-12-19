import requests
import sys
import json

def verify_roast():
    base_url = "http://127.0.0.1:8000/api"

    # 1. Login
    print("Logging in...")
    try:
        headers = {
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
        resp = requests.post(f"{base_url}/auth/login", json={
            "email": "king@viral.com",
            "password": "password"
        }, headers=headers, timeout=10)

        if resp.status_code != 200:
            print(f"Login failed: {resp.status_code}")
            # print(f"Response: {resp.text[:500]}") # Only print first 500 chars
            sys.exit(1)

        token = resp.json()['access_token']
        headers["Authorization"] = f"Bearer {token}"
        print("Logged in successfully.")
    except Exception as e:
        print(f"Login connection failed: {e}")
        sys.exit(1)

    # 2. Roast
    print("Generating roast...")
    try:
        resp = requests.post(f"{base_url}/wingman/roast", headers=headers, json={"mode": "roast"}, timeout=30)
        if resp.status_code != 200:
             print(f"Roast failed: {resp.text}")
             sys.exit(1)

        data = resp.json()
        share_id = data.get('share_id')
        roast = data.get('roast')
        print(f"Roast generated: {roast[:50]}...")
        print(f"Share ID: {share_id}")

        if not share_id:
            print("No share_id returned!")
            sys.exit(1)

        # 3. Check Public Content
        print("Checking public content...")
        resp = requests.get(f"{base_url}/viral-content/{share_id}", timeout=10)
        if resp.status_code != 200:
            print(f"Public content fetch failed: {resp.text}")
            sys.exit(1)

        public_data = resp.json()
        if public_data['content']['text'] != roast:
             print("Content mismatch!")
             print(f"Expected: {roast}")
             print(f"Got: {public_data['content']['text']}")
             sys.exit(1)

        print("Viral roast verified successfully!")

    except Exception as e:
        print(f"Error during verification: {e}")
        sys.exit(1)

if __name__ == "__main__":
    verify_roast()
