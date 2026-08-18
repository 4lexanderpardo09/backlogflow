# BacklogFlow

IT projects/backlog/activities tracking and application SLA management
platform for the Sistemas/TI department. Plain PHP MVC monolith (no
framework) backed by MariaDB, with two independent-but-shared-layout
modules:

- **Projects** (`/index.php?r=projects/...`): Developer → Project → Backlog →
  Activities hierarchy, with progress, status and traffic-light always
  computed from activities — never entered by hand.
- **SLA** (`/index.php?r=sla/...`): application inventory, providers, support
  levels, incident SLA matrix, availability, contracts/expirations and
  monthly compliance indicators.

Code, comments and tests are in English; every screen shown to the end user
is in Spanish (see `app/Helpers/Labels.php`).

## Architecture

- `app/Core` — front controller plumbing (Router, base Controller/Model,
  View, Database, .env loader). No framework.
- `app/Models` — data access only (PDO prepared statements). No business
  rules.
- `app/Helpers` — pure, unit-tested business logic (progress math, activity
  status, project/SLA traffic lights, contract expiration buckets, date
  math, Spanish labels, server-rendered SVG charts).
- `app/Services` — orchestrates Models + Helpers into what a screen needs
  (dashboards, management views). Controllers stay thin and only do HTTP
  glue; they never build SQL or business rules themselves.
- `app/Controllers`, `app/Views` — one subfolder per module (`Projects`,
  `Sla`).

Routing: `?r=<module>/<controller>/<action>[/<id>]`, resolved by
`app/Core/Router.php` to `App\Controllers\<Module>\<Controller>Controller::<action>Action()`.
`public/.htaccess` rewrites pretty URLs to the same query string.

## Setup

1. Copy `.env.example` to `.env` and set your MariaDB credentials.
2. Create the database and import schema + reference catalogs + example data:
   ```bash
   mysql -u root -e "CREATE DATABASE backlogflow CHARACTER SET utf8mb4;"
   mysql -u root backlogflow < database/schema.sql
   mysql -u root backlogflow < database/catalogs.sql
   mysql -u root backlogflow < database/seed.sql   # optional example data
   ```
3. Install dev dependencies (PHPUnit only — the app itself has zero runtime
   dependencies):
   ```bash
   composer install
   ```
4. Run it:
   ```bash
   php -S localhost:8000 -t public
   ```
   Visit `http://localhost:8000/index.php?r=projects/dashboard/index`.

## Tests

```bash
vendor/bin/phpunit
```

Covers the pure business logic in `app/Helpers`: progress calculation
(simple + weighted average), activity auto-status, project traffic light,
SLA compliance semaphore, contract expiration alert buckets, and date math.
Controllers/Models are exercised manually against a live database (see
"Verification" below) rather than with a DB-dependent test suite, to keep
the test run fast and dependency-free.

## Key design decisions

- **Progress is never entered manually above the activity level.** Backlog
  and project progress are computed by MariaDB views
  (`vw_backlog_progress`, `vw_project_progress`) so every screen reads the
  same number. `app/Helpers/Progress.php` mirrors the same math in PHP for
  unit testing.
- **Project traffic light** (`app/Helpers/TrafficLight.php`) considers
  progress, estimated end date, overdue activities and open
  critical/high-priority activities.
- **SLA compliance semaphore** (`app/Helpers/SlaCompliance.php`) is a
  separate set of rules from the project traffic light, reusing the same
  green/yellow/red vocabulary.
- **Contract/license/certificate alerts** bucket into expired / <30 /
  30-60 / 60-90 / >90 days (`app/Helpers/ContractAlert.php`), driving both
  the SLA dashboard and the contracts screen.
- **Charts are server-rendered inline SVG** (`app/Helpers/Charts.php`) —
  no client-side charting library or CDN dependency to vendor.
- **Missing SLA data** (no provider contract yet, unknown contact, etc.) is
  shown as "Por definir", never invented (`Labels::NOT_DEFINED`).

## Verification performed

- `database/schema.sql` + `catalogs.sql` + `seed.sql` import cleanly on a
  throwaway MySQL/MariaDB instance.
- Every route in both modules was smoke-tested (`curl`) for HTTP 200 and no
  PHP fatal errors/warnings.
- Edited an activity's progress via `POST projects/activities/edit/{id}`
  and confirmed the change propagated automatically: activity → backlog
  (`vw_backlog_progress`) → project (`vw_project_progress`), with no other
  table touched.
- Created records via `POST` in both modules (developer, contract
  expiration) and confirmed they persisted correctly.
- `vendor/bin/phpunit`: 34 tests / 42 assertions, all green.
