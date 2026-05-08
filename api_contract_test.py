import subprocess, json, sys

def api(method, path, token="", data=None):
    cmd = ["curl", "-s", "-X", method, f"https://api.fwber.me{path}"]
    if token:
        cmd += ["-H", f"Authorization: Bearer {token}"]
    cmd += ["-H", "Content-Type: application/json"]
    if data:
        cmd += ["-d", json.dumps(data)]
    r = subprocess.run(cmd, capture_output=True, text=True, timeout=15)
    try:
        return json.loads(r.stdout)
    except:
        return {"_raw": r.stdout[:200]}

def has_data(d, *keys):
    """Check if response has data under any of the given keys, or is itself a list"""
    if isinstance(d, list):
        return True
    if isinstance(d, dict):
        for k in keys:
            v = d.get(k)
            if isinstance(v, list) and len(v) > 0:
                return True
            if isinstance(v, dict):
                return True
            if v is not None and v != 0 and v != "" and v is not False:
                return True
    return False

login = api("POST", "/api/auth/login", "", {"email": "apitest_fwber_123@example.com", "password": "TestPass123!"})
token = login.get("access_token", "")
if not token:
    print("LOGIN FAILED"); sys.exit(1)

print("=" * 70)
print("  FRONTEND-BACKEND API CONTRACT VERIFICATION")
print("=" * 70)

endpoints = [
    ("GET", "/api/profile", "Profile", ["profile", "id"]),
    ("GET", "/api/profile/completeness", "Profile Completeness", ["percentage", "score"]),
    ("GET", "/api/matches?limit=3", "Matches", ["matches", "data"]),
    ("GET", "/api/matches/established", "Matches Established", ["data", "matches"]),
    ("GET", "/api/matches/history", "Matches History", ["data", "matches"]),
    ("GET", "/api/friends", "Friends", ["data"]),
    ("GET", "/api/friends/requests", "Friend Requests", ["data"]),
    ("GET", "/api/wallet/balance", "Wallet Balance", ["balance"]),
    ("GET", "/api/wallet/transactions", "Wallet Transactions", ["transactions", "data"]),
    ("GET", "/api/achievements", "Achievements", ["achievements"]),
    ("GET", "/api/topics", "Topics", ["topics", "data"]),
    ("GET", "/api/events", "Events", ["events", "data"]),
    ("GET", "/api/venues", "Venues", ["venues", "data"]),
    ("GET", "/api/chatrooms/popular", "Chatrooms Popular", ["chatrooms", "data"]),
    ("GET", "/api/bulletin-boards", "Bulletin Boards", ["boards", "data"]),
    ("GET", "/api/leaderboard", "Leaderboard", ["leaderboard", "data"]),
    ("GET", "/api/gifts/available", "Gifts Available", ["gifts", "data"]),
    ("GET", "/api/boosts", "Boosts", ["boosts", "data"]),
    ("GET", "/api/boosts/active", "Boosts Active", []),
    ("GET", "/api/boosts/history", "Boosts History", ["data", "history"]),
    ("GET", "/api/recommendations?limit=3", "Recommendations", ["recommendations", "data"]),
    ("GET", "/api/recommendations/trending", "Trending", ["recommendations", "data"]),
    ("GET", "/api/recommendations/feed", "Rec Feed", ["items", "feed", "recommendations"]),
    ("GET", "/api/premium/status", "Premium Status", ["plan", "status"]),
    ("GET", "/api/premium/who-likes-you", "Who Likes You", ["users", "data"]),
    ("GET", "/api/referrals/summary", "Referrals", ["referral_code", "code"]),
    ("GET", "/api/notifications?limit=5", "Notifications", []),
    ("GET", "/api/notification-preferences", "Notif Prefs", ["preferences"]),
    ("GET", "/api/groups", "Groups", ["data", "groups"]),
    ("GET", "/api/groups/2", "Group Detail", ["name", "id"]),
    ("GET", "/api/groups/2/posts", "Group Posts", ["posts", "data"]),
    ("GET", "/api/groups/2/matches", "Group Matches", ["matches", "data"]),
    ("GET", "/api/groups/2/events", "Group Events", ["events", "data"]),
    ("GET", "/api/journals", "Journals", ["data", "journals"]),
    ("GET", "/api/scrapbook", "Scrapbook", ["entries", "data"]),
    ("GET", "/api/safety/contacts", "Safety Contacts", ["contacts", "data"]),
    ("GET", "/api/blocks", "Blocks", ["blocked", "data"]),
    ("GET", "/api/proximity/local-pulse?lat=42.33&lng=-83.05", "Local Pulse", ["artifacts", "venues"]),
    ("GET", "/api/marketplace/nearby?lat=42.33&lng=-83.05", "Marketplace", ["items", "data"]),
    ("GET", "/api/users/search?q=test", "User Search", ["users", "data"]),
    ("GET", "/api/onboarding/status", "Onboarding", ["completed", "status"]),
    ("GET", "/api/dashboard/stats", "Dashboard Stats", ["total_matches", "matches"]),
    ("GET", "/api/config/features", "Config Features", ["features"]),
    ("GET", "/api/config/health", "Config Health", ["status"]),
    ("GET", "/api/health/metrics", "Health Metrics", ["status", "database"]),
    ("GET", "/api/analytics", "Analytics", ["matches", "messages"]),
    ("GET", "/api/analytics/retention", "Retention", ["total_users"]),
    ("GET", "/api/analytics/realtime", "Realtime", ["online_now"]),
    ("GET", "/api/analytics/boosts", "Analytics Boosts", ["total_boosts"]),
    ("GET", "/api/analytics/moderation", "Analytics Moderation", ["total_reports"]),
    ("GET", "/api/burner-links", "Burner Links", []),
    ("GET", "/api/feedback", "Feedback", []),
    ("GET", "/api/content-generation/stats", "Content Gen Stats", []),
    ("GET", "/api/relationship-links", "Relationship Links", []),
    ("GET", "/api/security/keys", "Security Keys", []),
    ("GET", "/api/moderation/dashboard", "Moderation Dashboard", []),
    ("GET", "/api/wingman/roast", "Wingman Roast", []),
    ("GET", "/api/matches/insights/available", "Match Insights Avail", ["data"]),
    ("GET", "/api/matches/insights/unlocked", "Match Insights Unlock", ["data"]),
    ("GET", "/api/matches/accepted", "Matches Accepted", ["data", "matches"]),
    ("GET", "/api/federation/actors/detail?uri=test", "Federation Actors", []),
    ("GET", "/api/federation/posts?actor=test", "Federation Posts", []),
]

ok = warn = fail = 0
for method, path, desc, keys in endpoints:
    d = api(method, path, token)
    
    if isinstance(d, dict) and d.get("error") and not d.get("success"):
        print(f"  FAIL  {desc:25s} error: {str(d.get('error',''))[:40]}")
        fail += 1
    elif isinstance(d, dict) and d.get("_raw"):
        print(f"  FAIL  {desc:25s} no JSON")
        fail += 1
    elif keys and not has_data(d, *keys):
        # Check if it's a bare list
        if isinstance(d, list):
            print(f"  OK    {desc:25s} ({len(d)} items as list)")
            ok += 1
        else:
            print(f"  WARN  {desc:25s} missing expected keys: {str(d)[:60]}")
            warn += 1
    else:
        if isinstance(d, list):
            print(f"  OK    {desc:25s} ({len(d)} items)")
        elif isinstance(d, dict):
            sample_keys = list(d.keys())[:3]
            print(f"  OK    {desc:25s} keys={sample_keys}")
        else:
            print(f"  OK    {desc:25s}")
        ok += 1

print(f"\n{'=' * 70}")
print(f"  Results: {ok} OK, {warn} WARN, {fail} FAIL  ({ok+warn+fail} total)")
print(f"{'=' * 70}")
