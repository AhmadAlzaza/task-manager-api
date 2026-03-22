# Task Manager API

A RESTful API for managing tasks and categories built with Laravel 12.

## Tech Stack

- **Laravel** 12
- **PHP** 8.2
- **MySQL**
- **Laravel Sanctum** (Authentication)
- **PHPUnit** (Testing)

## Installation

1. Clone the repository

```bash
git clone https://github.com/AhmadAlzaza/task-manager-api.git
cd task-manager-api
```

2. Install dependencies

```bash
composer install
```

3. Copy environment file

```bash
cp .env.example .env
```

4. Generate application key

```bash
php artisan key:generate
```

5. Configure database in `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager_api
DB_USERNAME=root
DB_PASSWORD=
```

6. Run migrations

```bash
php artisan migrate
```

7. Start the server

```bash
php artisan serve
```

## API Endpoints

### Auth

| Method | Endpoint      | Description         |
| ------ | ------------- | ------------------- |
| POST   | /api/register | Register a new user |
| POST   | /api/login    | Login               |
| POST   | /api/logout   | Logout              |

### Tasks

| Method | Endpoint        | Description          |
| ------ | --------------- | -------------------- |
| GET    | /api/tasks      | Get all tasks (auth) |
| POST   | /api/tasks      | Create a task (auth) |
| GET    | /api/tasks/{id} | Get a task (auth)    |
| PUT    | /api/tasks/{id} | Update a task (auth) |
| DELETE | /api/tasks/{id} | Delete a task (auth) |

> Filter by status: `/api/tasks?status=pending`

### Categories

| Method | Endpoint             | Description               |
| ------ | -------------------- | ------------------------- |
| GET    | /api/categories      | Get all categories (auth) |
| POST   | /api/categories      | Create a category (auth)  |
| GET    | /api/categories/{id} | Get a category (auth)     |
| PUT    | /api/categories/{id} | Update a category (auth)  |
| DELETE | /api/categories/{id} | Delete a category (auth)  |

## Testing

```bash
php artisan test
```

**15 tests, 34 assertions — all passing ✅**

## Postman Collection

Import the collection from: `task-manager-api.postman_collection.json`
