# CDG Wrap Quote — Proof of Concept

A web version of the Front Desk / Wrap Quote module from the client's Excel
workbook. The pricing engine is an isolated, framework-free package; the Laravel
app is a thin layer around it. See `docs/ARCHITECTURE.md` for the design.

## Stack

Laravel + Livewire (Blade, Alpine.js) · PostgreSQL · Pest · plain-PHP engine
package at `packages/pricing`.

## Requirements

- PHP 8.3+
- Composer 2
- PostgreSQL 14+
- Node 18+ (for asset build)

## Setup

```bash
git clone <repo-url> cdg-quote-poc
cd cdg-quote-poc

composer install        # installs the app + the local packages/pricing package
cp .env.example .env
php artisan key:generate

# configure DB in .env (DB_CONNECTION=pgsql, DB_DATABASE, DB_USERNAME, ...)
php artisan migrate --seed   # creates schema and loads workbook data

npm install && npm run build
```

The pricing engine is wired in as a local path package (see the
`repositories` and `require` entries in the root `composer.json`).

## Run locally

```bash
php artisan serve        # http://127.0.0.1:8000
# Front Desk:  /            (build a wrap quote)
# Admin:       /admin       (edit rates, costs, multipliers, add-on prices)
```

## Tests

```bash
# whole suite
./vendor/bin/pest

# engine only (the Excel-parity suite lives here)
cd packages/pricing && ./vendor/bin/pest
```

`ExcelParityTest` verifies the app reproduces the workbook to the cent. Ground
truth lives in `packages/pricing/tests/Fixtures/` and is mapped back to the
workbook in `docs/fixtures-source.md`.

## Docs

- `docs/ARCHITECTURE.md` — design overview (1–3 pages)
- `docs/DECISIONS.md` — business-rule decisions & open questions
- `docs/IMPLEMENTATION_PLAN.md` — phased plan
- `docs/PLAN.md` — run order
