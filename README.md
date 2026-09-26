# Task Manager API

A RESTful API for managing tasks and categories, built with Laravel 13.

## Tech Stack

- **Laravel 13**
- **PHP 8.3**
- **MySQL 8** for Docker-based environments
- **SQLite** for automated tests and default local development
- **Laravel Sanctum** for API authentication
- **Laravel Policies** for authorization
- **PHPUnit** for automated testing
- **Laravel Pint** for code style
- **Larastan / PHPStan** for static analysis
- **Scribe** for API documentation

## Architecture

The project follows a layered Laravel architecture focused on clear responsibilities and maintainability.

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

Task listing supports status filtering:

text
GET /api/v1/tasks?status=pending

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

Example:

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

- `401` — Unauthenticated
- `403` — Unauthorized
- `404` — Resource not found
- `422` — Validation error
- `429` — Too many requests
- `500` — Internal server error

When debug mode is disabled, production API responses do not expose internal exception messages.

## Environment Configuration

Copy `.env.example` to `.env` and configure the environment for the target runtime.

The repository's `.env.example` is a **development-oriented template**. It currently uses SQLite, database-backed sessions, database-backed cache, database queues, and log mail delivery.

Example:

bash
cp .env.example .env

Generate the application key:

bash
php artisan key:generate

The admin seeder uses the following environment variables:

env
ADMIN_NAME=Admin
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=

`ADMIN_PASSWORD` must be configured when running the admin seeder in production.

Production environments should provide their own environment configuration, including:

env
APP_ENV=production
APP_DEBUG=false

Production secrets such as `APP_KEY` and `ADMIN_PASSWORD` must never be committed to the repository.

## Local Installation

### Requirements

- PHP 8.3+
- Composer 2+
- SQLite or MySQL
- Node.js/npm if frontend asset tooling is required

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

## Docker

The repository includes a Docker Compose environment with:

- PHP 8.3 FPM
- Nginx
- MySQL 8.0

The current Docker Compose setup is intended for **local development and integration testing**. It is **not the final production deployment architecture**.

### Docker Services

| Service | Purpose                     | Port    |
| ------- | --------------------------- | ------- |
| `app`   | PHP-FPM Laravel application | `9000`  |
| `web`   | Nginx web server            | `8000`  |
| `db`    | MySQL 8 database            | `33306` |

Inside the Docker network, the database service is available as:

text
db:3306

### Docker Setup

Make sure Composer dependencies are installed before starting the current Docker environment:

bash
composer install

Build and start the containers:

bash
docker compose up -d --build

For the Docker environment, configure the application database connection to use the MySQL service:

env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=root
DB_PASSWORD=root

Run migrations inside the application container:

bash
docker compose exec app php artisan migrate --force

Seed the admin account:

bash
docker compose exec app php artisan db:seed --force

The current Docker Compose setup uses the database queue driver. Start a queue worker in a separate terminal:

bash
docker compose exec app php artisan queue:work --tries=3

The API is available through Nginx at:

text
http://localhost:8000

Laravel's health endpoint is available at:

text
http://localhost:8000/up

## Queue Processing

The application uses the database queue driver:

env
QUEUE_CONNECTION=database

Queued work includes the welcome email flow.

A queue worker must therefore be running in environments where queued jobs are expected to be processed.

For local development:

bash
php artisan queue:work --tries=3

For Docker:

bash
docker compose exec app php artisan queue:work --tries=3

## Database and Performance

The project uses database indexes aligned with current query patterns, including:

text
tasks_user_id_created_at_index
tasks_user_id_status_index
category_task_category_id_index

The task listing query uses the composite `(user_id, created_at)` index for user-scoped ordering.

The current database optimization was verified using `EXPLAIN`, including the expected use of the composite ordering index.

## Testing

Run the automated test suite with:

bash
php artisan test

The test suite covers authentication, authorization, validation, API behavior, task and category operations, error handling, rate limiting, configuration-related behavior, and query behavior.

## Code Quality

Run Laravel Pint:

bash
./vendor/bin/pint --test

Run Larastan / PHPStan:

bash
./vendor/bin/phpstan analyse

## API Documentation

The project uses Scribe for generated API documentation.

Generate the documentation with:

bash
php artisan scribe:generate

The generated documentation is available under:

text
/docs

## Health Check

Laravel exposes the application health endpoint:

text
GET /up

This can be used as a basic application health check.

## CI

The repository uses GitHub Actions for automated quality checks.

The current CI pipeline runs:

- Laravel Pint
- Larastan / PHPStan
- PHPUnit

The CI test environment uses SQLite.

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

The task listing query logic is currently encapsulated in:

text
app/Queries/TaskQuery.php

## Deployment Status

The repository currently contains Docker-based deployment groundwork, including:

- PHP-FPM application container
- Nginx web container
- MySQL container
- Persistent MySQL volume
- Laravel health endpoint
- Database-backed queue infrastructure

Further production deployment hardening and deployment discipline are handled separately from the current Docker development setup.

## License

This project is licensed under the MIT License.
