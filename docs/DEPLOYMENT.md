# Production Deployment

This document describes the production deployment workflow for Task Manager API using Docker Compose.

The production stack separates the application into dedicated services:

- Nginx web server
- Laravel PHP-FPM application
- MySQL database
- Laravel queue worker
- Database migration service

---

## Production Architecture

text
HTTP
|
v

        +----------------+
        |     Nginx      |
        |      web       |
        +----------------+
                 |
              FastCGI
                 |
                 v

        +----------------+
        |    PHP-FPM     |
        | Laravel App    |
        +----------------+
             |       |
             |       |
             v       v

       +---------+  +-------------+
       | MySQL 8 |  | Queue       |
       | Database|  | Worker      |
       +---------+  +-------------+

              ^
              |
      +----------------+
      | Migration      |
      | Service        |
      +----------------+

---

## Production Services

The production Docker Compose file defines the following services:

| Service   | Purpose                     | Exposure        |
| --------- | --------------------------- | --------------- |
| `web`     | Nginx web server            | `8000:80`       |
| `app`     | Laravel PHP-FPM application | Internal only   |
| `worker`  | Laravel queue worker        | Internal only   |
| `db`      | MySQL database              | Internal only   |
| `migrate` | Runs database migrations    | No exposed port |

MySQL is available inside the Docker network:

text
db:3306

The database port is not published externally.

---

## Prerequisites

Required:

- Docker
- Docker Compose
- Production environment file

Create:

text
.env.production

The file must not be committed to the repository.

---

## Environment Configuration

Minimum production configuration:

env
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

QUEUE_CONNECTION=database

Secrets must never be committed:

text
APP_KEY
DB_PASSWORD
MYSQL_ROOT_PASSWORD
ADMIN_PASSWORD

---

## Build Production Images

Build the Laravel application image:

bash
docker build -t task-manager-api:phase-8-3 .

Build the Nginx image:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 build web

---

## Start Production Stack

Start all services:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 up -d

The migration service runs first.

The application and worker services start only after successful migration completion.

---

## Verify Deployment

Check service status:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 ps

Expected state:

text
db healthy
app healthy
web healthy
worker running

---

## Laravel Environment Check

Run:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 exec app php artisan about

Expected:

text
Environment production
Debug Mode OFF
Database mysql
Logs stderr
Queue database

---

## Migration Verification

Check migration service:

bash
docker inspect task-manager-api-migrate-1 \
 --format 'status={{.State.Status}} exit_code={{.State.ExitCode}}'

Expected:

text
status=exited exit_code=0

---

## Health Check

Laravel exposes:

http
GET /up

Verify:

bash
curl -i http://localhost:8000/up

Expected:

text
HTTP/1.1 200 OK

---

# Queue Worker

The application uses the database queue driver:

env
QUEUE_CONNECTION=database

The production worker runs as a dedicated container.

Worker configuration:

text
--sleep=3
--tries=3
--timeout=60

The worker timeout is intentionally lower than the queue `retry_after` value to reduce duplicate processing risk.

---

## Failed Jobs

List failed jobs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 exec app php artisan queue:failed

Retry a failed job:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 exec app php artisan queue:retry <job-id>

---

## Logging

Production Laravel logs are written to:

env
LOG_CHANNEL=stderr

View application logs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 logs app --tail=100

View worker logs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 logs worker --tail=100

View Nginx logs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 logs web --tail=100

---

## Error Handling

When:

env
APP_DEBUG=false

internal exception details are not exposed to API clients.

Example response:

json
{
"success": false,
"message": "Internal Server Error",
"errors": null
}

Detailed exceptions remain available through application logs.

---

## Nginx Security Hardening

Production Nginx configuration includes:

- Disabled server version exposure
- Security response headers
- Hidden file restrictions
- Docker stdout/stderr logging

PHP version exposure is disabled:

ini
expose_php = Off

---

## Database Performance

The database indexes are aligned with common query patterns.

Important indexes:

text
tasks_user_id_created_at_index

category_task_category_id_index

The task listing query uses the composite index:

text
(user_id, created_at)

Performance was verified using `EXPLAIN`.

---

## Updating Production

Pull latest code:

bash
git pull

Rebuild images:

bash
docker build -t task-manager-api:phase-8-3 .

Restart services:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 up -d

---

## Troubleshooting

Check running containers:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 ps

Check application logs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 logs app --tail=100

Check queue worker:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 logs worker --tail=100

Check health endpoint:

bash
curl -i http://localhost:8000/up
