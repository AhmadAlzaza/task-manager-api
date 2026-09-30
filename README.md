# Task Manager API

A RESTful API for managing tasks and categories, built with Laravel 13.

The project focuses on a maintainable backend architecture, consistent API contracts, authentication and authorization, database performance, automated testing, and containerized production deployment.

## Tech Stack

- **Laravel 13**
- **PHP 8.3**
- **MySQL 8.0**
- **SQLite** for automated tests and default local development
- **Laravel Sanctum** for API authentication
- **Laravel Policies** for authorization
- **PHPUnit** for automated testing
- **Laravel Pint** for code style
- **Larastan / PHPStan** for static analysis
- **Scribe** for API documentation
- **Docker**
- **Docker Compose**
- **Nginx**
- **PHP-FPM**

## Architecture

The application follows a layered Laravel architecture focused on clear responsibilities and maintainability.

- **Controllers** — handle HTTP requests and responses.
- **Actions** — encapsulate task business operations such as `CreateTaskAction` and `UpdateTaskAction`.
- **Form Requests** — validate incoming API requests.
- **Policies** — enforce model-level authorization rules.
- **Enums** — provide type-safe task status and user role values.
- **Query Objects** — `TaskQuery` encapsulates task listing and filtering query logic.
- **API Resources** — provide consistent JSON API representations.
- **Rate Limiting** — protects authentication and API endpoints against excessive requests.
- **Database Transactions** — used where multiple database operations must remain consistent.
- **Database Indexing** — optimized for common task and category access patterns.

### Production Architecture

The production deployment uses separate containers for the web server, Laravel application, queue worker, database, and migration process.

text
HTTP
|
v
+-------------+
| Nginx |
| web |
+-------------+
|
FastCGI
|
v
+-------------+
| PHP-FPM |
| Laravel app |
+-------------+
| |
| |
v v
+-----------+ +----------------+
| MySQL 8 | | Queue Worker |
| Database | | Laravel Queue |
+-----------+ +----------------+
^
|
+------------------+
| Migration Service|
+------------------+

Production infrastructure includes:

- Dedicated PHP-FPM application container
- Dedicated Nginx container
- Dedicated queue worker container
- Dedicated migration container
- MySQL 8 container
- Persistent MySQL Docker volume
- Application and web health checks
- Container restart policies
- Docker-based application logging
- Production error handling and API error contracts

## API Versioning

All API routes are exposed under:

text
/api/v1

## Authentication

The API uses **Laravel Sanctum personal access tokens**.

After login, send the returned token as a Bearer token:

http
Authorization: Bearer <token>

Protected endpoints require authentication.

## Rate Limiting

The application defines the following named rate limiters:

| Limiter         | Limit              | Key                                      |
| --------------- | ------------------ | ---------------------------------------- |
| `auth-login`    | 5 requests/minute  | IP address                               |
| `auth-register` | 5 requests/minute  | IP address                               |
| `api`           | 60 requests/minute | Authenticated user, otherwise IP address |

## Roles

| Role    | Permissions                                   |
| ------- | --------------------------------------------- |
| `user`  | Manage own tasks and browse categories        |
| `admin` | All user permissions plus category management |

## API Endpoints

### Authentication

| Method | Endpoint           | Description                             |
| ------ | ------------------ | --------------------------------------- |
| POST   | `/api/v1/register` | Register a new user                     |
| POST   | `/api/v1/login`    | Authenticate and obtain a bearer token  |
| POST   | `/api/v1/logout`   | Revoke the current authentication token |

### Tasks

| Method | Endpoint             | Description                         |
| ------ | -------------------- | ----------------------------------- |
| GET    | `/api/v1/tasks`      | List the authenticated user's tasks |
| POST   | `/api/v1/tasks`      | Create a task                       |
| GET    | `/api/v1/tasks/{id}` | Get a task                          |
| PUT    | `/api/v1/tasks/{id}` | Update a task                       |
| DELETE | `/api/v1/tasks/{id}` | Delete a task                       |

Task listing supports filtering, searching, sorting, and pagination.

Example:

http
GET /api/v1/tasks?status=pending

Additional query parameters include:

text
status
category_id
search
sort_by
sort_direction
per_page

### Categories

| Method | Endpoint                  | Description                    |
| ------ | ------------------------- | ------------------------------ |
| GET    | `/api/v1/categories`      | List categories                |
| GET    | `/api/v1/categories/{id}` | Get a category                 |
| POST   | `/api/v1/categories`      | Create a category (admin only) |
| PUT    | `/api/v1/categories/{id}` | Update a category (admin only) |
| DELETE | `/api/v1/categories/{id}` | Delete a category (admin only) |

## API Error Contract

API errors use a consistent JSON structure.

Example validation response:

json
{
"success": false,
"message": "Validation Error",
"errors": {
"status": [
"The selected status is invalid."
]
}
}

Common API error responses include:

| Status | Meaning               |
| ------ | --------------------- |
| `401`  | Unauthenticated       |
| `403`  | Unauthorized          |
| `404`  | Resource not found    |
| `422`  | Validation error      |
| `429`  | Too many requests     |
| `500`  | Internal server error |

Production API responses do not expose internal exception messages when debug mode is disabled.

## Environment Configuration

### Local Development

Copy `.env.example` to `.env`:

bash
cp .env.example .env

Generate the application key:

bash
php artisan key:generate

The `.env.example` file is development-oriented and currently uses:

- SQLite
- Database-backed sessions
- Database-backed cache
- Database queues
- Log mail delivery

The admin seeder uses:

env
ADMIN_NAME=Admin
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=

`ADMIN_PASSWORD` must be configured before running the admin seeder.

### Production

Production deployments use a separate `.env.production` file that is not committed to the repository.

At minimum, production should provide appropriate values for:

env
APP_ENV=production
APP_DEBUG=false
APP_KEY=...
LOG_CHANNEL=stderr

Database credentials and other secrets must also be supplied through the production environment.

Production secrets such as the following must never be committed:

text
APP_KEY
DB_PASSWORD
MYSQL_ROOT_PASSWORD
ADMIN_PASSWORD

## Local Installation

### Requirements

- PHP 8.3+
- Composer 2+
- SQLite or MySQL

### Setup

Clone the repository:

bash
git clone https://github.com/AhmadAlzaza/task-manager-api.git
cd task-manager-api

Install PHP dependencies:

bash
composer install

Create the environment file:

bash
cp .env.example .env

Generate the application key:

bash
php artisan key:generate

The default `.env.example` configuration uses SQLite.

Create the SQLite database:

bash
touch database/database.sqlite

Run migrations:

bash
php artisan migrate

Create the initial admin account:

bash
php artisan db:seed

Start the development server:

bash
php artisan serve

The API will be available at:

text
http://127.0.0.1:8000

## Production Deployment

The repository includes a production-oriented Docker deployment using:

- PHP 8.3 FPM
- Nginx
- MySQL 8.0
- Laravel database queue
- Dedicated queue worker
- Dedicated migration service
- Health checks
- Persistent MySQL storage
- Docker stdout/stderr logging

### Production Docker Services

| Service   | Purpose                     | Exposure        |
| --------- | --------------------------- | --------------- |
| `web`     | Nginx web server            | `8000:80`       |
| `app`     | Laravel PHP-FPM application | Internal only   |
| `worker`  | Laravel queue worker        | Internal only   |
| `db`      | MySQL database              | Internal only   |
| `migrate` | Runs database migrations    | No exposed port |

MySQL is available to application containers through the internal Docker network at:

text
db:3306

The database port is not published to the host.

### Production Image Build

Build the Laravel application image:

bash
docker build -t task-manager-api:phase-8-3 .

Build the Nginx production image:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 build web

### Start the Production Stack

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 up -d

The migration service runs before the application containers and must complete successfully before `app` and `worker` start.

### Verify Deployment

Check container status:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 ps

Expected services:

text
db healthy
app healthy
web healthy
worker running

Check the Laravel environment:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 exec app php artisan about

Production should report:

text
Environment production
Debug Mode OFF
Database mysql
Logs stderr
Queue database

Check migration completion:

bash
docker inspect task-manager-api-migrate-1 \
 --format 'status={{.State.Status}} exit_code={{.State.ExitCode}}'

Expected:

text
status=exited exit_code=0

Check the application health endpoint:

bash
curl -i http://localhost:8000/up

Expected:

text
HTTP/1.1 200 OK

## Queue Processing

The application uses the database queue driver:

env
QUEUE_CONNECTION=database

Queued work includes the welcome email flow.

In production, the queue worker runs as a dedicated Docker service rather than being started manually inside the application container.

The production worker runs with:

text
--sleep=3
--tries=3
--timeout=60

The database queue configuration uses:

text
retry_after = 90

The worker timeout is intentionally lower than `retry_after` to reduce the risk of the same job being processed concurrently after a worker timeout.

Check failed jobs:

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

View worker logs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 logs worker --tail=50

## Logging and Error Handling

Production Laravel logs are written to `stderr`:

env
LOG_CHANNEL=stderr

This allows Laravel application errors to be collected by Docker logging.

View application logs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 logs app --tail=100

View Nginx logs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 logs web --tail=100

Unexpected API exceptions are returned to clients using a generic production response:

json
{
"success": false,
"message": "Internal Server Error",
"errors": null
}

Internal exception details remain available through application logs while `APP_DEBUG=false`.

## Nginx Security Hardening

The production Nginx configuration includes:

- `server_tokens off`
- Docker stdout/stderr logging
- `X-Content-Type-Options`
- `X-Frame-Options`
- `Referrer-Policy`
- Hidden-file access restrictions

PHP version exposure is also disabled in the application image:

ini
expose_php = Off

## Database and Performance

The database indexes are aligned with the current query patterns.

Relevant indexes include:

text
tasks_user_id_created_at_index
category_task_category_id_index

The task listing query uses the composite `(user_id, created_at)` index for user-scoped ordering.

Database optimization was verified with `EXPLAIN`, including the expected use of the composite ordering index.

## Testing

Run the automated test suite with:

bash
php artisan test

The test suite covers:

- Authentication
- Authorization
- Validation
- Task and category operations
- API behavior
- Error handling
- Rate limiting
- Configuration-related behavior
- Query behavior

The CI test environment uses SQLite.

## Code Quality

Run Laravel Pint:

bash
./vendor/bin/pint --test

Run Larastan / PHPStan:

bash
./vendor/bin/phpstan analyse

## API Documentation

The project uses Scribe for generated API documentation.

Generate the documentation in a development environment:

bash
php artisan scribe:generate

The generated documentation is available under:

text
/docs

Scribe configuration is excluded from the production application image.

## Health Check

Laravel exposes the application health endpoint:

http
GET /up

Example:

bash
curl http://localhost:8000/up

The same endpoint is used by the production Nginx health check to verify that the Laravel upstream is available.

## CI

The repository uses GitHub Actions for automated quality checks.

The CI pipeline runs:

- Laravel Pint
- Larastan / PHPStan
- PHPUnit

## Operations and Troubleshooting

Check the complete stack:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 ps

View application logs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 logs app --tail=100

View queue worker logs:

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

Check failed queue jobs:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 exec app php artisan queue:failed

Check Laravel health:

bash
curl -i http://localhost:8000/up

Restart the production stack:

bash
docker compose \
 --env-file .env.production \
 -f docker-compose.production.yml \
 up -d

## Repository Structure

Relevant application directories include:

text
app/
├── Actions/
├── Enums/
├── Events/
├── Http/
├── Jobs/
├── Listeners/
├── Mail/
├── Models/
├── Policies/
├── Providers/
├── Queries/
└── Traits/

The task listing query logic is encapsulated in:

text
app/Queries/TaskQuery.php

Production Docker files are organized as:

text
Dockerfile
docker-compose.production.yml
docker/
└── nginx/
├── Dockerfile.production
└── conf.d/
└── app.conf

## License

This project is licensed under the MIT License.
