# Arquitectura Onion — test-simple-stock-flow-api

> **Backend en PHP con Laravel bajo Arquitectura Onion (Cebolla)**
> **Prueba técnica · Ficha ADSO 3413974**

---

## 1. Topología de Capas

```
        ┌──────────────────────────────────────┐
        │  Bootstrap                           │  ← app/Bootstrap/PortBindingsServiceProvider.php
        │  Punto de Ensamblaje (Composition)   │    Conecta interfaces con implementaciones
        └───────────────┬──────────────────────┘
     ┌──────────────────┴──────────────────┐
     ▼                                     ▼
┌─────────────────┐            ┌──────────────────────┐
│ Presentation    │            │ Infrastructure       │
│ Anillo 4        │            │ Anillo 3             │
│ app/Presentation│            │ app/Infrastructure   │
│ Controllers     │            │ Eloquent Models      │
│ Requests        │            │ Mappers              │
│ Resources       │            │ Repositorios         │
│ PROHIBIDO:      │            │ JWT / Hash / Storage │
│ importar Infra  │            │ Migraciones          │
└────────┬────────┘            └──────────┬───────────┘
         │                                │
         │        imports hacia adentro   │
         ▼                                ▼
       ┌───────────────────────────────────────┐
       │ Application   Anillo 2                │
       │ app/Application                       │
       │ Casos de Uso · 5 Puertos Inbound      │
       │ 10 Puertos Outbound                   │
       │ CERO Laravel. Solo importa Domain.    │
       └──────────────────┬────────────────────┘
                          │ solo importa Domain
                          ▼
       ┌───────────────────────────────────────┐
       │ Domain   Anillo 1 (NÚCLEO)            │
       │ app/Domain                            │
       │ Entidades · Value Objects · Reglas    │
       │ PHP PURO. Cero dependencias externas. │
       └───────────────────────────────────────┘
```

---

## 2. Reglas de Dependencia
1. **Domain:** PHP puro sin dependencias de Laravel. Invariantes puras.
2. **Application:** Solo importa `Domain`. Orquesta los casos de uso (`PlaceSaleService`, `ProductCatalogService`, etc.) y define puertos de entrada y salida.
3. **Infrastructure:** Implementa los puertos salientes (`UnitOfWork`, Repositorios, Hashing, TokenGenerator) utilizando Eloquent y servicios del framework.
4. **Presentation:** Consume únicamente los puertos de entrada (`Inbound Ports`) de `Application`. Valida forma sintáctica y transforma salidas.
5. **Transacciones:** `UnitOfWork` encapsula `DB::transaction()` en `Infrastructure`. `Application` permanece libre de dependencias a base de datos.
