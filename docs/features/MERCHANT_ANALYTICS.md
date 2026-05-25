# Merchant Analytics Feature

## Overview
The Merchant Analytics system provides local businesses with insights into how their promotions are performing on the fwber platform. It tracks user interactions (views, clicks, redemptions) and calculates key performance indicators (KPIs) such as reach, conversion rate, and estimated revenue.

## Architecture

### Database Schema

#### `promotions` Table (Updated)
- `views` (unsignedBigInteger): Total number of times the promotion was displayed.
- `clicks` (unsignedBigInteger): Total number of times users clicked on the promotion card.
- `redemptions` (unsignedBigInteger): Total number of times users redeemed the promotion.
- `starts_at` (dateTime): Start time of the promotion.
- `expires_at` (dateTime): Expiration time of the promotion.

#### `promotion_events` Table (New)
Tracks granular interaction events for detailed analytics.
- `id`: Primary Key.
- `promotion_id`: Foreign Key to `promotions` table.
- `user_id`: Foreign Key to `users` table (nullable for guest views).
- `type`: Enum (`view`, `click`, `redemption`).
- `metadata`: JSON field for additional data (e.g., source page, device info).
- `created_at`: Timestamp of the event.

### Backend Implementation

#### Models
- **`App\Models\Promotion`**:
  - `hasMany` relationship to `PromotionEvent`.
  - Incremented counters for performance.
- **`App\Models\PromotionEvent`**:
  - Records individual interaction events.
  - Linked to `Promotion` and `User`.

#### Services
- **`App\Services\MerchantAnalyticsService`**:
  - **`getKPIs(MerchantProfile $merchant, string $range)`**:
    - Calculates total revenue from `MerchantPayment` (status: `paid`).
    - Calculates unique reach based on `PromotionEvent` (type: `view`).
    - Calculates conversion rate based on `redemptions` / `reach`.
  - **`getPromotionsPerformance(MerchantProfile $merchant, string $range)`**:
    - Returns list of promotions with their individual stats (views, clicks, redemptions, revenue).

#### API Endpoints
- **`POST /api/promotions/{id}/track`**:
  - Accepts `type` (`view`, `click`, `redemption`) and optional `metadata`.
  - Records event in `promotion_events` table.
  - Increments corresponding counter on `promotions` table.

### Frontend Implementation
- **`fwber-frontend/app/deals/page.tsx`**:
  - Tracks `view` event on component mount (using `useEffect` and `useRef` to prevent duplicates).
  - Tracks `click` event on card click.
  - Tracks `redemption` event on "Redeem" button click.
- **`fwber-frontend/app/merchant/analytics/page.tsx`**:
  - Displays dashboard with charts and tables consuming the analytics service data.

## Future Enhancements
- **Cohort Analysis**: Advanced retention tracking based on repeat visits.
- **Geographic Heatmaps**: Visualize where views and redemptions are happening.
- **A/B Testing**: Allow merchants to run multiple versions of a promotion.
