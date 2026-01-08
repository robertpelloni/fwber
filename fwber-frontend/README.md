# Frontend Documentation

> **⚠️ MASTER PROTOCOL:** The Single Source of Truth for all operations is `../docs/UNIVERSAL_LLM_INSTRUCTIONS.md`.

## 🏗️ Architecture
This is a Next.js 14 application using the App Router, React 18, and TypeScript. It serves as the primary user interface for fwber.

### Key Components
- **UI:** Shadcn/UI + Tailwind CSS.
- **State:** Zustand (global), React Query (async), URL-based (shareable).
- **Realtime:** Laravel Reverb + Echo integration.
- **Maps:** Leaflet/React-Leaflet for geospatial features.

## 🚀 Getting Started

### Prerequisites
- Node.js 20+
- NPM 10+

### Installation
```bash
npm install
cp .env.example .env.local
npm run dev
```

### Commands
- `npm run dev` - Start development server
- `npm run build` - Production build
- `npm run lint` - Linting
- `npm run type-check` - TypeScript validation

## 🧪 Testing
We use Cypress for End-to-End testing.
```bash
npm run test:e2e
```
See `package.json` for specific test suites (e.g., `test:e2e:matching`).

## 📚 Agent Instructions
See `AGENTS.md` in this directory for specific guidelines on working with the frontend code.
