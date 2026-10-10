# Contrato de la API REST — test-simple-stock-flow-api

> **Especificación Formal de Endpoints, Payloads y Códigos HTTP**  
> **Prueba técnica · Ficha ADSO 3413974**  
> Basado en la especificación SDD de `test-simple-stock-flow-docs`.

---

## 1. Convenciones Globales

- **Formato:** JSON, UTF-8.
- **Nombres de Atributos:** `camelCase` estricto en petición y respuesta.
- **Fechas:** ISO 8601 UTC con desplazamiento explícito `+00:00` (ej: `2026-10-09T23:00:00.000000+00:00`).
- **Moneda:** Monomoneda fija `"COP"`.
- **Importes:** Números JSON con 2 decimales (`3500.50`).
- **Autenticación:** Header `Authorization: Bearer <jwt>`. Token con vigencia fija de 60 minutos.

---

## 2. Catálogo Detallado de los 15 Endpoints

### E-01: `POST /api/auth/login` (Público)
- **Entrada:**
  ```json
  {
    "username": "admin",
    "password": "Password123!"
  }
  ```
- **Salida (200 OK):**
  ```json
  {
    "accessToken": "eyJhbGciOi...",
    "expiresAt": "2026-10-10T00:00:00+00:00",
    "username": "admin",
    "role": "admin"
  }
  ```
- **Errores:** `400 Bad Request` (campos faltantes), `401 Unauthorized` (credenciales inválidas, cuerpo vacío).

### E-02: `POST /api/auth/register` (Solo Rol `admin`)
- **Entrada:**
  ```json
  {
    "username": "carlos",
    "password": "Password123!"
  }
  ```
- **Salida (201 Created):** Cuerpo vacío (`Content-Length: 0`).
- **Restricción:** Solo crea usuarios con rol `seller`.

### E-03: `GET /api/products` (Autenticado)
- **Query Params:** `page` (int, default 1), `size` (int, default 20, max 100), `categoryId` (UUID, opcional).
- **Salida (200 OK):**
  ```json
  {
    "items": [
      {
        "id": "e4f8d220-410a-4b9b-8fbb-5cb7fa923011",
        "name": "Martillo de Uña 16oz",
        "price": 28500.00,
        "stock": 45,
        "categoryId": "22222222-2222-2222-2222-222222222222",
        "categoryName": "Herramientas",
        "imageUrl": "http://localhost:8000/media/img-martillo.webp"
      }
    ],
    "page": 1,
    "size": 20,
    "total": 1,
    "totalPages": 1
  }
  ```

### E-04: `GET /api/products/{id}` (Autenticado)
- **Salida (200 OK):** Detalle de un producto individual (incluso si está dado de baja lógica).
- **Errores:** `404 Not Found` (vacío).

### E-05: `POST /api/products` (Solo Rol `admin`)
- **Entrada:**
  ```json
  {
    "name": "Destornillador Phillips #2",
    "price": 12500.00,
    "stock": 30,
    "categoryId": "22222222-2222-2222-2222-222222222222"
  }
  ```
- **Salida (201 Created):** Representación del `ProductView` creado.

### E-06: `PUT /api/products/{id}` (Solo Rol `admin`)
- **Entrada:** Actualización de `name`, `price`, `stock`, `categoryId`.
- **Salida (200 OK):** `ProductView` actualizado.

### E-07: `DELETE /api/products/{id}` (Solo Rol `admin`)
- **Salida (204 No Content):** Cuerpo vacío. Ejecuta baja lógica (`deleted_at`).

### E-08: `POST /api/products/{id}/image` (Solo Rol `admin`)
- **Formato:** `multipart/form-data` con campo `image`.
- **Restricciones:** Máximo 5 MB, formatos MIME permitidos: JPEG, PNG, WebP.
- **Salida (200 OK):** `ProductView` con el nuevo `imageUrl`.

### E-09: `GET /api/categories` (Autenticado)
- **Salida (200 OK):** Lista de las 5 categorías oficiales sembradas.

### E-10: `POST /api/sales` (Autenticado)
- **Entrada:**
  ```json
  {
    "items": [
      {
        "productId": "e4f8d220-410a-4b9b-8fbb-5cb7fa923011",
        "quantity": 2
      }
    ]
  }
  ```
- **Salida (201 Created):**
  ```json
  {
    "id": "7a8b9c0d-1111-2222-3333-444455556666",
    "soldAt": "2026-10-09T23:15:00.000000+00:00",
    "soldByUsername": "carlos",
    "total": 57000.00,
    "items": [
      {
        "productId": "e4f8d220-410a-4b9b-8fbb-5cb7fa923011",
        "productName": "Martillo de Uña 16oz",
        "categoryName": "Herramientas",
        "quantity": 2,
        "unitPrice": 28500.00,
        "subtotal": 57000.00
      }
    ]
  }
  ```
- **Errores:** `409 Conflict` (si el stock cambió o es insuficiente), `422 Unprocessable Entity` (invariante violada).

### E-11: `GET /api/sales` (Autenticado)
- **Query Params:** `from` (ISO 8601, obligatorio), `to` (ISO 8601, obligatorio), `page`, `size`.
- **Rango:** `from <= sold_at < to` (extremo inicial inclusivo, final exclusivo).

### E-12: `GET /api/sales/{id}` (Autenticado)
- **Salida (200 OK):** Detalle de venta con líneas históricas congeladas.

### E-13: `GET /api/reports/sales` (Autenticado)
- **Query Params:** `from` y `to` obligatorios.
- **Salida (200 OK):**
  ```json
  {
    "salesCount": 15,
    "grandTotal": 450000.00,
    "currency": "COP",
    "rows": [
      {
        "productId": "e4f8d220-410a-4b9b-8fbb-5cb7fa923011",
        "productName": "Martillo de Uña 16oz",
        "categoryName": "Herramientas",
        "quantitySold": 5,
        "totalAmount": 142500.00
      }
    ]
  }
  ```

### E-14: `GET /health` (Público)
- **Salida (200 OK):** `{"status": "ok"}`.

### E-15: `GET /media/{key}` (Público)
- **Salida (200 OK):** Archivo binario con cabecera `Content-Type: image/jpeg` (o png/webp).
