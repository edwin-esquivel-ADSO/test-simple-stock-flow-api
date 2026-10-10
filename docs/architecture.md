# Arquitectura Onion (Cebolla) — test-simple-stock-flow-api

> **Backend en PHP 8.2+ con Laravel 11 bajo Arquitectura Onion**  
> **Prueba técnica · Ficha ADSO 3413974**  
> Rige bajo los principios de `constitution.md`. Traduce formalmente la especificación base a **Arquitectura Onion pura**.

---

## 1. Fundamentos y Regla de Dependencia Concéntrica

La Arquitectura Onion organiza el sistema en capas concéntricas donde el núcleo representa el dominio del negocio y los anillos exteriores representan los detalles técnicos y mecanismos de entrega.

```
                    ESQUEMA DETALLADO DE CAPAS Y ANILLOS
                    
         ┌────────────────────────────────────────────────────────┐
         │  PUNTO DE ENSAMBLAJE (COMPOSITION ROOT)                │
         │  app/Bootstrap/PortBindingsServiceProvider.php         │
         │  Amarra los puertos salientes con su implementación    │
         └───────────────────────────┬────────────────────────────┘
        ┌────────────────────────────┴─────────────────────────────┐
        ▼                                                          ▼
┌──────────────────────────────┐            ┌──────────────────────────────┐
│  ANILLO 4: PRESENTATION      │            │  ANILLO 3: INFRASTRUCTURE    │
│  app/Presentation/           │            │  app/Infrastructure/         │
│  - Controllers HTTP          │            │  - Repositorios Eloquent     │
│  - Form Requests             │            │  - Mappers Dominio <-> DB    │
│  - JsonResources             │            │  - LaravelUnitOfWork (Tx)    │
│  - Middleware de Auth (JWT)  │            │  - JWT & Password Hasher     │
│  PROHIBIDO importar Infra    │            │  - LocalDiskFileStorage      │
└──────────────┬───────────────┘            └──────────────┬───────────────┘
               │                                           │
               │           imports hacia adentro           │
               ▼                                           ▼
             ┌──────────────────────────────────────────────────┐
             │  ANILLO 2: APPLICATION                           │
             │  app/Application/                                │
             │  - Casos de Uso (Services de Aplicación)         │
             │  - 5 Puertos Inbound (Interfaces de entrada)     │
             │  - 10 Puertos Outbound (Interfaces de salida)    │
             │  CERO Illuminate / CERO acoplamiento a Laravel   │
             └─────────────────────────┬────────────────────────┘
                                       │ solo importa Domain
                                       ▼
             ┌──────────────────────────────────────────────────┐
             │  ANILLO 1: DOMAIN (NÚCLEO DEL SISTEMA)           │
             │  app/Domain/                                     │
             │  - Agregados: Product, Sale, User                │
             │  - Entidades: SaleItem, Category                 │
             │  - Value Objects: Money, Quantity, Role, etc.    │
             │  - Excepciones de Dominio (Invariantes puras)    │
             │  PHP PURO. 0 dependencias externas.              │
             └──────────────────────────────────────────────────┘
```

---

## 2. Descripción Detallada de Cada Anillo

### Anillo 1: Dominio (`app/Domain`)
- **Responsabilidad:** Encapsular las reglas del negocio innegociables (RN-01 a RN-12).
- **Características:** No utiliza clases del framework, ni anotaciones ORM, ni métodos de persistencia activos.
- **Modelos:**
  - `Product`: Agregado raíz que controla la invariante de existencias (`withdraw()`, `restock()`, `softDelete()`).
  - `Sale`: Agregado raíz inmutable que recibe líneas de venta, calcula el total derivado y previene productos duplicados.
  - `SaleItem`: Entidad interna que congela el nombre, categoría y precio en el instante de la transacción.
  - `Money`: Value Object inmutable con precisión decimal vía `BigDecimal` y divisa obligatoria `COP`.
  - `Quantity`: Value Object que garantiza cantidades enteras positivas estrictas ($q > 0$).
  - `Role`: Value Object que restringe los roles al conjunto cerrado `{'admin', 'seller'}`.

### Anillo 2: Aplicación (`app/Application`)
- **Responsabilidad:** Orquestar los casos de uso del sistema sin conocer los detalles de infraestructura.
- **Puertos de Entrada (Inbound Ports):** Interfaces que exponen las operaciones del negocio a la capa de presentación.
  1. `AuthenticationUseCaseInterface`
  2. `ProductCatalogUseCaseInterface`
  3. `PlaceSaleUseCaseInterface`
  4. `GetSalesUseCaseInterface`
  5. `SalesReportUseCaseInterface`
- **Puertos de Salida (Outbound Ports):** Interfaces que definen los contratos requeridos para interactuar con el exterior.
  - `ProductRepositoryInterface`, `SaleRepositoryInterface`, `UserRepositoryInterface`, `CategoryRepositoryInterface`
  - `UnitOfWorkInterface` (abstracción transaccional)
  - `TokenGeneratorInterface`, `PasswordHasherInterface`, `FileStorageInterface`
  - `SalesReportQueryInterface`

### Anillo 3: Infraestructura (`app/Infrastructure`)
- **Responsabilidad:** Implementar los adaptadores técnicos requeridos por los puertos salientes.
- **Componentes:**
  - `EloquentProductRepository`, `EloquentSaleRepository`: Adaptadores que usan modelos Eloquent solo para lectura/escritura y convierten hacia entidades de dominio mediante Mappers.
  - `LaravelUnitOfWork`: Encapsula `DB::transaction()` garantizando que la aplicación ejecute operaciones atómicas sin importar clases de base de datos.
  - `JwtTokenGenerator`: Genera y valida tokens con expiración de 60 minutos.
  - `BcryptPasswordHasher`: Hashea credenciales de forma segura.

### Anillo 4: Presentación (`app/Presentation`)
- **Responsabilidad:** Entrada HTTP, transformación y respuesta al cliente.
- **Componentes:**
  - Controladores delgados que delegan directamente a los puertos de entrada de `Application`.
  - Form Requests para validación sintáctica de datos entrantes.
  - JsonResources para serialización canónica en `camelCase`.
  - **Regla Estricta:** La presentación tiene prohibido comunicarse directamente con la infraestructura.

### Composition Root (`app/Bootstrap`)
- **Responsabilidad:** El único punto de ensamblaje del sistema es `PortBindingsServiceProvider`. Es la única clase autorizada a conocer interfaces e implementaciones a la vez para registrarlas en el contenedor de inyección de dependencias de Laravel.

---

## 3. Pruebas de Arquitectura Automatizadas

La integridad de esta arquitectura se valida de forma automática mediante PHPUnit (`tests/Architecture/ArchitectureTest.php`):
- Prueba automática que verifica que ninguna clase bajo `app/Domain/` importe el namespace `Illuminate`.
- Prueba que valida que `Application` no importe clases de `Infrastructure`.
