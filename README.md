# babifit API

Laravel 13 backend implementing the babifit API spec (`API_SPEC.md`). It powers
the Flutter app's Today, Scans (InBody), Meals, Activities and Progress screens,
replacing the app's seeded `lib/data.dart`.

- **Base URL:** `https://babifit.xpertbot.online/v1` (local dev: `http://127.0.0.1:8000/v1`)
- **Auth:** `Authorization: Bearer <accessToken>` on every route except `/v1/auth/*`
- **Database:** MySQL (`babifit`)
- **PHP:** 8.4 · **Laravel:** 13 · **Sanctum:** 4

## Setup

```bash
composer install
cp .env.example .env        # configured for MySQL `babifit`
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link    # serves uploaded scan/dish photos
php artisan serve           # http://127.0.0.1:8000
```

`.env` DB block (adjust to your MySQL):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=babifit
DB_USERNAME=root
DB_PASSWORD="..."
```

### Demo account

The seeder reproduces every example payload in the spec ("today" = `2026-05-26`).

| | |
|---|---|
| Email | `demo@babifit.app` |
| Password | `password` |

```bash
# log in, then call a protected route
TOKEN=$(curl -s -X POST https://babifit.xpertbot.online/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@babifit.app","password":"password"}' | jq -r .accessToken)

curl -s https://babifit.xpertbot.online/v1/today -H "Authorization: Bearer $TOKEN" | jq
```

## Admin panel

A [Filament](https://filamentphp.com) admin panel ships at **`/admin`** for
browsing and editing every module. Sign in with the same credentials as the
demo account (`demo@babifit.app` / `password`).

```bash
php artisan serve            # then open http://127.0.0.1:8000/admin
```

- **Dashboard** — stat cards (members, meals, activities, scans) plus a
  14-day calories-consumed-vs-burned chart.
- **Members** — Users (with avatar, streak, and inline Scans / Meals /
  Activities relation managers) and their Targets.
- **Tracking** — Scans (grouped composition + detail metrics, JSON
  `ranges`/`segments`/`nutrition` editors), Meals (with dish-photo thumbnails)
  and Activities.
- **Media & Jobs** — Photos (image previews + upload) and Scan parse jobs
  (status badges, a pending-count nav badge).

Access is controlled by `User::canAccessPanel()` in
[`app/Models/User.php`](app/Models/User.php), which currently allows **any**
authenticated user — lock this down before production (e.g. an email allowlist
or an `is_admin` column).

## Auth model

Login issues two Sanctum tokens distinguished by ability:

- **access token** — ability `access`, expires in 1h (`expiresIn` seconds). Used as the Bearer on every API route (enforced by `ability:access`).
- **refresh token** — ability `refresh`, expires in 30 days. Only valid at `POST /v1/auth/refresh`, which returns a fresh access token.

`POST /v1/auth/logout` revokes the access token used for the request.

## Architecture

```
app/Http/Controllers/Api/   one controller per spec area (Auth, Profile, Today,
                            Scan, Meal, Activity, Progress)
app/Http/Resources/         JSON shaping to match the Dart models
app/Services/               domain logic:
  TokenService                  access/refresh token issuance
  TodayService                  the /today dashboard aggregate
  ProgressService               series / compare / insights
  ActivityRecommendationService burn-target suggestions
  CalorieEstimator              MET-based kcal estimates
  ScanParserService             OCR stub (upload -> parse job -> draft)
  FoodRecognitionService        food-vision stub (snap a dish)
app/Models/                 User, UserTarget, Scan, Meal, Activity, Photo,
                            ScanParseJob
app/Filament/Resources/     admin panel: one resource per model (form/table/
                            infolist) + User relation managers
app/Filament/Widgets/       dashboard stat cards + calories chart
app/Providers/Filament/     AdminPanelProvider (branding, colors, nav groups)
```

- **IDs:** scans/meals/activities/photos/jobs use string primary keys. Seed rows keep the spec's friendly ids (`scan-1`, `m1`, `a1`); new rows get a prefixed ULID (`scan_…`, `meal_…`).
- **Errors:** every API error returns `{ "error": { "code", "message", "details" } }`.
- **Lists:** `{ "data": [...], "nextCursor": "…|null" }`. Single objects are returned bare (no `data` wrapper).
- **Deltas / previousScanId** are computed from the chronologically previous scan, not stored.

## AI / external dependencies

The two AI-backed endpoints ship as deterministic **stubs** so the full flows are
testable without external services:

- `POST /scans/upload` -> `GET /scans/parse/{jobId}` — stores the file, then
  synthesises a draft from the user's latest scan (dated +8 days). Swap
  `ScanParserService` for a real OCR/LLM pipeline in production.
- `POST /meals/recognize` — returns a detection from a small catalog keyed off
  the file hash. Swap `FoodRecognitionService` for a food-vision model + nutrition DB.

Uploaded files are stored on the `public` disk (`storage/app/public`, served via
the `storage` symlink). Point `FILESYSTEM_DISK` at S3 for production signed URLs.

## Notes / deviations from the spec

- `POST /meals/recognize` additionally returns `photoId` so the client can attach the stored dish photo when confirming via `POST /meals`.
- `GET /activities` with no `week` param returns a rolling 7-day window ending today (what the week chart shows by default); pass `?week=YYYY-Www` for a strict ISO Mon–Sun week.
- Activity recommendation `mins` are derived from MET rates and the latest scan weight, so they differ slightly from the illustrative numbers in the spec but stay self-consistent with each item's `estKcal`.
