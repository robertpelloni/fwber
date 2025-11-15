# Monitoring and Observability Setup

Comprehensive guide for production monitoring with multiple service options.

## Table of Contents
- [Sentry (Error Tracking)](#sentry-error-tracking)
- [New Relic (APM)](#new-relic-apm)
- [Prometheus + Grafana](#prometheus--grafana)
- [Application Logging](#application-logging)
- [Health Checks](#health-checks)
- [Alerts Configuration](#alerts-configuration)

---

## Sentry (Error Tracking)

### Backend Setup (Laravel)

```bash
# Install Sentry SDK
cd fwber-backend
composer require sentry/sentry-laravel

# Publish config
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

**Configure `.env`**:
```env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_PROFILES_SAMPLE_RATE=0.2
```

**Register middleware** in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\SentryContext::class);
})
```

### Frontend Setup (Next.js)

```bash
cd fwber-frontend
npm install @sentry/nextjs
npx @sentry/wizard@latest -i nextjs
```

**Configure `sentry.client.config.ts`**:
```typescript
import * as Sentry from "@sentry/nextjs";

Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  tracesSampleRate: 0.1,
  debug: false,
  environment: process.env.NODE_ENV,
});
```

**Environment variables**:
```env
NEXT_PUBLIC_SENTRY_DSN=https://your-frontend-dsn@sentry.io/project-id
SENTRY_AUTH_TOKEN=your-sentry-auth-token
```

### Testing Sentry

```bash
# Backend - Trigger test error
php artisan sentry:test

# Frontend - Add test error in a page:
throw new Error("Sentry test error");
```

---

## New Relic (APM)

### Backend Setup

```bash
# Install PHP agent
cd fwber-backend
composer require newrelic/nr-agent-wrapper

# Configure in php.ini or via environment
```

**Docker environment variables** in `docker-compose.prod.yml`:
```yaml
laravel:
  environment:
    - NEW_RELIC_ENABLED=true
    - NEW_RELIC_APP_NAME=FWBER-Backend
    - NEW_RELIC_LICENSE_KEY=${NEW_RELIC_LICENSE_KEY}
```

### Frontend Setup

```bash
cd fwber-frontend
npm install @newrelic/next
```

**Configure `newrelic.js`**:
```javascript
exports.config = {
  app_name: ['FWBER-Frontend'],
  license_key: process.env.NEW_RELIC_LICENSE_KEY,
  distributed_tracing: { enabled: true },
  logging: { level: 'info' }
};
```

---

## Prometheus + Grafana

### Architecture

```
[Laravel] → [laravel-prometheus-exporter] → [Prometheus] → [Grafana]
[Next.js] → [prometheus-exporter] → [Prometheus] → [Grafana]
```

### Backend Metrics Exporter

```bash
cd fwber-backend
composer require triadev/laravel-prometheus-exporter
```

**Publish config**:
```bash
php artisan vendor:publish --provider="Triadev\LaravelPrometheusExporter\Provider\LaravelPrometheusExporterServiceProvider"
```

**Add route** in `routes/web.php`:
```php
Route::get('/metrics', [\Triadev\LaravelPrometheusExporter\Controller\MetricsController::class, 'getMetrics']);
```

### Docker Compose Integration

**Add to `docker-compose.prod.yml`**:
```yaml
  prometheus:
    image: prom/prometheus:latest
    container_name: fwber-prometheus
    restart: unless-stopped
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
    networks:
      - fwber_network

  grafana:
    image: grafana/grafana:latest
    container_name: fwber-grafana
    restart: unless-stopped
    ports:
      - "3000:3000"
    volumes:
      - grafana_data:/var/lib/grafana
      - ./monitoring/grafana-provisioning:/etc/grafana/provisioning
    environment:
      - GF_SECURITY_ADMIN_USER=${GRAFANA_ADMIN_USER:-admin}
      - GF_SECURITY_ADMIN_PASSWORD=${GRAFANA_ADMIN_PASSWORD}
      - GF_INSTALL_PLUGINS=redis-datasource
    networks:
      - fwber_network

volumes:
  prometheus_data:
  grafana_data:
```

**Create `monitoring/prometheus.yml`**:
```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  - job_name: 'laravel'
    static_configs:
      - targets: ['laravel:9000']
    metrics_path: '/metrics'

  - job_name: 'mysql'
    static_configs:
      - targets: ['mysql:3306']

  - job_name: 'redis'
    static_configs:
      - targets: ['redis:6379']

  - job_name: 'nginx'
    static_configs:
      - targets: ['nginx:80']
```

---

## Application Logging

### Centralized Logging with ELK Stack

**Add to `docker-compose.prod.yml`**:
```yaml
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.11.0
    container_name: fwber-elasticsearch
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data
    networks:
      - fwber_network

  logstash:
    image: docker.elastic.co/logstash/logstash:8.11.0
    container_name: fwber-logstash
    volumes:
      - ./monitoring/logstash.conf:/usr/share/logstash/pipeline/logstash.conf
    depends_on:
      - elasticsearch
    networks:
      - fwber_network

  kibana:
    image: docker.elastic.co/kibana/kibana:8.11.0
    container_name: fwber-kibana
    ports:
      - "5601:5601"
    environment:
      - ELASTICSEARCH_HOSTS=http://elasticsearch:9200
    depends_on:
      - elasticsearch
    networks:
      - fwber_network
```

### Laravel Logging Configuration

**Update `config/logging.php`**:
```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['daily', 'sentry'],
],

'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 14,
],
```

---

## Health Checks

### Backend Health Endpoint

**Create `app/Http/Controllers/HealthController.php`**:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function check()
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => []
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $status['checks']['database'] = 'ok';
        } catch (\Exception $e) {
            $status['checks']['database'] = 'failed';
            $status['status'] = 'unhealthy';
        }

        // Redis check
        try {
            Redis::ping();
            $status['checks']['redis'] = 'ok';
        } catch (\Exception $e) {
            $status['checks']['redis'] = 'failed';
            $status['status'] = 'unhealthy';
        }

        // Storage check
        $status['checks']['storage'] = is_writable(storage_path('logs')) ? 'ok' : 'failed';

        $httpCode = $status['status'] === 'healthy' ? 200 : 503;
        return response()->json($status, $httpCode);
    }
}
```

**Add route** in `routes/api.php`:
```php
Route::get('/health', [HealthController::class, 'check']);
```

### Docker Health Checks

**Update services in `docker-compose.prod.yml`**:
```yaml
laravel:
  healthcheck:
    test: ["CMD", "php", "artisan", "health:check"]
    interval: 30s
    timeout: 10s
    retries: 3
    start_period: 40s

mysql:
  healthcheck:
    test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
    interval: 10s
    timeout: 5s
    retries: 3

redis:
  healthcheck:
    test: ["CMD", "redis-cli", "ping"]
    interval: 10s
    timeout: 5s
    retries: 3
```

---

## Alerts Configuration

### Uptime Monitoring

**Options:**
- [UptimeRobot](https://uptimerobot.com/) (Free tier: 50 monitors)
- [Pingdom](https://www.pingdom.com/)
- [StatusCake](https://www.statuscake.com/)

**Monitor endpoints:**
- `https://yourdomain.com/api/health` (Backend health)
- `https://yourdomain.com/` (Frontend)

### Sentry Alerts

Configure in Sentry dashboard:
- Error threshold: > 10 errors in 5 minutes
- Performance degradation: P95 latency > 2 seconds
- Notification channels: Email, Slack, PagerDuty

### Prometheus Alert Rules

**Create `monitoring/alert-rules.yml`**:
```yaml
groups:
  - name: fwber_alerts
    interval: 30s
    rules:
      - alert: HighErrorRate
        expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High error rate detected"
          description: "Error rate is {{ $value }} errors/sec"

      - alert: HighMemoryUsage
        expr: (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes) < 0.1
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High memory usage"

      - alert: DatabaseDown
        expr: up{job="mysql"} == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "MySQL database is down"
```

---

## Quick Start Commands

```bash
# Deploy monitoring stack
docker compose -f docker-compose.prod.yml up -d prometheus grafana

# View Grafana
open http://localhost:3000
# Default credentials: admin / (from env)

# View Prometheus
open http://localhost:9090

# Test health endpoint
curl http://localhost/api/health

# View Laravel logs
docker compose -f docker-compose.prod.yml logs -f laravel

# View all service logs
docker compose -f docker-compose.prod.yml logs -f
```

---

## Performance Metrics to Track

### Backend (Laravel)
- Request rate (req/sec)
- Response time (P50, P95, P99)
- Error rate (4xx, 5xx)
- Database query time
- Cache hit ratio
- Queue job processing time

### Frontend (Next.js)
- Page load time (FCP, LCP)
- Time to Interactive (TTI)
- Cumulative Layout Shift (CLS)
- JavaScript bundle size
- API call latency

### Infrastructure
- CPU usage (per service)
- Memory usage (per service)
- Disk I/O
- Network throughput
- Container restart count

---

## Cost Optimization

### Free Tier Options
- **Sentry**: 5,000 events/month free
- **UptimeRobot**: 50 monitors free
- **Grafana Cloud**: 10k series free
- **New Relic**: 100 GB/month free

### Self-Hosted Stack (Lowest Cost)
- Prometheus + Grafana (free, open-source)
- ELK Stack (free, but resource-intensive)
- Custom health checks + email alerts

---

## Resources

- [Sentry Laravel Docs](https://docs.sentry.io/platforms/php/guides/laravel/)
- [Prometheus Best Practices](https://prometheus.io/docs/practices/)
- [Grafana Dashboards](https://grafana.com/grafana/dashboards/)
- [Laravel Logging](https://laravel.com/docs/logging)
- [Next.js Monitoring](https://nextjs.org/docs/basic-features/built-in-css-support)
