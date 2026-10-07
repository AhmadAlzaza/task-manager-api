# Production Deployment

This document describes the production deployment workflow for Task Manager API using Docker Compose.

The production stack separates the application into dedicated services:

```text
                    HTTP
                     │
                     ▼
              ┌─────────────┐
              │    Nginx    │
              │     web     │
              └──────┬──────┘
                     │ FastCGI
                     ▼
              ┌─────────────┐
              │   PHP-FPM   │
              │    app      │
              └──────┬──────┘
                     │
              ┌──────┴──────┐
              ▼             ▼
        ┌───────────┐  ┌──────────────┐
        │  MySQL 8  │  │ Queue Worker │
        │    db     │  │    worker    │
        └───────────┘  └──────────────┘
              ▲
              │
        ┌──────────────┐
        │  Migration   │
        │   Service    │
        └──────────────┘
```

## Production Services

The production Compose file defines:

| Service   | Purpose                     | Exposure        |
| --------- | --------------------------- | --------------- |
| `web`     | Nginx web server            | `8000:80`       |
| `app`     | Laravel PHP-FPM application | Internal only   |
| `worker`  | Laravel queue worker        | Internal only   |
| `db`      | MySQL database              | Internal only   |
| `migrate` | Database migration service  | No exposed port |

MySQL is reachable internally as:

```text
db:3306
```

The database port is not published to the host.

## Prerequisites

Install:

- Docker
- Docker Compose

Create the production environment file:

```text
.env.production
```

The file must never be committed to Git.

The repository already excludes it through `.gitignore`.

## Production Environment

At minimum, configure:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=

LOG_CHANNEL=stderr

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

MYSQL_ROOT_PASSWORD=

QUEUE_CONNECTION=database

ADMIN_NAME=Admin
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

Depending on the deployment, configure the remaining application variables required by the application.

### Required secrets

The following values must never be committed:

```text
APP_KEY
DB_PASSWORD
MYSQL_ROOT_PASSWORD
ADMIN_PASSWORD
```

### Important

`DB_HOST` must be:

```text
db
```

because the Laravel containers communicate with MySQL through the Docker network.

## Build Production Images

Build the Laravel application image:

```bash
docker build \
  --build-arg DOCS_BASE_URL=http://localhost:8000 \
  -t task-manager-api:phase-8-6 .
```

Build the Nginx image:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  build web
```

The Nginx image copies the `public/` directory from the application image, so the Nginx image must be rebuilt whenever application assets or the Laravel `public/` directory change.
Static API documentation is generated automatically during the application image build.
The documentation base URL is supplied through the `DOCS_BASE_URL` build argument.
Scribe remains a development dependency and is not included in the production runtime image.

## Start the Production Stack

Start the services:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  up -d
```

The startup flow is:

```text
db
 ↓
migrate
 ↓
app + worker
 ↓
web
```

The migration service runs only after MySQL becomes healthy.

The application and queue worker start only after the migration service completes successfully.

## Verify Deployment

Check service status:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  ps
```

Expected state:

```text
db       healthy
app      healthy
web      healthy
worker   running
```

The migration service is a one-shot container, so inspect it with:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  ps -a migrate
```

Expected:

```text
migrate    exited (0)
```

## Laravel Environment Check

Run:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  exec app php artisan about
```

Verify that the application reports values consistent with production, including:

```text
Environment: production
Debug Mode: OFF
Database: mysql
Queue: database
Logs: stderr
```

## Health Checks

Laravel exposes the application health endpoint:

```http
GET /up
```

Verify it through Nginx:

```bash
curl -i http://localhost:8000/up
```

Expected:

```text
HTTP/1.1 200 OK
```

The Nginx container also has its own health check.

Check it with:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  ps
```

## Database Verification

Check that the migration table exists and migrations have completed:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  exec app php artisan migrate:status
```

The latest migrations should show as:

```text
Ran
```

Do not run destructive migration commands such as `migrate:fresh` against a production database.

## Queue Worker

The application uses Laravel's database queue:

```env
QUEUE_CONNECTION=database
```

The worker is a dedicated Docker service.

Current worker configuration:

```text
--sleep=3
--tries=3
--timeout=60
```

The application queue connection uses:

```text
retry_after = 90 seconds
```

The worker timeout is intentionally lower than `retry_after` to reduce the chance of duplicate processing caused by a job becoming visible again while still running.

### Check Worker Status

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  ps worker
```

### View Queue Failed Jobs

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  exec app php artisan queue:failed
```

### Retry a Failed Job

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  exec app php artisan queue:retry <job-id>
```

## Logging

Production Laravel logs are written to stderr:

```env
LOG_CHANNEL=stderr
```

View application logs:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  logs app --tail=100
```

View worker logs:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  logs worker --tail=100
```

View Nginx logs:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  logs web --tail=100
```

Follow logs in real time:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  logs -f app
```

## Error Handling

Production runs with:

```env
APP_DEBUG=false
```

Internal exception details are not returned to API clients.

Example:

```json
{
    "success": false,
    "message": "Internal Server Error",
    "errors": null
}
```

Detailed exception information should be investigated through container logs.

## Nginx Security Hardening

The production Nginx configuration includes:

- Disabled server version exposure
- Security response headers
- Hidden-file protection
- PHP request validation with `try_files`
- FastCGI communication with the internal PHP-FPM service
- Access logging to stdout
- Error logging to stderr

The database is not directly exposed through a published host port.

## Updating an Existing Production Deployment

After pulling the latest code, rebuild the application image and the Nginx image.

```bash
git pull
```

Build the updated application image:

```bash
docker build \
  --build-arg DOCS_BASE_URL=http://localhost:8000 \
  -t task-manager-api:phase-8-6 .
```

Rebuild Nginx using the updated application image:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  build web
```

Start or recreate the stack:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  up -d
```

Verify:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  ps
```

Then verify migrations:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  ps -a migrate
```

And verify application health:

```bash
curl -i http://localhost:8000/up
```

## Configuration Changes

When changing application environment variables, update `.env.production` and recreate the affected services:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  up -d
```

For changes that affect the application image itself, rebuild the application image first.

## Composer Dependency Changes

When `composer.json` or `composer.lock` changes:

```bash
docker build \
  --build-arg DOCS_BASE_URL=http://localhost:8000 \
  -t task-manager-api:phase-8-6 .
```

Then rebuild the Nginx image:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  build web
```

Finally restart the stack:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  up -d
```

## Troubleshooting

### App does not start

Check:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  logs app --tail=100
```

Then check migration status:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  ps -a migrate
```

### Migration service failed

View its logs:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  logs migrate
```

Common causes include:

- Incorrect database credentials
- Incorrect `DB_HOST`
- Missing `MYSQL_ROOT_PASSWORD`
- Database not becoming healthy
- Application image containing invalid migrations

### Database connection failure

Confirm:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
```

Then inspect:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  ps
```

The MySQL service should be:

```text
healthy
```

### Queue jobs are not processed

Check:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  logs worker --tail=100
```

Then check failed jobs:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  exec app php artisan queue:failed
```

### Nginx is unhealthy

Check:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  logs web --tail=100
```

Confirm that the application container is healthy:

```bash
docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  ps app
```

Then check:

```bash
curl -i http://localhost:8000/up
```

### Application changes are not visible

Because the Nginx image copies the Laravel `public/` directory from the application image, rebuild both images:

```bash
docker build \
  --build-arg DOCS_BASE_URL=http://localhost:8000 \
  -t task-manager-api:phase-8-6 .

docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  build web

docker compose \
  --env-file .env.production \
  -f docker-compose.production.yml \
  up -d
```

## Production Data

The MySQL data directory is stored in the named Docker volume:

```text
production_dbdata
```

Inspect volumes:

```bash
docker volume ls
```

The database volume must be included in the deployment's backup strategy.

Removing the volume destroys the persisted database data.

Do not run:

```bash
docker compose down -v
```

against a production environment unless intentional data deletion is part of the operation.

## Operational Notes

This deployment is designed as a production-oriented containerized setup.

It includes:

```text
Nginx
PHP-FPM
MySQL
Queue worker
Migration service
Health checks
Persistent database storage
Production error handling
Container logging
```

Additional infrastructure such as HTTPS termination, external backups, secret management, monitoring, and alerting should be provided by the deployment environment when required.
