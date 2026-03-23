# fwber Submodule & Architecture Dashboard

> **Last Updated:** 2026-03-23
> **Version:** 0.5.1-beta

This document outlines the directory structure, modular components, and key dependencies of the fwber ecosystem. 

*Note: While fwber operates as a monorepo, it does not currently use git submodules (`.gitmodules`). Instead, it uses a modular directory structure.*

## 🏗️ Project Structure

```text
fwber/
├── fwber-backend/      # Core API and business logic (Laravel 12.x)
├── fwber-frontend/     # User-facing web application (Next.js 16)
├── fwber-geo/          # High-performance spatial indexing service (Rust/Actix)
├── mobile/             # Native mobile wrapper (Expo/React Native)
├── kubernetes/         # Infrastructure manifests for production
└── docs/               # Project documentation and marketing assets
```

## 📦 Component Details & Dependencies

### 1. fwber-backend (Laravel API)
- **Role:** Handles authentication, database interactions, core dating logic, and real-time event broadcasting.
- **Language/Framework:** PHP 8.3 / Laravel 12.0
- **Key Packages (`composer.json`):**
  - `laravel/reverb` (^1.6): Real-time WebSocket broadcasting.
  - `openai-php/laravel` (^0.18.0): AI Avatar generation and Wingman features.
  - `aws/aws-sdk-php` (^3.364): S3 storage and Rekognition image moderation.
  - `stripe/stripe-php` (^19.0): Payment processing for premium tiers and tokens.
  - `pragmarx/google2fa` (^9.0): TOTP Two-Factor Authentication.

### 2. fwber-frontend (Next.js PWA)
- **Role:** The primary user interface for discovering matches, chatting, and managing profiles.
- **Language/Framework:** TypeScript / Next.js 16.1.1 (React 18.3)
- **Key Packages (`package.json`):**
  - `@vladmandic/face-api` (^1.7.15): Client-side face detection and blurring for privacy.
  - `laravel-echo` (^2.2.7) & `pusher-js` (^8.4.0): Real-time WebSocket client for Reverb.
  - `@solana/web3.js` & `@solana/wallet-adapter-*`: Web3 wallet integration for token interactions.
  - `framer-motion` (^12.23.26): Fluid UI animations and transitions.
  - `leaflet` / `react-leaflet`: Interactive maps for the Local Pulse and events.

### 3. fwber-geo (Rust Spatial Screener)
- **Role:** High-performance microservice for O(1) spatial lookups using the H3 grid system. Offloads heavy geographic calculations from the SQL database.
- **Language/Framework:** Rust 2024 / Actix-Web
- **Key Packages (`Cargo.toml`):**
  - `actix-web` (4.13.0): High-performance HTTP server.
  - `h3o` (0.9.4): Uber's H3 geospatial indexing system.
  - `dashmap` (6.1.0): Blazing fast concurrent hash map for holding user coordinates in memory.

### 4. mobile (Expo App)
- **Role:** Native shell for iOS and Android, wrapping the Next.js PWA while handling native permissions (location, push notifications).
- **Language/Framework:** React Native / Expo
- **Key Packages (`package.json`):**
  - `react-native-webview`: Renders the Next.js frontend.
  - `expo-location`: Native GPS access.
  - `expo-notifications`: APNs/FCM push notification handling.

## 🔄 Integration Map
- **Frontend ↔ Backend:** REST API (`/api/*`) authenticated via Laravel Sanctum (cookie-based for web, token-based for mobile).
- **Frontend ↔ Real-time:** WebSocket connection to Laravel Reverb (port 8080 default).
- **Backend ↔ Geo-Screener:** Internal HTTP POST/GET requests to port 8081 for fast proximity lookups.
- **Backend ↔ External:** AWS S3 (Storage), AWS Rekognition (Moderation), OpenAI (Avatars), Stripe (Payments).