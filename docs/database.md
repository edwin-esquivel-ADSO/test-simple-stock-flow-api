# Esquema de Base de Datos y Migraciones — test-simple-stock-flow-api

> **Persistencia Relacional en MySQL 8.4 LTS**  
> **Prueba técnica · Ficha ADSO 3413974**  
> Rige el principio de soberanía del esquema: la API es la **única dueña** de la base de datos.

---

## 1. Diagrama Entidad-Relación (ER)

```
┌─────────────────────────┐           ┌────────────────────────────────┐
│        category         │           │            product             │
├─────────────────────────┤           ├────────────────────────────────┤
│ id: CHAR(36) [PK]       │1         *│ id: CHAR(36) [PK]              │
│ name: VARCHAR(120) [UQ] ├───────────┤ name: VARCHAR(200)             │
└─────────────────────────┘           │ price: DECIMAL(12,2)           │
                                      │ stock: INT                     │
                                      │ category_id: CHAR(36) [FK]     │
                                      │ image_key: VARCHAR(512)        │
                                      │ deleted_at: DATETIME(6) [NULL] │
                                      │ version: INT [DEFAULT 1]       │
                                      └──────────────┬─────────────────┘
                                                     │1
                                                     │
                                                     │*
┌─────────────────────────┐           ┌──────────────┴─────────────────┐
│          sale           │           │           sale_item            │
├─────────────────────────┤           ├────────────────────────────────┤
│ id: CHAR(36) [PK]       │1         *│ id: CHAR(36) [PK]              │
│ sold_at: DATETIME(6)    ├───────────┤ sale_id: CHAR(36) [FK]         │
│ sold_by_username: V(120)│           │ product_id: CHAR(36) [FK]      │
│ sold_by_user_id: CH(36) │           │ product_name: VARCHAR(200)     │
└────────────┬────────────┘           │ category_name: VARCHAR(120)    │
             │*                       │ quantity: INT                  │
             │                        │ unit_price: DECIMAL(12,2)      │
             │1                       └────────────────────────────────┘
┌────────────┴────────────┐
│          user           │
├─────────────────────────┤
│ id: CHAR(36) [PK]       │
│ username: V(120) [UQ]   │
│ password_hash: V(512)   │
│ role: VARCHAR(40)       │
└─────────────────────────┘
```

---

## 2. Los 9 Check Constraints en MySQL 8.4

Para evitar inconsistencias incluso ante escrituras directas o errores de concurrencia, la migración `2026_10_02_000001_create_simple_stock_flow_schema.php` implementa las siguientes 9 restricciones:

1. **`category.ck_category_name_not_blank`:** `CHAR_LENGTH(TRIM(name)) > 0`
2. **`product.ck_product_name_not_blank`:** `CHAR_LENGTH(TRIM(name)) > 0`
3. **`product.ck_product_price_positive`:** `price > 0` (Garantiza RN-02 a nivel físico)
4. **`product.ck_product_stock_non_negative`:** `stock >= 0` (Garantiza RN-01: barrera física anti-sobreventa)
5. **`user.ck_user_username_normalized`:** `CHAR_LENGTH(username) > 0 AND CAST(username AS BINARY) = CAST(LOWER(TRIM(username)) AS BINARY)` (RN-10: solo minúsculas sin espacios)
6. **`user.ck_user_role_allowed`:** `role IN ('admin', 'seller')` (RN-11: conjunto cerrado de roles)
7. **`user.ck_user_password_hash_not_blank`:** `CHAR_LENGTH(password_hash) > 0`
8. **`sale_item.ck_sale_item_quantity_positive`:** `quantity > 0` (RN-03: cantidad estrictamente positiva)
9. **`sale_item.ck_sale_item_unit_price_positive`:** `unit_price > 0` (RN-02: precio unitario congelado positivo)

---

## 3. Concurrencia Optimista y Congelamiento

### Concurrencia Optimista (`version`)
En la tabla `product`, la columna entera `version` se incrementa con cada actualización atómica:
```sql
UPDATE product 
SET stock = stock - ?, version = version + 1 
WHERE id = ? AND version = ?;
```
Si dos vendedores procesan la última unidad en paralelo, una transacción tendrá éxito y la otra detectará 0 filas afectadas, disparando una `ConcurrencyConflictException` que se traduce en un `409 Conflict`.

### Congelamiento de Líneas de Venta (`sale_item`)
Cada registro en `sale_item` almacena copias inmutables de:
- `product_name`
- `category_name`
- `unit_price`

Esto asegura que modificaciones futuras en los productos no alteren balances financieros pasados (RN-06).
