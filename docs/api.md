# API Documentation

## Auth
- POST /api/auth/register
- POST /api/auth/login
- POST /api/auth/logout

## Pinterest
- GET /api/pinterest/oauth/start
- GET /api/pinterest/oauth/callback
- GET /api/pinterest/boards
- POST /api/pinterest/boards

## Imports
- POST /api/imports/upload
- POST /api/imports/paste
- GET /api/imports/:id

## Drafts
- GET /api/drafts
- GET /api/drafts/:id
- PATCH /api/drafts/:id
- POST /api/drafts/bulk-update
- POST /api/drafts/:id/regenerate

## Publishing
- POST /api/publish/now
- POST /api/publish/schedule
- POST /api/publish/retry/:id

## Analytics / Compliance / Settings
- GET /api/analytics/overview
- GET /api/analytics/pins
- GET /api/compliance/report
- GET /api/settings
- PATCH /api/settings
