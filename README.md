# Facebook Page Event Automation Tool (PHP + Excel + Google Sheets)

A production-ready starter app that lets you:
- Authenticate with Facebook OAuth.
- Upload an `.xlsx` sheet of events.
- Validate and preview events.
- Bulk-create Facebook Page events with retry and delay controls.
- Track status/errors in **Google Sheets** (no MySQL required).

## 1) Architecture

- **Backend**: PHP 8.1+
- **Frontend**: Bootstrap 5 + PHP templates
- **Excel parser**: PhpSpreadsheet
- **Facebook API**: Graph API (Page events endpoint)
- **Persistent storage**: Google Sheets (events tab + logs tab)

## 2) Google Sheets “Schema” (replacement for MySQL)

Create one spreadsheet and share it with your service-account email.

### Sheet: `events`
Columns `A:R`:
1. event_name
2. event_description
3. start_date
4. start_time
5. end_date
6. end_time
7. event_type
8. venue_name
9. city
10. state
11. country
12. ticket_url
13. category
14. event_image_url
15. status (Pending / Posted / Failed)
16. facebook_event_id
17. error_message
18. created_at

### Sheet: `logs`
Columns `A:C`:
1. timestamp
2. level
3. message

The app auto-creates missing tabs.

## 3) Excel format expected

Use these columns in row 1:
- A: Event Name
- B: Event Description
- C: Start Date (YYYY-MM-DD)
- D: Start Time (HH:MM AM/PM)
- E: End Date
- F: End Time
- G: Event Type (In person / Virtual)
- H: Venue Name
- I: City
- J: State
- K: Country
- L: Ticket URL
- M: Category (Music & Audio)
- N: Event Image URL

Data starts from row 2.

## 4) Project structure

```text
.
├── config/
│   └── bootstrap.php
├── public/
│   └── index.php
├── src/
│   ├── Controllers/
│   │   └── DashboardController.php
│   ├── Services/
│   │   ├── EventValidator.php
│   │   ├── ExcelParser.php
│   │   ├── FacebookService.php
│   │   └── GoogleSheetsRepository.php
│   └── Utils/
│       ├── Csrf.php
│       └── Logger.php
├── storage/logs/
├── templates/
│   └── dashboard.php
├── .env.example
├── composer.json
└── README.md
```

## 5) Setup (step-by-step)

1. Install dependencies:
   ```bash
   composer install
   ```
2. Configure environment:
   ```bash
   cp .env.example .env
   ```
3. Fill `.env`:
   - Facebook app credentials + redirect URI.
   - Google spreadsheet ID.
   - Path to Google service account JSON key.
4. In Facebook App settings:
   - Add OAuth redirect URI: `http://localhost:8000/facebook-callback`
   - Request permissions: `pages_manage_metadata`, `pages_read_engagement`, `pages_manage_events`
5. Run local server:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
6. Open browser: `http://localhost:8000`

## 6) Security notes

- CSRF protection on all POST routes.
- Strict upload extension check (`.xlsx`).
- Output escaping in templates.
- Tokens stay server-side in session.
- Keep `.env` and service-account JSON outside public web root.

## 7) Automation controls

Configured via `.env`:
- `BATCH_SIZE` (default 10)
- `API_DELAY_MS` (default 500ms)
- `MAX_RETRIES` (default 2)

## 8) Optional enhancements

- CSV support (add parser path for `.csv`).
- Scheduled submission using cron + queue worker.
- Multi-page profile storage per admin user.
- Download failed-row report.
- Add row-level inline edit persistence before submit.

## 9) Troubleshooting

### Error: `Failed opening required ... vendor/autoload.php`

This means Composer dependencies are not installed yet. From your project root run:

```bash
composer install
```

If you are on WAMP/XAMPP, make sure the command runs in the same folder that contains `composer.json`.

