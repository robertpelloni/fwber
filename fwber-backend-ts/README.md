# FWBER TypeScript Backend (v2.0)

This is the high-performance TypeScript port of the original Laravel backend, designed for extreme scalability, real-time engagement, and privacy-focused social interactions.

## 🚀 Stack
- **Runtime**: Node.js (ESM)
- **Framework**: Express.js
- **ORM**: Prisma (PostgreSQL)
- **Real-time**: Socket.io
- **Spatial**: H3-js + Redis Bloom Filter
- **Security**: ZK-Identity Verification (Simulator) + Geo-Spoof Detection

## 🛠️ Key Services
- **Auth & Profiles**: JWT-based auth with Decoy Mode support.
- **TokenDistribution**: Exponential decay signup bonuses and referrals.
- **MatchMaker**: Token-escrowed match bounties and wingman rewards.
- **GeoScreener**: H3 spatial indexing for privacy-preserving nearby matching.
- **GeoSpoof**: Velocity and IP-discrepancy heuristics to prevent GPS spoofing.

## 📦 Setup & Development
1. **Install Dependencies**: `npm install`
2. **Environment**: Copy `.env.example` to `.env` and configure `DATABASE_URL` and `REDIS_URL`.
3. **Database**: `npx prisma migrate dev`
4. **Development**: `npm run dev`
5. **Build**: `npm run build`
6. **Production**: `npm start`

## 🧪 Testing
- **Run Tests**: `npm test`
- **Tooling**: Jest + Supertest (Configured for ESM)

## 🔗 Frontend Integration
Update the Next.js frontend `.env` to point to the new API:
`NEXT_PUBLIC_API_URL=http://localhost:4000/api`
`NEXT_PUBLIC_WS_URL=http://localhost:4000`
