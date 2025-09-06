# Smart Ticket Triage (Laravel 11 + Vue 3)

A small, production‑ready helpdesk sample: create tickets, edit them, classify with AI (queued), see stats, and export filtered CSV — all with a clean Service/Repository architecture and a Vue SPA.

---

## Features

* **Tickets CRUD**: create, list (filters + pagination), show, update (status, category, note)
* **AI Classification**: `POST /tickets/{id}/classify` enqueues a job using OpenAI (or a safe fallback)
* **Stats API + Dashboard**: counts by status & category with a bar chart
* **CSV Export at Scale**: filter → enqueue export → poll → download CSV (memory‑safe)
* **Good UX**: per‑page selector, skeleton loaders, simple and fast
* **Clean Code**: Service + Repository + DTO; small controllers; strong validation

---

## Tech Stack

* **Backend**: Laravel 11 (PHP 8.2+), Eloquent, Queues (database), RateLimiter, Resources
* **AI SDK**: `openai-php/laravel` (configurable / can be disabled)
* **Frontend**: Vue 3 (Options API), Vite, Axios, Chart.js, BEM CSS
* **DB**: any Laravel‑supported DB (SQLite/MySQL/PostgreSQL). Tests use SQLite in‑memory

---

## Requirements

* PHP 8.2+
* Composer
* Node 20+ & npm
* Database (SQLite/MySQL/PostgreSQL). SQLite is enough for local

---

## Quick Start (10 steps)

1. **Clone & enter**

```bash
git clone https://github.com/achrafbouhadou/ai-ticketing-system.git smart-ticket-triage
cd ai-ticketing-system
```

2. **Install deps**

```bash
composer install
npm install
```

3. **Env & key**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Set DB in `.env`** 

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Create the file:

```bash
mkdir -p database && touch database/database.sqlite
```

5. **OpenAI (optional)**

```env
OPENAI_API_KEY=your-key-or-empty
OPENAI_CLASSIFY_ENABLED=true
OPENAI_MODEL=gpt-4o-mini
```

6. **Queues & rate limit** (already set to database queue; limiter is registered in `AppServiceProvider@boot`)

```env
QUEUE_CONNECTION=database
CLASSIFY_RATE_PER_MINUTE=10
```

7. **Migrate & seed**

```bash
php artisan migrate --seed
```

8. **Publish OpenAI config**

```bash
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"
```

9. **Run dev**

```bash
npm run dev
php artisan serve
php artisan queue:work   # separate terminal (for classify & exports)
```

10. **Build for prod**

```bash
npm run build
php artisan optimize
```

Serve `public/` via your web server.

---

## Environment Variables

```env
APP_ENV=local
APP_KEY=base64:...

# DB (choose one)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Queues
QUEUE_CONNECTION=database

# AI classification
OPENAI_API_KEY=your-key-or-empty
OPENAI_CLASSIFY_ENABLED=true
OPENAI_MODEL=gpt-4o-mini
CLASSIFY_RATE_PER_MINUTE=10
```

---

## Project Structure (key bits)

```
app/
  DTOs/ClassificationDTO.php
  Http/Controllers/
    TicketController.php
    ClassificationController.php
    StatsController.php
    TicketExportController.php
  Http/Requests/
    StoreTicketRequest.php
    UpdateTicketRequest.php
  Http/Resources/TicketResource.php
  Jobs/
    ClassifyTicket.php
    GenerateTicketsCsv.php
  Models/
    Ticket.php
    DataExport.php
  Providers/
    RepositoryServiceProvider.php
  Repositories/
    TicketRepository.php
    EloquentTicketRepository.php
  Services/
    TicketService.php
    TicketClassifier.php
    StatsService.php
bootstrap/providers.php      # binds RepositoryServiceProvider
resources/
  js/
    app.js
    router.js
    services/api.js
    components/App.vue
    views/
      TicketsList.vue
      TicketDetail.vue
      Dashboard.vue
  views/app.blade.php        # Vite + SPA shell
  css/app.css
routes/
  api.php
  web.php
```

---

## API Reference (JSON)

### Tickets

* `GET /api/tickets`

  * Query: `q`, `status` (open|in\_progress|resolved), `category` (billing|technical|account|other), `has_note` (1|0), `per_page` (1..100), `page`
* `POST /api/tickets`

  * Body: `{ subject: string<=200, body: string }`
* `GET /api/tickets/{id}`
* `PATCH /api/tickets/{id}`

  * Body: `{ status?, category? (nullable), note? (nullable) }`
  * If `category` is set here, `category_source` becomes `manual`

### Classification

* `POST /api/tickets/{id}/classify` → `202 Accepted`

  * Throttled by `classify` RateLimiter; enqueues `ClassifyTicket`

### Stats

* `GET /api/stats` → `{ status_counts: {...}, category_counts: {..., unclassified} }`

### CSV Export (queued)

* `POST /api/tickets/export` → `{ export_id, status }`

  * Body accepts same filters as `GET /tickets`
* `GET /api/tickets/export/{id}` → `{ status, download_url? }`
* `GET /api/tickets/export/{id}/download` → CSV file

---

## Frontend Routes

* `/tickets` — list, filters, create, export
* `/tickets/:id` — detail with edit + classify button
* `/dashboard` — counters + bar chart

---

## Architecture Notes

* **Enums**: `TicketStatus` (open/in\_progress/resolved), `TicketCategory` (billing/technical/account/other)
* **Ticket fields**: `subject`, `body`, `status`, `category?`, `category_source` (ai|manual), `explanation?`, `confidence?`, `note?`, `classified_at?`
* **Service/Repository**: `TicketService` orchestrates; `TicketRepository` encapsulates queries & filters
* **AI**: `TicketClassifier` uses OpenAI chat; strict JSON; safe fallback heuristics (keyword‑based)
* **Jobs**: `ClassifyTicket` updates AI fields; `GenerateTicketsCsv` streams big exports to `storage/app/exports/`
* **DataExport**: tracks export `status` (pending|running|done|failed), `params`, `file_path`, `expires_at`
* **Security**: per‑page is clamped (1..100); validation on all inputs; throttle classify

---

## Testing

* Run all tests

```bash
php artisan test
```

* Defaults (via `phpunit.xml`):

  * SQLite in‑memory
  * `QUEUE_CONNECTION=sync`
  * `OPENAI_CLASSIFY_ENABLED=false` → fallback path covered
* Suite covers: tickets create/list/update, classification enqueue, stats, export job & download

---

---

## Assumptions & Trade-offs

* **Simple Auth**: project ships with no auth; assumes single-user or protected environment.
* **One model**: only GPT‑4o‑mini wired; switching models means editing config.
* **Keyword fallback**: chosen for transparency and zero-cost testing, but not smart.
* **Database queues**: easier to demo locally; in production, Redis or SQS is preferable.
* **Export storage**: files are local `storage/app/exports/`; not cleaned up automatically.
* **UI scope**: Vue SPA is deliberately minimal (Options API, no state lib) to stay clear.

---

## What I’d Do with More Time

* Add authentication and roles (agent/admin).
* Multi‑tenant (companies) with ticket ownership.
* SSE or WebSockets for live job status instead of polling.
* CI/CD pipeline with automated tests + deploy.
* Better error handling & user‑facing messages.
* Cleanup scheduler for old CSV exports.
* Improve AI prompts and support multiple models (with retries).

---

## License

MIT (feel free to use, tweak, and learn from it)
