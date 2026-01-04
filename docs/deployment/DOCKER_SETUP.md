# Docker Development Environment Setup

This guide explains how to use Docker Compose to run the entire fwber development environment.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) 20.10+
- [Docker Compose](https://docs.docker.com/compose/install/) 2.0+
- At least 4GB of available RAM
- At least 10GB of available disk space

## Quick Start

```bash
# Clone the repository
git clone <repository-url>
cd fwber

# Start all services
docker-compose up

# Or run in background
docker-compose up -d
```

That's it! The application will be available at:
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## Services

### Core Services

| Service | Description | Port | Container Name |
|---------|-------------|------|----------------|
| `frontend` | Next.js application | 3000 | fwber-frontend |
| `backend` | Laravel API | 8000 | fwber-backend |
| `queue` | Laravel queue worker | - | fwber-queue |
| `mysql` | MySQL 8.0 database | 3306 | fwber-mysql |
| `redis` | Redis cache/queue | 6379 | fwber-redis |

### Optional Services (Tools Profile)

| Service | Description | Port | Container Name |
|---------|-------------|------|----------------|
| `phpmyadmin` | Database management UI | 8080 | fwber-phpmyadmin |
| `mailhog` | Email testing | 8025 (UI), 1025 (SMTP) | fwber-mailhog |

## Common Commands

### Starting Services

```bash
# Start all core services
docker-compose up

# Start in detached mode (background)
docker-compose up -d

# Start specific service
docker-compose up frontend

# Start with optional tools (PHPMyAdmin, Mailhog)
docker-compose --profile tools up
```

### Stopping Services

```bash
# Stop all services
docker-compose down

# Stop and remove volumes (⚠️ deletes all data)
docker-compose down -v

# Stop specific service
docker-compose stop frontend
```

### Viewing Logs

```bash
# View all logs
docker-compose logs

# View logs for specific service
docker-compose logs frontend

# Follow logs (real-time)
docker-compose logs -f backend

# View last 100 lines
docker-compose logs --tail=100 frontend
```

### Executing Commands

```bash
# Run command in frontend container
docker-compose exec frontend npm install

# Run command in backend container
docker-compose exec backend php artisan migrate

# Open shell in container
docker-compose exec backend sh
docker-compose exec frontend sh
```

## Initial Setup

### 1. Environment Configuration

Both frontend and backend use environment variables from docker-compose.yml by default. For customization:

**Frontend** (optional):
```bash
# Create .env.local in fwber-frontend/
cp fwber-frontend/.env.example fwber-frontend/.env.local
```

**Backend** (optional):
```bash
# Create .env in fwber-backend/
cp fwber-backend/.env.example fwber-backend/.env
```

Environment variables in docker-compose.yml will override these files.

### 2. Generate Laravel Application Key

```bash
docker-compose exec backend php artisan key:generate
```

### 3. Run Database Migrations

```bash
# Run migrations
docker-compose exec backend php artisan migrate

# Run migrations with seeders
docker-compose exec backend php artisan migrate --seed
```

### 4. Install Dependencies (if needed)

```bash
# Frontend dependencies
docker-compose exec frontend npm install

# Backend dependencies
docker-compose exec backend composer install
```

## Development Workflow

### Hot Reload

Both frontend and backend support hot reload:

- **Frontend**: Changes to code automatically trigger page refresh
- **Backend**: PHP files are reloaded automatically on each request

### Database Access

**Via PHPMyAdmin** (start with `--profile tools`):
1. Start PHPMyAdmin: `docker-compose --profile tools up -d phpmyadmin`
2. Open http://localhost:8080
3. Login with:
   - Server: `mysql`
   - Username: `root`
   - Password: `root_password`

**Via MySQL Client**:
```bash
# From host machine
mysql -h 127.0.0.1 -P 3306 -u fwber_user -pfwber_password fwber_dev

# From container
docker-compose exec mysql mysql -u fwber_user -pfwber_password fwber_dev
```

### Redis Access

```bash
# From host machine
redis-cli -h 127.0.0.1 -p 6379

# From container
docker-compose exec redis redis-cli
```

### Email Testing with Mailhog

```bash
# Start Mailhog
docker-compose --profile tools up -d mailhog

# Access web UI
open http://localhost:8025
```

Configure Laravel to use Mailhog:
```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### Queue Monitoring

```bash
# View queue worker logs
docker-compose logs -f queue

# Restart queue worker
docker-compose restart queue

# Process failed jobs
docker-compose exec backend php artisan queue:retry all
```

## Advanced Usage

### Running Tests

```bash
# Frontend tests
docker-compose exec frontend npm test

# Backend tests
docker-compose exec backend php artisan test

# With coverage
docker-compose exec backend php artisan test --coverage
```

### Database Backup

```bash
# Backup database
docker-compose exec mysql mysqldump -u fwber_user -pfwber_password fwber_dev > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u fwber_user -pfwber_password fwber_dev < backup.sql
```

### Rebuild Containers

```bash
# Rebuild all containers
docker-compose build

# Rebuild specific container
docker-compose build frontend

# Rebuild without cache
docker-compose build --no-cache

# Rebuild and start
docker-compose up --build
```

### Scaling Services

```bash
# Run 3 queue workers
docker-compose up --scale queue=3

# Run 2 backend instances (requires load balancer)
docker-compose up --scale backend=2
```

## Troubleshooting

### Container Won't Start

```bash
# Check container status
docker-compose ps

# Check logs for errors
docker-compose logs backend

# Remove and recreate containers
docker-compose down
docker-compose up --force-recreate
```

### Port Already in Use

If you see "port is already allocated" error:

```bash
# Find process using port
lsof -i :3000  # macOS/Linux
netstat -ano | findstr :3000  # Windows

# Kill the process or change port in docker-compose.yml
```

### Permission Issues

```bash
# Fix storage permissions (backend)
docker-compose exec backend chmod -R 775 storage bootstrap/cache
docker-compose exec backend chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Failed

```bash
# Wait for MySQL to be ready
docker-compose exec backend php artisan migrate

# Check MySQL is running
docker-compose ps mysql

# Verify connection
docker-compose exec backend php artisan tinker
>>> DB::connection()->getPdo();
```

### Node Modules Issues (Frontend)

```bash
# Remove node_modules and reinstall
docker-compose exec frontend rm -rf node_modules package-lock.json
docker-compose exec frontend npm install

# Or rebuild container
docker-compose build --no-cache frontend
```

### Clear All Data and Start Fresh

```bash
# ⚠️ WARNING: This will delete all data!
docker-compose down -v
docker volume prune
docker-compose up --build
```

## Performance Optimization

### Use Docker Desktop's Virtualization

- Enable VirtioFS (macOS)
- Use WSL 2 backend (Windows)
- Allocate more resources in Docker Desktop settings

### Optimize Volume Mounts

For better performance, use named volumes for dependencies:

```yaml
volumes:
  - frontend_node_modules:/app/node_modules  # ✅ Fast
  - ./fwber-frontend:/app  # Code mount
```

### Cache Composer/NPM Packages

Add to docker-compose.yml:

```yaml
volumes:
  - ~/.composer:/root/.composer  # Composer cache
  - ~/.npm:/root/.npm  # NPM cache
```

## Production vs Development

⚠️ **DO NOT** use docker-compose.yml for production!

For production, use:
- Multi-stage Dockerfiles
- Environment-specific compose files
- Kubernetes or similar orchestration
- Managed databases (not Docker containers)
- See `DEPLOYMENT_CHECKLIST.md` for details

## Helper Scripts

Create convenience scripts:

### `./scripts/dev-start.sh`
```bash
#!/bin/bash
docker-compose up -d
echo "✅ Services started!"
echo "Frontend: http://localhost:3000"
echo "Backend: http://localhost:8000"
```

### `./scripts/dev-stop.sh`
```bash
#!/bin/bash
docker-compose down
echo "✅ Services stopped!"
```

### `./scripts/dev-reset.sh`
```bash
#!/bin/bash
echo "⚠️  This will delete all data! Press Ctrl+C to cancel."
sleep 5
docker-compose down -v
docker-compose up --build
```

## Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Dockerfile Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Laravel Docker](https://laravel.com/docs/sail)
- [Next.js Docker](https://nextjs.org/docs/deployment#docker-image)

## Support

If you encounter issues:

1. Check this documentation
2. Search existing GitHub issues
3. Check Docker and service logs
4. Ask in project Slack/Discord
5. Create a GitHub issue with:
   - Docker version (`docker --version`)
   - Docker Compose version (`docker-compose --version`)
   - Error logs (`docker-compose logs`)
   - Steps to reproduce

---

**Pro tip**: Add aliases to your shell RC file:
```bash
alias dcup='docker-compose up -d'
alias dcdown='docker-compose down'
alias dclogs='docker-compose logs -f'
alias dcps='docker-compose ps'
```
