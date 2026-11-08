# M0616-Nurses: Hospital Nursing Staff REST API

## Description

The goal of the project is to build a REST API with Symfony to manage the nursing staff of a hospital. It replaces manual record keeping with a centralised system that lets users create, view, update and delete nurses, and check their access through a login.

## Tech stack

- PHP >= 8.2 (Composer platform set to PHP 8.2.0)
- Symfony 7.4 (`framework-bundle`, `console`, `dotenv`, `runtime`, `validator`, `yaml`)
- Doctrine ORM 3 with Doctrine Migrations
- MariaDB (or MySQL)

## Installation

### Requirements

- PHP 8.2 or higher, with the `ctype`, `iconv` and `pdo_mysql` extensions
- [Composer](https://getcomposer.org/)
- [MariaDB](https://mariadb.org/download/) (or MySQL) running locally
- [Symfony CLI](https://symfony.com/download) (recommended, not required)

### Clone the repository

```bash
git clone https://github.com/Gerpaes/M0616-Nurses.git
cd M0616-Nurses
```

### Install dependencies

```bash
composer install
```

### Configure the environment

The committed `.env` file only holds default values and must not be edited with
real credentials. Put your local configuration in `.env.dev.local`, which is
ignored by Git.

The project needs two variables:

- `APP_SECRET`: a random secret for the `dev` environment.
- `DATABASE_URL`: the connection to your local database.

Generate a random `APP_SECRET`:

```bash
echo "APP_SECRET=$(php -r 'echo bin2hex(random_bytes(16));')" > .env.dev.local
```

Add the database connection, replacing `user`, `password` and `serverVersion`
with your own values (check your server version with `mariadb --version`):

```bash
echo 'DATABASE_URL="mysql://user:password@127.0.0.1:3306/nurses?serverVersion=11.8.6-MariaDB&charset=utf8mb4"' >> .env.dev.local
```

If the password contains special characters (`@`, `:`, `/`, `#`, `%`, `?`), they
must be URL-encoded (for example, `@` becomes `%40`).

### Set up the database

Create the database and run the migrations:

```bash
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate
```

Without the Symfony CLI:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

The migration creates the `nurse` table, with unique indexes on `internal_id`,
`license_number`, `username` and `email`.

Sample nurse data is available in `data/nurses.json`.

## Usage

### Running the API

With the Symfony CLI:

```bash
symfony serve -d
```

Without the Symfony CLI:

```bash
php -S localhost:8000 -t public
```

To list all registered routes:

```bash
symfony console debug:router
```

Without the Symfony CLI:

```bash
php bin/console debug:router
```

### Endpoints

All endpoints receive and return JSON. `{id}` only accepts digits.

| Method | Route | Description | Response codes |
| ------ | ----- | ----------- | -------------- |
| GET | `/nurse` | List all nurses | `200` |
| GET | `/nurse/index` | List all nurses (same output as `/nurse`) | `200` |
| GET | `/nurse/{id}` | Get a nurse by id | `200` found, `404` not found |
| GET | `/nurse/name/{name}` | Find a nurse by exact first name (returns the first match) | `200` found, `404` not found |
| POST | `/nurse/new` | Create a nurse (body fields in snake_case; the password is stored hashed) | `201` created, `400` invalid JSON, `409` nurse already exists, `422` validation errors |
| PUT | `/nurse/{id}` | Update a nurse (body fields in camelCase; `internalId` cannot be changed) | `200` updated, `400` invalid data or validation errors, `404` not found |
| DELETE | `/nurse/{id}` | Delete a nurse | `200` deleted, `404` not found |
| POST | `/nurse/login` | Check a nurse's credentials | `200` valid, `400` missing user or password, `401` invalid credentials |

Validation rules for nurse fields:

- When creating a nurse, all fields are required. All values must be strings.
- `first_name` / `firstName` and `last_name` / `lastName`: letters and spaces only.
- `email`: a valid email address.
- `shift`: one of `morning`, `afternoon` or `night`.
- `internal_id`, `license_number`, `username` and `email` must be unique.

### Postman collection

A Postman collection with requests for the API is available in
`postman/M0616-Nurses.postman_collection.json`.

## Sequence diagram

The diagram follows the sequence diagram of the project brief. The flow is the same with a local database (localhost) or a centralised one (server).

```mermaid
sequenceDiagram
    participant C as Browser / HTTP client
    participant N as NurseController
    participant D as Database

    Note over C,D: Login
    C->>N: POST /nurse/login
    N->>D: login(user, pw)
    D-->>N: nurse found?
    alt user and password exist
        N-->>C: JSON true
    else user or password do not exist
        N-->>C: JSON false
    end

    Note over C,D: List nurses
    C->>N: GET /nurse/index
    N->>D: getAll()
    D-->>N: List<Nurse>
    N-->>C: JSON List<Nurse>

    Note over C,D: List nurse by name
    C->>N: GET /nurse/name/{name}
    N->>D: findByName(name)
    D-->>N: Nurse
    N-->>C: JSON Nurse
```

## Tests

To be completed after #36 and #37.

## Project structure

```
src/
  Controller/   # HTTP controllers
  Entity/       # Doctrine entities
  Repository/   # Doctrine repositories
  Kernel.php
migrations/     # Doctrine migrations
tests/
  Controller/   # Functional tests for the controllers
data/
  nurses.json   # Sample nurse data
postman/        # Postman collection
```

## Debugging

- Logs: `var/log/dev.log`
- Project overview: `symfony console about`
