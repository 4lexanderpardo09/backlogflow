# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

BacklogFlow: IT projects/backlog/activities tracking and application SLA
management platform for the Sistemas/TI department. Plain PHP MVC monolith
(no framework, zero runtime dependencies) backed by MariaDB.

Two independent-but-shared-layout modules:

- **Projects** (`?r=projects/...`): Developer → Project → Backlog →
  Activities hierarchy. Progress, status and traffic-light are always
  computed from activities — never entered by hand.
- **SLA** (`?r=sla/...`): application inventory, providers, support levels,
  incident SLA matrix, availability, contracts/expirations and monthly
  compliance indicators.

Code, comments and tests are in English; every screen shown to the end user
is in Spanish (see `app/Helpers/Labels.php`).

## Commands

```bash
composer install                        # only dev dependency is phpunit
php -S localhost:8000 -t public         # run the app
vendor/bin/phpunit                      # run all tests
vendor/bin/phpunit --filter TrafficLightTest   # run a single test class
vendor/bin/phpunit tests/Unit/ProgressTest.php # run a single test file
```

First-time DB setup:

```bash
mysql -u root -e "CREATE DATABASE backlogflow CHARACTER SET utf8mb4;"
mysql -u root backlogflow < database/schema.sql
mysql -u root backlogflow < database/catalogs.sql
mysql -u root backlogflow < database/seed.sql   # optional example data
```

Copy `.env.example` to `.env` and set MariaDB credentials before running.

There is no DB-dependent test suite by design (see Testing below) — verify
DB-touching changes manually with `curl` against the running dev server.

## Architecture

- `app/Core` — front controller plumbing: `Router` (resolves routes to
  controller actions), `Controller`/`Model`/`View` bases, `Database` (PDO
  singleton), `Env` (.env loader). No framework.
- `app/Models` — data access only (PDO prepared statements). No business
  rules live here.
- `app/Helpers` — pure, unit-tested business logic: progress math
  (`Progress`), activity auto-status (`ActivityStatus`), traffic lights
  (`TrafficLight`, `SlaCompliance`), contract expiration buckets
  (`ContractAlert`), date math (`DateMath`), Spanish labels (`Labels`),
  server-rendered inline SVG charts (`Charts`), and view UI helpers (`Ui`).
  These are the only classes covered by `phpunit.xml`'s `<source>` include
  and the only thing the test suite exercises.
- `app/Services` (`Projects/`, `Sla/`) — orchestrates Models + Helpers into
  what a screen needs (dashboards, management views). Controllers stay thin
  HTTP glue only; they never build SQL or business rules themselves.
- `app/Controllers`, `app/Views` — one subfolder per module (`Projects`,
  `Sla`), mirrored 1:1.

### Routing

`?r=<module>/<controller>/<action>/<id>` (module/controller/action default
to `projects`/`dashboard`/`index` when segments are missing) is resolved by
`app/Core/Router.php` to
`App\Controllers\<Module>\<Controller>Controller::<action>Action($id, $params)`.
`public/.htaccess` rewrites pretty URLs to the same query string. Adding a
route means adding both the controller action and, if there's a UI, a view
under the matching `app/Views/<module>/...` path — the router does no
auto-discovery beyond class/method name matching.

### Rendering

`View::render()` requires the view file, buffers it, then requires
`app/Views/layout/main.php` around the buffered `$content`. `Controller`
subclasses call `$this->render('projects/foo/bar', $data)`,
`$this->json($data)`, or `$this->redirect('module/controller/action')`.
`$this->flash($type, $message)` stashes a one-time session banner for the
next page load (used after create/edit/delete redirects).

## Key design decisions

- **Progress is never entered manually above the activity level.** Backlog
  and project progress are computed by MariaDB views
  (`vw_backlog_progress`, `vw_project_progress`) so every screen reads the
  same number; `app/Helpers/Progress.php` mirrors the same math in PHP
  purely so it's unit-testable.
- **Project traffic light** (`app/Helpers/TrafficLight.php`) considers
  progress, estimated end date, overdue activities and open
  critical/high-priority activities.
- **SLA compliance semaphore** (`app/Helpers/SlaCompliance.php`) is a
  separate rule set from the project traffic light, reusing the same
  green/yellow/red vocabulary — don't conflate the two or try to unify them.
- **Contract/license/certificate alerts** bucket into expired / <30 /
  30-60 / 60-90 / >90 days (`app/Helpers/ContractAlert.php`), driving both
  the SLA dashboard and the contracts screen.
- **Charts are server-rendered inline SVG** (`app/Helpers/Charts.php`) — no
  client-side charting library or CDN dependency to vendor. New charts
  should follow this pattern rather than pulling in JS chart libs.
- **Missing SLA data** (no provider contract yet, unknown contact, etc.) is
  always shown as "Por definir" (`Labels::NOT_DEFINED`), never invented or
  left blank.

## Testing

`tests/Unit` covers only `app/Helpers` (progress calculation, activity
auto-status, project traffic light, SLA compliance semaphore, contract
expiration buckets, date math) — this is intentional, per `phpunit.xml`'s
`<source>` restriction. Controllers/Models are DB-dependent and are verified
manually against a live database rather than with a DB-dependent test
suite, to keep the automated run fast and dependency-free. When adding
business logic, put it in a new/existing `Helper` so it can be unit tested;
if it inherently needs a DB round-trip, it belongs in a `Model`/`Service`
and should be verified manually (e.g. `curl` the route, check the DB row).
