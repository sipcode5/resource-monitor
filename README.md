# 📡 Real-Time Resource Monitor

A full-stack, real-time system dashboard built with **Laravel 11**, **Reverb**, **Redis**, and **Vue 3** (Composition API).

---

## Architecture at a Glance

```
Browser (Vue 3)
  │
  ├─ HTTP (Axios)     → Laravel API  (/api/metrics, /api/queue-stats)
  └─ WebSocket (Echo) → Reverb       (ws://localhost:8080)
                              │
                      Broadcasting Events
                              │
         ┌────────────────────┼────────────────────┐
         │                    │                     │
   Queue Worker         Scheduler (cron)         Redis
   (CollectSystemMetrics, FetchApiStats)
```

### Services (Docker)

| Container        | Role                                   | Port      |
|------------------|----------------------------------------|-----------|
| `rm_nginx`       | Reverse proxy / static files           | **8000**  |
| `rm_app`         | PHP-FPM (Laravel)                      | internal  |
| `rm_reverb`      | WebSocket server (Reverb)              | **8080**  |
| `rm_queue`       | Queue worker (`redis` driver)          | —         |
| `rm_scheduler`   | Cron replacement (`schedule:run`)      | —         |
| `rm_mysql`       | MySQL 8.3 database                     | 3306      |
| `rm_redis`       | Redis 7 (cache + queues + sessions)    | 6379      |

---

## Quick Start (Docker)

### Prerequisites
- Docker ≥ 24
- Docker Compose ≥ 2.20

```bash
# 1. Enter the project
cd ~/Herd/realtime-dashboard

# 2. Launch the full stack (first run ~2 min to build)
docker compose up -d --build

# 3. Wait for MySQL health check, then run migrations
docker compose exec app php artisan migrate --force

# 4. Dispatch an initial metrics collection
docker compose exec app php artisan tinker --execute="dispatch(new App\Jobs\CollectSystemMetrics);"

# 5. Open the dashboard
open http://localhost:8000
```

> The WebSocket badge in the top-right turns **green (Live)** once Reverb connects.

---

## Local Development (Herd / Valet)

```bash
composer install && npm install
cp .env.example .env
# Edit .env: DB_HOST=127.0.0.1, REDIS_HOST=127.0.0.1, REVERB_HOST=localhost
php artisan key:generate && php artisan migrate

# Run in separate terminals:
php artisan serve            # http://localhost:8000
php artisan reverb:start     # ws://localhost:8080
php artisan queue:work redis
php artisan schedule:work
npm run dev                  # Vite HMR
```

---

## Key Files

```
app/
  Events/
    SystemMetricsUpdated.php  # Broadcast: cpu/memory/disk stats
    AlertTriggered.php        # Broadcast: threshold alerts
  Jobs/
    CollectSystemMetrics.php  # Queued: reads sys metrics every minute
    FetchApiStats.php         # Queued: GitHub & OpenWeather every 5 min
  Http/Controllers/
    DashboardController.php   # API endpoints + SPA shell

resources/js/
  bootstrap.js                # Axios + Echo/Reverb setup
  App.vue                     # Root Vue 3 component (Composition API)
  composables/
    useMetrics.js             # WebSocket listener + HTTP polling fallback
    useNotifications.js       # Live alert feed
  components/
    MetricCard.vue            # Stat card with animated progress bar
    GaugeRing.vue             # SVG circular gauge
    SparkLine.vue             # SVG sparkline chart
    NotificationFeed.vue      # Animated alert list with auto-dismiss
    ApiStatsPanel.vue         # Third-party API stats panel
```

---

## Alert Thresholds

Alerts broadcast automatically when:

| Metric | Warning | Critical |
|--------|---------|----------|
| CPU    | ≥ 75%   | ≥ 90%    |
| Memory | —       | ≥ 90%    |
| Disk   | ≥ 85%   | —        |

Trigger manually from Tinker:
```php
broadcast(new App\Events\AlertTriggered('cpu', 'Test alert!', 'warning'));
```

---

## Optional Integrations

Set in `.env`:
```env
OPENWEATHER_API_KEY=your_api_key_here
OPENWEATHER_CITY=London
```
