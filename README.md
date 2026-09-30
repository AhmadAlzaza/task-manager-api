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

- Versioned API under `/api/v1`
- Token-based authentication with Laravel Sanctum
- Role-based authorization with Policies
- Task and category management
- Request validation with Form Requests
- Consistent API Resources and error responses
- Named rate limiters for authentication and API endpoints
- Database transactions for multi-step operations
- Query Object for task listing and filtering
- Database indexing aligned with common query patterns
- Automated tests with PHPUnit
- Static analysis with Larastan / PHPStan
- Code style checks with Laravel Pint
- API documentation with Scribe
- Production-oriented Docker deployment
- Dedicated queue worker and migration service
- Container and application health checks

## Architecture

The application follows a layered Laravel architecture with clear separation of responsibilities.

- **Controllers** — handle HTTP requests and responses
- **Actions** — encapsulate business operations
- **Form Requests** — validate incoming requests
- **Policies** — enforce authorization rules
- **Enums** — provide type-safe status and role values
- **Query Objects** — encapsulate complex query logic
- **API Resources** — provide consistent JSON responses
- **Jobs / Events / Listeners** — coordinate application workflows
- **Database Transactions** — maintain consistency across multi-step operations
- **Rate Limiting** — protect authentication and API endpoints

## API

All API routes are versioned under:

text
/api/v1

## Authentication

The API uses Laravel Sanctum personal access tokens.

Protected endpoints use bearer authentication:

http
Authorization: Bearer <token>

### Authentication Endpoints

| Method | Endpoint           | Description                            |
| ------ | ------------------ | -------------------------------------- |
| POST   | `/api/v1/register` | Register a new user                    |
| POST   | `/api/v1/login`    | Authenticate and obtain a bearer token |
| POST   | `/api/v1/logout`   | Revoke the current token               |

## Tasks

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

Supported query parameters:

text
status
category_id
search
sort_by
sort_direction
per_page

## Categories

| Method | Endpoint                  | Description                    |
| ------ | ------------------------- | ------------------------------ |
| GET    | `/api/v1/categories`      | List categories                |
| GET    | `/api/v1/categories/{id}` | Get a category                 |
| POST   | `/api/v1/categories`      | Create a category (admin only) |
| PUT    | `/api/v1/categories/{id}` | Update a category (admin only) |
| DELETE | `/api/v1/categories/{id}` | Delete a category (admin only) |

## Roles

| Role    | Permissions                               |
| ------- | ----------------------------------------- |
| `user`  | Manage own tasks and browse categories    |
| `admin` | User permissions plus category management |

## API Error Contract

API errors follow a consistent JSON structure.

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

Common HTTP responses:

| Status | Meaning               |
| ------ | --------------------- |
| `401`  | Unauthenticated       |
| `403`  | Unauthorized          |
| `404`  | Resource not found    |
| `422`  | Validation error      |
| `429`  | Too many requests     |
| `500`  | Internal server error |

Production responses do not expose internal exception details when debug mode is disabled.

## Quick Start

### Requirements

- PHP 8.3+
- Composer 2+
- SQLite or MySQL

### Installation

bash
git clone https://github.com/AhmadAlzaza/task-manager-api.git

cd task-manager-api

composer install

cp .env.example .env

php artisan key:generate

touch database/database.sqlite

php artisan migrate

php artisan db:seed

php artisan serve

The API will be available at:

text
http://127.0.0.1:8000

The default `.env.example` configuration uses SQLite for local development.

The admin seeder requires:

env
ADMIN_NAME=Admin
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=

Set `ADMIN_PASSWORD` before running the seeder.

## Testing

Run the automated test suite:

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
- Configuration behavior
- Query behavior

The CI test environment uses SQLite.

## Code Quality

Run Laravel Pint:

bash
./vendor/bin/pint --test

Run Larastan / PHPStan:

bash
./vendor/bin/phpstan analyse

The repository uses GitHub Actions to run:

- Laravel Pint
- Larastan / PHPStan
- PHPUnit

## API Documentation

The project uses Scribe for API documentation.

Generate documentation locally:

bash
php artisan scribe:generate

Generated documentation is available under:

text
/docs

## Production Deployment

The project includes a production-oriented Docker setup with:

- PHP 8.3 FPM
- Nginx
- MySQL 8.0
- Dedicated Laravel queue worker
- Dedicated migration service
- Persistent database storage
- Application and web health checks
- Docker-based logging
- Production error handling

For the complete deployment procedure, verification steps, queue operations, logging, and troubleshooting:

See:

text
docs/DEPLOYMENT.md

## Repository Structure

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

Production Docker files:

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
