# Affiliate Pin Publisher

Production-ready starter for importing Amazon affiliate inputs and publishing compliant Pinterest drafts.

## Architecture summary
- `client`: React + Vite + TypeScript + Tailwind admin UI.
- `server`: Express + Prisma + BullMQ + Redis API.
- `shared`: shared enums/types.
- `docs`: API docs + compliance notes.
- `samples`: CSV and mock product data.
- `docker`: compose stack.

## Run locally
1. `npm install`
2. Copy `.env.example` to `.env`
3. Set `DATABASE_PROVIDER=sqlite` for local SQLite usage.
4. `npm run prisma:migrate -w server`
5. `npm run seed -w server`
6. `npm run dev`

## Demo mode
Set `DEMO_MODE=true`. Use seeded account:
- email: `demo@example.com`
- password: `password123`

## Pinterest credentials
Create a Pinterest app and set:
- `PINTEREST_CLIENT_ID`
- `PINTEREST_CLIENT_SECRET`
- `PINTEREST_REDIRECT_URI`

## Amazon provider integration
Use `server/src/providers/amazonProvider.ts` and replace `MockAmazonProvider` with an approved Amazon provider integration (Creators API / approved Associates flow).

## Compliance guardrails
- Do not scrape Amazon pages as source-of-truth.
- Only use imported links or approved APIs.
- Keep disclosure enabled unless explicit admin override is required.

## Scheduling
BullMQ queue (`server/src/queue/publishQueue.ts`) handles immediate and delayed publish jobs with status transitions.

## Deploy
Use Docker compose in `docker/docker-compose.yml` or deploy services separately with managed Postgres + Redis.
