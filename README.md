# Task Manager API

A production-oriented RESTful API for managing tasks and categories, built with Laravel 13.

The project focuses on clean backend architecture, consistent API contracts, authentication and authorization, validation, database performance, automated testing, static analysis, and containerized deployment.

## Tech Stack

- **Laravel 13**
- **PHP 8.3**
- **MySQL 8.0**
- **SQLite** for automated tests and default local development
- **Laravel Sanctum**
- **Laravel Policies**
- **PHPUnit**
- **Laravel Pint**
- **Larastan / PHPStan**
- **Scribe**
- **Docker / Docker Compose**
- **Nginx**
- **PHP-FPM**

## Key Features

- Versioned REST API under `/api/v1`
- Token-based authentication with Laravel Sanctum
- Role-based authorization
- Task ownership protection through Policies
- Task and category management
- Form Request validation
- Consistent API Resources and JSON error responses
- Named rate limiters for authentication and API endpoints
- Database transactions for multi-step operations
- Query Object for task filtering and sorting
- Database indexes aligned with common query patterns
- Automated feature tests
- Static analysis with Larastan / PHPStan
- Code style enforcement with Laravel Pint
- API documentation with Scribe
- Production-oriented Docker deployment
- Dedicated queue worker
- Dedicated database migration service
- Container health checks
- Nginx security hardening

## Architecture

The application follows a layered Laravel architecture with clear separation of responsibilities.

```text
HTTP Request
     │
     ▼
Controllers
     │
     ├── Form Requests
     │
     ├── Policies
     │
     └── Actions / Queries
              │
              ▼
            Models
              │
              ▼
           Database
```

### Main Responsibilities

- **Controllers** — handle HTTP requests and responses
- **Form Requests** — validate incoming request data
- **Actions** — encapsulate business operations and transactions
- **Policies** — enforce authorization and resource ownership
- **Enums** — provide type-safe status and role values
- **Query Objects** — encapsulate task listing, filtering, searching, sorting, and pagination
- **API Resources** — provide consistent JSON responses
- **Observers** — handle model-level side effects such as cache invalidation
- **Events / Listeners / Jobs** — coordinate asynchronous workflows
- **Rate Limiting** — protect authentication and API endpoints

## Design Decisions & Known Limitations

This project intentionally favors simple, maintainable solutions over premature complexity.

### Design Decisions

- **Policy-based authorization**
  Authorization is implemented through Laravel Policies rather than custom permission gates. This keeps authorization rules close to the models they protect and provides a consistent approach across resources.

- **Observer-based cache invalidation**
  Category cache invalidation is handled by an Eloquent Observer instead of individual controllers. This keeps cache consistency independent from the HTTP layer and ensures the same behavior when categories are modified from other application contexts.

- **Query Object for task listing**
  Task filtering, searching, sorting, and pagination are encapsulated in `TaskQuery`. This keeps controllers focused on HTTP concerns and makes the query logic easier to test and evolve.

- **Database transactions for multi-step operations**
  Operations that modify multiple related records are wrapped in transactions so partial failures do not leave inconsistent data.

- **Stateless bearer-token authentication**
  The API uses Laravel Sanctum personal access tokens. The current logout behavior revokes only the token used by the current request, allowing other active sessions or devices to remain authenticated.

### Known Limitations

- **Search uses SQL `LIKE`**
  Task search currently performs substring matching against the title and description using `LIKE '%term%'`. This is intentionally simple and appropriate for the current project scope, but it is not a full-text search solution and may become less efficient as the dataset grows.

- **Token lifecycle is intentionally simple**
  Issued Sanctum tokens expire after 24 hours. The application does not currently enforce a maximum number of active tokens per user or provide device/session management. A future production system could add token rotation, explicit session management, or per-user token limits depending on requirements.

- **SQLite for tests and MySQL in production**
  Automated tests use SQLite for speed and isolation, while the production environment uses MySQL 8.0. This keeps the test suite lightweight, but database-specific behavior should still be verified against MySQL before major production changes.

- **Single application instance**
  The current Docker deployment is designed as a production-oriented single-host setup rather than a horizontally scaled architecture. Running multiple application instances would require additional infrastructure and considerations such as shared cache, centralized logs, and load balancing.

## API

All API routes are versioned under:

```text
/api/v1
```

### Authentication

The API uses Laravel Sanctum personal access tokens.

Protected endpoints require a bearer token:

```http
Authorization: Bearer <token>
```

### Authentication Endpoints

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

Task listing supports:

- Status filtering
- Category filtering
- Title and description search
- Sorting
- Pagination

Example:

```http
GET /api/v1/tasks?status=pending
```

Supported query parameters:

```text
status
category_id
search
sort_by
sort_direction
per_page
```

Supported `sort_by` values:

```text
due_date
created_at
title
```

Supported `sort_direction` values:

```text
asc
desc
```

### Categories

| Method | Endpoint                  | Description                    |
| ------ | ------------------------- | ------------------------------ |
| GET    | `/api/v1/categories`      | List categories                |
| GET    | `/api/v1/categories/{id}` | Get a category                 |
| POST   | `/api/v1/categories`      | Create a category (admin only) |
| PUT    | `/api/v1/categories/{id}` | Update a category (admin only) |
| DELETE | `/api/v1/categories/{id}` | Delete a category (admin only) |

### Roles

| Role    | Permissions                               |
| ------- | ----------------------------------------- |
| `user`  | Manage own tasks and browse categories    |
| `admin` | User permissions plus category management |

## API Error Contract

API errors follow a consistent JSON structure.

Example validation response:

```json
{
    "success": false,
    "message": "Validation Error",
    "errors": {
        "status": ["The selected status is invalid."]
    }
}
```

Common HTTP responses:

| Status | Meaning               |
| ------ | --------------------- |
| `401`  | Unauthenticated       |
| `403`  | Unauthorized          |
| `404`  | Resource not found    |
| `422`  | Validation error      |
| `429`  | Too many requests     |
| `500`  | Internal server error |

When debug mode is disabled, internal exception details are not exposed to API clients.

## Authentication & Authorization

Authentication is implemented using Laravel Sanctum personal access tokens.

Authorization is enforced through Laravel Policies.

### Task Ownership

Users can only view, update, and delete their own tasks.

Attempting to access another user's task returns:

```http
403 Forbidden
```

### Category Management

Category creation, update, and deletion are restricted to users with the `admin` role.

## Database Design

The application uses:

- MySQL 8.0 for the production Docker environment
- SQLite for automated tests and default local development

The database includes:

```text
users
tasks
categories
category_task
personal_access_tokens
jobs
failed_jobs
cache
sessions
```

Tasks are owned by users and can be associated with multiple categories.

Foreign keys and cascade rules are used to maintain referential integrity.

## Database Performance

The task listing workload is supported by indexes designed around the application's common query patterns.

The project includes a dedicated migration that replaces unnecessary task indexes with a composite index on:

```text
(user_id, created_at)
```

and adds an index on:

```text
category_task.category_id
```

The task listing query uses the composite user/date index for the default ordering.

## Transactions

Multi-step task operations are handled inside database transactions.

For example, task creation and category attachment are performed atomically so a failure while attaching categories does not leave a partially created task.

## Queue Processing

The application uses Laravel's database queue driver.

User registration triggers:

```text
UserRegistered
      │
      ▼
SendWelcomeEmailListener
      │
      ▼
SendWelcomeEmailJob
      │
      ▼
WelcomeEmail
```

The production environment runs the queue worker as a dedicated Docker service.

The welcome email job is configured with retries and backoff:

```text
tries:   3
backoff: 10s, 30s, 60s
```

## Rate Limiting

Named rate limiters are configured for authentication and API endpoints.

```text
auth-login      5 requests/minute per IP
auth-register   5 requests/minute per IP
api             60 requests/minute per authenticated user
                or IP for unauthenticated traffic
```

Rate-limit failures use the same API error contract.

## CORS

The API uses an explicit allow-list for CORS origins.

Configuration is controlled through:

```text
CORS_ALLOWED_ORIGINS
```

Credentials support is disabled because the API uses bearer-token authentication.

## Testing

Run the automated test suite:

```bash
php artisan test
```

The test suite covers:

- Authentication
- Authorization
- Task ownership
- Validation
- Task operations
- Category operations
- API versioning
- Error handling
- Rate limiting
- CORS
- Token expiration
- Filtering and search
- Sorting and pagination
- Database transaction behavior
- Mass-assignment protection
- Category cache behavior

The current suite contains:

```text
78 tests
231 assertions
```

## Code Quality

Run Laravel Pint:

```bash
./vendor/bin/pint --test
```

Run Larastan / PHPStan:

```bash
./vendor/bin/phpstan analyse
```

Validate Composer configuration:

```bash
composer validate --no-check-publish
```

The GitHub Actions CI pipeline runs:

```text
Laravel Pint
Larastan / PHPStan
PHPUnit
```

The `main` branch requires the `ci` status check before merging pull requests.

## API Documentation

The project uses Scribe for API documentation.

Generate the documentation locally:

```bash
php artisan scribe:generate
```

Generated documentation is available under:

```text
/docs
```

## Quick Start

### Requirements

- PHP 8.3+
- Composer 2+
- SQLite or MySQL

### Installation

```bash
git clone https://github.com/AhmadAlzaza/task-manager-api.git

cd task-manager-api

composer install

cp .env.example .env

php artisan key:generate

touch database/database.sqlite

php artisan migrate

php artisan db:seed

php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

The default `.env.example` configuration uses SQLite for local development.

### Admin Seeder

The admin seeder uses:

```env
ADMIN_NAME=Admin
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=
```

Set `ADMIN_PASSWORD` before running:

```bash
php artisan db:seed
```

In production, `ADMIN_PASSWORD` is required.

## Production Deployment

The repository includes a production-oriented Docker Compose setup with:

```text
Nginx
PHP-FPM
MySQL 8.0
Laravel queue worker
Dedicated migration service
Persistent database storage
Application health checks
Web health checks
Production logging
```

For the complete production deployment procedure, environment configuration, verification steps, queue operations, logging, and troubleshooting:

See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

## Repository Structure

```text
app/
├── Actions/
├── Enums/
├── Events/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Mail/
├── Models/
├── Observers/
├── Policies/
├── Providers/
├── Queries/
└── Traits/

database/
├── factories/
├── migrations/
└── seeders/

docker/
└── nginx/
    ├── Dockerfile.production
    └── conf.d/
        └── app.conf

docs/
└── DEPLOYMENT.md

tests/
└── Feature/

Dockerfile
docker-compose.production.yml
```

The main task listing query logic is encapsulated in:

```text
app/Queries/TaskQuery.php
```

The production application image is built from:

```text
Dockerfile
```

The Nginx image is defined by:

```text
docker/nginx/Dockerfile.production
```

## Project Status

This project is intentionally focused on backend engineering fundamentals rather than feature volume.

The current implementation demonstrates:

```text
REST API design
Authentication
Authorization
Validation
Database design
Query optimization
Transactions
Cache management
Queue processing
Rate limiting
CORS
Error handling
Automated testing
Static analysis
Containerization
Production-oriented deployment
CI quality gates
```
