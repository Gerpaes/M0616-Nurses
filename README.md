# M0616-Nurses

API backend construida con Symfony para la gestión de personal de enfermería.

## Stack

- PHP >= 8.2
- Symfony 7.4 (`framework-bundle`, `console`, `dotenv`, `routing`, `runtime`, `yaml`)
- Symfony Flex para la gestión de recetas/paquetes
- Doctrine ORM 3 (`doctrine-bundle`, `doctrine-migrations-bundle`) sobre MariaDB

## Requisitos

- PHP 8.2 o superior, con las extensiones `ctype`, `iconv` y `pdo_mysql`
- [Composer](https://getcomposer.org/)
- [MariaDB](https://mariadb.org/download/) (o MySQL) corriendo en local
- [Symfony CLI](https://symfony.com/download) (recomendado, no obligatorio)

## Instalación

```bash
git clone git@github.com:Gerpaes/M0616-Nurses.git
cd M0616-Nurses
composer install
```

El `APP_SECRET` de desarrollo no se versiona. Creá tu propio `.env.dev.local`
(ignorado por git) con un secret aleatorio:

```bash
echo "APP_SECRET=$(php -r 'echo bin2hex(random_bytes(16));')" > .env.dev.local
```

Si necesitás sobreescribir alguna variable de entorno, creá `.env.local` (no se
versiona) en vez de editar `.env`.

## Base de datos

El `DATABASE_URL` de `.env` es solo un ejemplo: las credenciales reales tampoco
se versionan. Creá un usuario de MariaDB para el proyecto, con permisos sobre la
base `nurses` (cambiá `TU_PASSWORD` por una contraseña propia):

```bash
sudo mariadb -e "CREATE USER 'nurses'@'localhost' IDENTIFIED BY 'TU_PASSWORD'; GRANT ALL PRIVILEGES ON nurses.* TO 'nurses'@'localhost'; FLUSH PRIVILEGES;"
```

Agregá la conexión a tu `.env.dev.local`, con esa misma contraseña y la versión
de tu servidor (`mariadb --version`):

```bash
echo 'DATABASE_URL="mysql://nurses:TU_PASSWORD@127.0.0.1:3306/nurses?serverVersion=11.8.6-MariaDB&charset=utf8mb4"' >> .env.dev.local
```

Si la contraseña tiene caracteres especiales (`@`, `:`, `/`, `#`, `%`, `?`) hay
que codificarlos en la URL (por ejemplo `@` pasa a ser `%40`).

Creá la base de datos y verificá la conexión:

```bash
symfony console doctrine:database:create
symfony console dbal:run-sql "SELECT 1"
```

Los cambios de esquema se hacen siempre con migraciones:

```bash
symfony console make:migration
symfony console doctrine:migrations:migrate
```

## Levantar el proyecto

Con Symfony CLI:

```bash
symfony serve -d
```

Sin Symfony CLI:

```bash
php -S localhost:8000 -t public
```

## Endpoints disponibles

| Método | Ruta     | Descripción                       |
| ------ | -------- | ---------------------------------- |
| GET    | `/nurse` | Endpoint de prueba del `NurseController` |

Para ver todas las rutas registradas:

```bash
symfony console debug:router
```

## Tests

El proyecto no trae instalado `symfony/test-pack` por defecto. Para ejecutar
los tests (`tests/`, basados en `WebTestCase`) hace falta instalarlo primero:

```bash
composer require --dev symfony/test-pack
php bin/phpunit
```

## Estructura del proyecto

```
src/
  Controller/   # Controladores HTTP
  Entity/       # Entidades de Doctrine
  Repository/   # Repositorios de Doctrine
  Kernel.php
migrations/     # Migraciones de Doctrine
tests/
  Controller/   # Tests funcionales de los controladores
data/
  nurses.json   # Datos de prueba
```

## Depuración

- Logs: `var/log/dev.log`
- Web profiler: `/_profiler` (entorno `dev`)
- Estado general del proyecto: `symfony console about`
