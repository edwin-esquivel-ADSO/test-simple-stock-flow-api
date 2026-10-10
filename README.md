# test-simple-stock-flow-api

> **Prueba técnica · Ficha ADSO 3413974**
> Horario: de **9:00 a. m. a 3:00 p. m.** (15:00)

Este repositorio es el **backend** de *Simple Stock Flow*, en **PHP con Laravel**. **Empieza vacío a propósito**: se construye en el fork de cada aprendiz.

## Instrucciones

Cada aprendiz debe **crear el fork** de los seis repositorios del proyecto y **resolver el proyecto
con el spec planteado**.

1. Hacer fork, a su cuenta de GitHub, de cada repositorio de la tabla del final.
2. Leer el spec en [`test-simple-stock-flow-docs`](https://github.com/code-sena/test-simple-stock-flow-docs).
   Se entrega en dos versiones: `spec-python/` y `spec-.net/`.
3. Desarrollar en los forks.

## El reto se desarrolla con React y PHP (Laravel)

El spec está escrito para Python y para .NET, pero el reto **no** se hace en esos lenguajes:

| Capa | Tecnología del reto |
|---|---|
| Frontend | React |
| Backend | PHP con Laravel |

Lo que el spec define sobre el negocio —historias, criterios de aceptación, reglas, contrato de la
API, modelo de datos— se respeta. Lo que define sobre la tecnología se traduce a React y Laravel.

## La prueba no consiste en escribir el código

El propósito principal es ver la **capacidad de desempeño con SDD** (*Spec-Driven Development*,
desarrollo guiado por especificación): cómo se lee, se interpreta y se aplica una especificación
para llevarla a un stack distinto. El código es el medio, no el fin.

## Los seis repositorios

| Repositorio | Qué va ahí |
|---|---|
| [`test-simple-stock-flow-docs`](https://github.com/code-sena/test-simple-stock-flow-docs) | El spec: `spec-python/` y `spec-.net/` |
| [`test-simple-stock-flow-api`](https://github.com/code-sena/test-simple-stock-flow-api) | Backend en PHP (Laravel) |
| [`test-simple-stock-flow-app`](https://github.com/code-sena/test-simple-stock-flow-app) | Frontend en React |
| [`test-simple-stock-flow-page`](https://github.com/code-sena/test-simple-stock-flow-page) | Sitio público estático de presentación |
| [`test-simple-stock-flow-infra`](https://github.com/code-sena/test-simple-stock-flow-infra) | Contenedores, red, volúmenes y motor de base de datos vacío |
| [`test-simple-stock-flow-tool`](https://github.com/code-sena/test-simple-stock-flow-tool) | Utilidades: sembrador de datos de demostración |

---

# Documentación Técnica de Implementación — Nivel Senior

## 1. Ficha Técnica del Backend

- **Entorno de Ejecución:** PHP 8.2+
- **Framework Base:** Laravel 11.x
- **Motor de Base de Datos:** MySQL 8.4 LTS
- **Estilo Arquitectónico:** Arquitectura Onion (Cebolla) estricta en 4 anillos concéntricos + Composition Root
- **Manejo de Moneda:** Monomoneda (`COP`) con aritmética decimal de alta precisión (`brick/math` - `BigDecimal`)
- **Autenticación y Seguridad:** JWT con expiración estricta de 60 minutos y PBKDF2/Bcrypt para hashing
- **Contrato de API:** 15 endpoints REST conformes con RFC 7807 (`application/problem+json`)

---

## 2. Topología de la Arquitectura Onion

El backend aplica la **Inward Dependency Rule** (las dependencias apuntan exclusivamente hacia el centro). No existen fugas de dependencias del framework hacia el dominio ni hacia la aplicación.

```
                     ARQUITECTURA ONION (4 ANILLOS)
                     
         ┌────────────────────────────────────────────────────────┐
         │  Bootstrap (Composition Root)                          │
         │  app/Bootstrap/PortBindingsServiceProvider.php         │
         │  Vincula puertos (interfaces) con implementaciones     │
         └───────────────────────────┬────────────────────────────┘
        ┌────────────────────────────┴─────────────────────────────┐
        ▼                                                          ▼
┌──────────────────────────────┐            ┌──────────────────────────────┐
│  Presentation (Anillo 4)     │            │  Infrastructure (Anillo 3)   │
│  app/Presentation/           │            │  app/Infrastructure/         │
│  - Controllers HTTP          │            │  - Repositorios Eloquent     │
│  - Form Requests             │            │  - Mappers Dominio <-> DB    │
│  - JsonResources             │            │  - LaravelUnitOfWork (Tx)    │
│  PROHIBIDO: Importar Infra   │            │  - JWT & Password Hasher     │
└──────────────┬───────────────┘            └──────────────┬───────────────┘
               │                                           │
               │           Imports hacia adentro           │
               ▼                                           ▼
             ┌──────────────────────────────────────────────────┐
             │  Application (Anillo 2)                          │
             │  app/Application/                                │
             │  - Casos de Uso (Services)                       │
             │  - 5 Puertos Inbound (Entrada)                   │
             │  - 10 Puertos Outbound (Salida)                  │
             │  CERO dependencias de Laravel (Illuminate)       │
             └─────────────────────────┬────────────────────────┘
                                       │ Solo importa Domain
                                       ▼
             ┌──────────────────────────────────────────────────┐
             │  Domain (Anillo 1 - NÚCLEO)                      │
             │  app/Domain/                                     │
             │  - Agregados: Product, Sale, User                │
             │  - Entidades: SaleItem, Category                 │
             │  - Value Objects: Money, Quantity, Role, etc.    │
             │  PHP PURO. 100% independiente del framework.     │
             └──────────────────────────────────────────────────┘
```

### Reglas de Dependencia Inviolables
1. **Domain (Anillo 1):** PHP puro. `grep -R "Illuminate\\" app/Domain` arroja estrictamente **0 dependencias**.
2. **Application (Anillo 2):** Solo importa `Domain`. Orquesta la lógica del negocio mediante puertos salientes (interfaces).
3. **Infrastructure (Anillo 3):** Implementa las interfaces salientes interactuando con Eloquent, MySQL y el sistema de archivos.
4. **Presentation (Anillo 4):** Recibe solicitudes HTTP, valida formato sintáctico y delega a los casos de uso de `Application`.
5. **Bootstrap:** Único punto donde se ensamblan las implementaciones con los puertos salientes.

---

## 3. Matriz de Endpoints de la API (15 Endpoints Oficiales)

Todos los endpoints que devuelven JSON usan formato **camelCase** en el cuerpo y fechas en formato **ISO 8601 UTC** (`YYYY-MM-DDTHH:MM:SS+00:00`).

| # | Método | Endpoint | Rol Mínimo | Códigos HTTP | Descripción / Payload |
|---|---|---|---|---|---|
| **E-01** | `POST` | `/api/auth/login` | Público | `200`, `400`, `401` | Autenticación de credenciales; entrega `{ accessToken, expiresAt, username, role }`. |
| **E-02** | `POST` | `/api/auth/register` | `admin` | `201`, `400`, `401`, `403`, `422` | Registro exclusivo de nuevos usuarios con rol `seller`. Cuerpo de respuesta vacío. |
| **E-03** | `GET` | `/api/products` | Autenticado | `200`, `400`, `401` | Catálogo paginado de productos activos (`page`, `size`, `categoryId`). |
| **E-04** | `GET` | `/api/products/{id}` | Autenticado | `200`, `401`, `404` | Detalle de un producto individual (incluye productos con baja lógica). |
| **E-05** | `POST` | `/api/products` | `admin` | `201`, `400`, `401`, `403`, `422` | Creación de producto (`name`, `price`, `stock`, `categoryId`). |
| **E-06** | `PUT` | `/api/products/{id}` | `admin` | `200`, `400`, `401`, `403`, `404`, `422` | Actualización de atributos de un producto activo. |
| **E-07** | `DELETE` | `/api/products/{id}` | `admin` | `204`, `401`, `403`, `404` | Baja lógica del producto (`deleted_at`); nunca borrado físico. Cuerpo vacío. |
| **E-08** | `POST` | `/api/products/{id}/image`| `admin` | `200`, `400`, `401`, `403`, `404`, `422` | Carga de foto (`multipart/form-data`, campo `image`, máx 5 MB, JPEG/PNG/WebP). |
| **E-09** | `GET` | `/api/categories` | Autenticado | `200`, `401` | Lista las 5 categorías oficiales sembradas en el sistema. |
| **E-10** | `POST` | `/api/sales` | Autenticado | `201`, `400`, `401`, `409`, `422` | Registro transaccional de venta con descuento de stock y congelamiento histórico. |
| **E-11** | `GET` | `/api/sales` | Autenticado | `200`, `400`, `401`, `422` | Lista paginada de ventas en rango obligatorio `?from=...&to=...`. |
| **E-12** | `GET` | `/api/sales/{id}` | Autenticado | `200`, `401`, `404` | Detalle completo de una venta con sus líneas inmutables congeladas. |
| **E-13** | `GET` | `/api/reports/sales` | Autenticado | `200`, `400`, `401`, `422` | Reporte consolidado financiero en rango `from <= sold_at < to` con totales derivados. |
| **E-14** | `GET` | `/health` | Público | `200` | Liveness probe del servicio; responde `{"status":"ok"}`. |
| **E-15** | `GET` | `/media/{key}` | Público | `200`, `404` | Entrega binaria directa de imágenes con su cabecera `Content-Type`. |

### Convención de Errores
- **422 / 409 / 500:** Formato RFC 7807 `application/problem+json` con campos `title`, `status` y `detail` en español.
- **400:** Formato de error sintáctico con diccionario `errors`.
- **401 / 403 / 404 / 405:** Respuesta con cuerpo **completamente vacío (`Content-Length: 0`)** y cabeceras estándar (`WWW-Authenticate`, `Allow`).

---

## 4. Base de Datos y Modelo de Persistencia (MySQL 8.4)

El esquema relacional cuenta con 5 tablas y **9 Check Constraints manuales** para blindar las invariantes de negocio a nivel físico de base de datos:

| Tabla | Restricción DDL | Condición SQL / Propósito |
|---|---|---|
| `category` | `ck_category_name_not_blank` | `CHAR_LENGTH(TRIM(name)) > 0` |
| `product` | `ck_product_name_not_blank` | `CHAR_LENGTH(TRIM(name)) > 0` |
| `product` | `ck_product_price_positive` | `price > 0` (RN-02) |
| `product` | `ck_product_stock_non_negative`| `stock >= 0` (RN-01, previene sobreventa física) |
| `user` | `ck_user_username_normalized` | `CHAR_LENGTH(username) > 0 AND CAST(username AS BINARY) = CAST(LOWER(TRIM(username)) AS BINARY)` (RN-10) |
| `user` | `ck_user_role_allowed` | `role IN ('admin', 'seller')` (RN-11) |
| `user` | `ck_user_password_hash_not_blank`| `CHAR_LENGTH(password_hash) > 0` |
| `sale_item`| `ck_sale_item_quantity_positive` | `quantity > 0` (RN-03) |
| `sale_item`| `ck_sale_item_unit_price_positive`| `unit_price > 0` (RN-02) |

### Invariantes Clave:
- **Control de Concurrencia Optimista:** La tabla `product` cuenta con la columna entera `version`. Cada actualización atómica incrementa la versión. Si dos transacciones intentan vender simultáneamente la última unidad, una de ellas colisiona y genera un `409 Conflict`.
- **Inmutabilidad Histórica (RN-06):** La tabla `sale_item` almacena su propia copia de `product_name`, `category_name` y `unit_price`. Si el producto cambia de precio o nombre en el futuro, los balances contables pasados permanecen inalterados.
- **Baja Lógica (RN-08):** Los productos cuentan con `deleted_at`. Los productos vendidos tienen integridad referencial `RESTRICT`; al eliminarse, solo se marca la fecha de baja lógica.

---

## 5. Variables de Entorno (`.env`)

| Variable | Tipo | Valor Recomendado | Descripción |
|---|---|---|---|
| `APP_ENV` | string | `local` / `production` | Entorno de ejecución de la aplicación. |
| `APP_KEY` | string | `base64:...` | Clave de cifrado de la aplicación. |
| `APP_DEBUG` | boolean | `false` | Depuración (debe ser false en evaluación). |
| `APP_URL` | string | `http://localhost:8000` | URL base del backend. |
| `DB_CONNECTION` | string | `mysql` | Driver de conexión a base de datos. |
| `DB_HOST` | string | `db` (Docker) / `127.0.0.1` | Host del motor MySQL 8.4. |
| `DB_PORT` | integer | `3306` | Puerto de conexión a MySQL. |
| `DB_DATABASE` | string | `stockflow` | Nombre de la base de datos. |
| `DB_USERNAME` | string | `stockflow` | Usuario del motor de base de datos. |
| `DB_PASSWORD` | string | `stockflow` | Contraseña del usuario de base de datos. |
| `JWT_SECRET` | string | Clave > 32 caracteres | Secreto para firma simétrica HMAC-SHA256 de tokens JWT. |
| `JWT_TTL` | integer | `60` | Tiempo de vida del token JWT en minutos (fijo en 60 min). |
| `MEDIA_STORAGE_PATH` | string | `/var/lib/simple-stock-flow/media` | Ruta del volumen Docker para almacenamiento de fotos. |

---

## 6. Puesta en Marcha y Ejecución

### Opción A: Entorno Local (Bare-Metal)
```bash
# 1. Instalar dependencias
composer install

# 2. Configurar variables de entorno
cp .env.example .env
php artisan key:generate

# 3. Ejecutar migraciones del esquema y categorías base
php artisan migrate

# 4. Sembrar el usuario administrador inicial
php artisan db:seed

# 5. Iniciar servidor de desarrollo
php artisan serve --port=8000
```

### Opción B: Entorno Contenedorizado (Docker)
El servicio es orquestado por el repositorio central `test-simple-stock-flow-infra`:
```bash
cd ../test-simple-stock-flow-infra
docker compose up -d api db
```

---

## 7. Ejecución de la Suite de Pruebas

Para garantizar que todas las invariantes de negocio y reglas de arquitectura se cumplan:

```bash
# Ejecutar la suite completa de pruebas automatizadas
php artisan test
```

### Resultados de Verificación:
- **`DomainRulesTest`:** Valida que el stock no sea negativo, la precisión de `Money` con `BigDecimal`, rechazo de ventas vacías o con productos duplicados, normalización de usuarios y conjuntos cerrados de roles.
- **`ArchitectureTest`:** Valida la pureza absoluta del núcleo (`Domain` sin dependencias de `Illuminate`).
