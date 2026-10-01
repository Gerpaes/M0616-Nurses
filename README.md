# M0616-Nurses

API backend construida con Symfony para la gestión de personal de enfermería.

## Stack

- PHP >= 8.2
- Symfony 7.4 (`framework-bundle`, `console`, `dotenv`, `routing`, `runtime`, `yaml`)
- Symfony Flex para la gestión de recetas/paquetes

## Requisitos

- PHP 8.2 o superior, con las extensiones `ctype` e `iconv`
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download) (recomendado, no obligatorio)

## Instalación

```bash
git clone git@github.com:Gerpaes/M0616-Nurses.git
cd M0616-Nurses
composer install
```

Si necesitás sobreescribir alguna variable de entorno, creá `.env.local` (no se
versiona) en vez de editar `.env`.

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
  Kernel.php
tests/
  Controller/   # Tests funcionales de los controladores
data/
  nurses.json   # Datos de prueba
```

## Depuración

- Logs: `var/log/dev.log`
- Web profiler: `/_profiler` (entorno `dev`)
- Estado general del proyecto: `symfony console about`
