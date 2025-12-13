# Code Efficiency Analysis Report

**Date:** December 7, 2025  
**Repository:** robertpelloni/fwber  
**Analyzed by:** Devin (Cognition AI)

## Executive Summary

This report identifies several efficiency improvements that could be made to the fwber codebase. The issues range from N+1 query problems to code duplication and suboptimal caching patterns.

## Findings

### 1. N+1 Query Problem in AIMatchingService.analyzeUserBehavior() [HIGH PRIORITY]

**Location:** `fwber-backend/app/Services/AIMatchingService.php` (lines 56-103)

**Issue:** The method fetches user interactions, then for each interaction, it makes a separate database query to load the target user with their profile:

```php
foreach ($interactions as $interaction) {
    $targetUser = User::with('profile')->find($interaction->target_user_id);
    // ...
}
```

**Impact:** If a user has 100 interactions, this creates 100+ additional database queries instead of 1 batch query.

**Recommended Fix:** Batch load all target users at once using `whereIn()`:

```php
$targetUserIds = $interactions->pluck('target_user_id')->unique();
$targetUsers = User::with('profile')->whereIn('id', $targetUserIds)->get()->keyBy('id');

foreach ($interactions as $interaction) {
    $targetUser = $targetUsers->get($interaction->target_user_id);
    // ...
}
```

---

### 2. N+1 Query Problem in MatchController.establishedMatches() [HIGH PRIORITY]

**Location:** `fwber-backend/app/Http/Controllers/MatchController.php` (lines 184-230)

**Issue:** Inside the `map()` callback, for each matched user, the code queries for the last message:

```php
return $users->map(function ($otherUser) use ($user, $matches) {
    // ...
    $lastMessage = \App\Models\Message::where(function ($q) use ($user, $otherUser) {
        $q->where('sender_id', $user->id)->where('receiver_id', $otherUser->id);
    })->orWhere(function ($q) use ($user, $otherUser) {
        $q->where('sender_id', $otherUser->id)->where('receiver_id', $user->id);
    })->latest()->first();
    // ...
});
```

**Impact:** For N matches, this creates N additional database queries.

**Recommended Fix:** Use a single query with a subquery to get the latest message for each conversation, or use eager loading with a custom relationship.

---

### 3. Duplicate Distance Calculation Functions [MEDIUM PRIORITY]

**Locations:**
- `fwber-backend/app/Http/Controllers/MatchController.php` (lines 323-342)
- `fwber-backend/app/Services/CachingService.php` (lines 283-302)
- `fwber-backend/app/Services/AIMatchingService.php` (line 455+)
- `fwber-frontend/lib/matchingAlgorithm.ts` (lines 54-73)

**Issue:** The Haversine distance calculation is implemented in at least 4 different places with nearly identical code.

**Impact:** Code duplication leads to maintenance burden and potential inconsistencies if one implementation is updated but others are not.

**Recommended Fix:** Extract to a shared utility trait (PHP) or utility function (TypeScript):

```php
// app/Support/GeoUtils.php
trait GeoUtils
{
    public function calculateDistanceInMiles(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        return $dist * 60 * 1.1515;
    }
}
```

---

### 4. Inefficient Cache Key Pattern in CachingService.invalidateUserCache() [MEDIUM PRIORITY]

**Location:** `fwber-backend/app/Services/CachingService.php` (lines 151-174)

**Issue:** Uses `$this->redis->keys($pattern)` which is O(N) and blocks Redis:

```php
$keys = $this->redis->keys($pattern);
```

**Impact:** The Redis `KEYS` command scans the entire keyspace and blocks the server. Redis documentation explicitly warns: "Don't use KEYS in your regular application code."

**Recommended Fix:** Use `SCAN` instead of `KEYS`:

```php
$cursor = 0;
do {
    [$cursor, $keys] = $this->redis->scan($cursor, ['match' => $pattern, 'count' => 100]);
    if (!empty($keys)) {
        $this->redis->del($keys);
    }
} while ($cursor != 0);
```

---

### 5. Redundant Dual Cache Storage [LOW PRIORITY]

**Location:** `fwber-backend/app/Services/CachingService.php` (lines 19-28)

**Issue:** The `cacheUserProfile()` method stores data in both Laravel Cache AND Redis separately:

```php
Cache::put($key, $profileData, $ttl);
$this->redis->setex($key, $ttl, json_encode($profileData));
```

**Impact:** If Laravel's cache driver is already configured to use Redis (which is common), this creates redundant storage and potential consistency issues.

**Recommended Fix:** Use only one caching mechanism. If Laravel Cache is configured with Redis driver, the direct Redis call is unnecessary.

---

## Implementation Plan

For this PR, I will fix **Issue #1: N+1 Query Problem in AIMatchingService.analyzeUserBehavior()** as it has the highest impact on database performance and is a straightforward fix.

## Estimated Impact

- **Issue #1 Fix:** Reduces database queries from O(N) to O(1) for the behavioral analysis, where N is the number of user interactions. For a user with 100 interactions, this reduces queries from ~100 to ~2.
