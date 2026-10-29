# Products API

API RESTful en Laravel 13 para gestionar productos, sus precios en distintas divisas y las divisas. Autenticación con JWT, arquitectura DDD por capas y tests con Pest.

- [Puesta en marcha](#puesta-en-marcha)
- [Autenticación](#autenticación)
- [Endpoints](#endpoints)
- [Ejemplos](#ejemplos)
- [Errores](#errores)
- [Seguridad](#seguridad)
- [Concurrencia](#concurrencia)
- [Resiliencia](#resiliencia)
- [Performance](#performance)
- [Cache](#cache)
- [Mensajería asíncrona](#mensajería-asíncrona)
- [Arquitectura](#arquitectura)
- [Tests](#tests)
- [Decisiones de diseño](#decisiones-de-diseño)

## Puesta en marcha

Requisitos: PHP 8.3+, Composer y Docker (para PostgreSQL, Redis y SQS).

```bash
composer install
cp .env.example .env               # completar DB_PASSWORD y REDIS_PASSWORD
php artisan key:generate
php artisan jwt:secret             # clave para firmar los JWT

docker compose up -d               # PostgreSQL 17 + Redis 7.4 + SQS (ElasticMQ)
php artisan migrate --seed         # tablas + datos de ejemplo
php artisan serve                  # http://localhost:8000
```

Y en otras terminales, un worker por cola (ver [Mensajería asíncrona](#mensajería-asíncrona)):

```bash
php artisan queue:work sqs --queue=imports
php artisan queue:work sqs --queue=catalog-events
php artisan queue:work sqs --queue=mail
php artisan queue:work sqs --queue=default
```

Si el puerto 6379 ya está ocupado (por ejemplo, por un Redis instalado en la máquina), cambiá `REDIS_PORT` en el `.env`: el contenedor publica Redis en ese puerto.

El seeder crea:

- Un usuario `test@example.com` con contraseña `password`.
- Cinco divisas: US Dollar (base, tasa 1), Euro, Peso argentino, Real brasileño y Pound sterling.
- Tres productos con precios en otras divisas.

### Documentación interactiva (OpenAPI)

La especificación OpenAPI 3 se genera automáticamente a partir de las rutas, los FormRequests y los API Resources con [Scramble](https://scramble.dedoc.co):

- UI: <http://localhost:8000/docs/api> (solo con `APP_ENV=local`)
- JSON: <http://localhost:8000/docs/api.json>, o exportado en [`docs/openapi.json`](docs/openapi.json)

Para regenerar el archivo: `php artisan scramble:export --path=docs/openapi.json`.

## Autenticación

Todos los endpoints de productos y divisas requieren un JWT. Se obtiene con login o registro y se envía en cada request:

```
Authorization: Bearer <access_token>
```

El token vence según `JWT_TTL` (60 minutos por defecto). Se renueva con `POST /api/auth/refresh` y se invalida con `POST /api/auth/logout`.

## Endpoints

Base URL: `/api`. Todas las respuestas son JSON.

Todos los listados están paginados con `?page=1&per_page=15` (máximo 100) y devuelven `data`, `links` y `meta`. Los `GET` devuelven un `ETag`; reenviándolo en `If-None-Match` se obtiene `304` si nada cambió.

### Productos

| Método | Ruta | Descripción | Respuesta |
|---|---|---|---|
| GET | `/products` | Lista paginada (`?page=1&per_page=15`, máximo 100) | 200 |
| POST | `/products` | Crea un producto | 201 |
| GET | `/products/{id}` | Obtiene un producto | 200 |
| PUT | `/products/{id}` | Reemplaza todos los campos del producto | 200 |
| DELETE | `/products/{id}` | Elimina el producto (soft delete) | 204 |
| GET | `/products/{id}/prices` | Lista paginada de los precios del producto en otras divisas | 200 |
| POST | `/products/{id}/prices` | Registra el precio en una divisa: lo crea o lo reemplaza si ya existe | 201 / 200 |
| GET | `/products/{id}/history` | Historial de cambios del producto (auditoría asíncrona), paginado | 200 |
| POST | `/products/import` | Importa hasta 1000 productos en background | 202 |
| GET | `/imports/{id}` | Estado de una importación (solo para quien la creó) | 200 |

### Divisas

| Método | Ruta | Descripción | Respuesta |
|---|---|---|---|
| GET | `/currencies` | Lista paginada de divisas | 200 |
| POST | `/currencies` | Crea una divisa (nombre único) | 201 |
| GET | `/currencies/{id}` | Obtiene una divisa | 200 |
| PUT | `/currencies/{id}` | Reemplaza todos los campos de la divisa | 200 |
| DELETE | `/currencies/{id}` | Elimina una divisa que ningún producto usa | 204 / 409 |

### Autenticación

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| POST | `/auth/register` | — | Registra un usuario y devuelve un token |
| POST | `/auth/login` | — | Devuelve un token (máximo 5 intentos fallidos) |
| POST | `/auth/refresh` | JWT | Cambia el token por uno nuevo |
| POST | `/auth/logout` | JWT | Invalida el token |
| GET | `/auth/me` | JWT | Usuario autenticado |
| POST | `/auth/forgot-password` | — | Envía el link de reseteo por email |
| POST | `/auth/reset-password` | — | Cambia la contraseña con el token del email |
| GET | `/auth/email/verify/{id}/{hash}` | URL firmada | Verifica el email desde el link enviado |
| POST | `/auth/email/verification-notification` | JWT | Reenvía el email de verificación |

## Ejemplos

**Login**

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password"}'
```

```json
{
  "message": "Logged in successfully.",
  "user": { "id": 1, "name": "Test User", "email": "test@example.com", "email_verified_at": "2026-10-29T17:00:00.000000Z", "created_at": "2026-10-29T17:00:00.000000Z" },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**Crear un producto**

```bash
curl -X POST http://localhost:8000/api/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Mechanical keyboard",
    "description": "Hot-swappable mechanical keyboard.",
    "price": 150.50,
    "currency_id": 1,
    "tax_cost": 31.61,
    "manufacturing_cost": 70
  }'
```

```json
{
  "data": {
    "id": 4,
    "name": "Mechanical keyboard",
    "description": "Hot-swappable mechanical keyboard.",
    "price": "150.50",
    "currency_id": 1,
    "tax_cost": "31.61",
    "manufacturing_cost": "70.00"
  }
}
```

**Registrar un precio en otra divisa**

```bash
curl -X POST http://localhost:8000/api/products/4/prices \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"currency_id": 2, "price": 138.50}'
```

```json
{ "data": { "id": 6, "product_id": 4, "currency_id": 2, "price": "138.50" } }
```

Responde `201` si el producto no tenía precio en esa divisa. Si ya lo tenía, lo reemplaza y responde `200`.

**Listado paginado**

```bash
curl "http://localhost:8000/api/products?per_page=2&page=1" -H "Authorization: Bearer $TOKEN"
```

```json
{
  "data": [ { "id": 1, "name": "Mechanical keyboard", "...": "..." }, { "id": 2, "...": "..." } ],
  "links": { "first": "...?page=1", "last": "...?page=2", "prev": null, "next": "...?page=2" },
  "meta": { "current_page": 1, "last_page": 2, "per_page": 2, "total": 3, "...": "..." }
}
```

## Errores

| Código | Cuándo | Cuerpo |
|---|---|---|
| 304 | `If-None-Match` coincide con el `ETag` actual | sin cuerpo |
| 400 | Header `Idempotency-Key` vacío o de más de 255 caracteres | `{"message": "..."}` |
| 401 | Falta el token, es inválido, venció o fue revocado | `{"message": "Unauthenticated."}` |
| 403 | Link de verificación de email inválido | `{"message": "..."}` |
| 404 | El producto o la divisa no existe, o el id no es un entero válido | `{"message": "The requested product does not exist."}` |
| 409 | Se borra una divisa en uso; otra request tiene bloqueado el recurso hace más de 5 s (reintentar); o una `Idempotency-Key` sigue en proceso | `{"message": "..."}` |
| 422 | Error de validación o de una regla de negocio | `{"message": "...", "errors": {"campo": ["..."]}}` |
| 429 | Se superó el rate limit | `{"message": "Too Many Attempts."}` |
| 503 | La base de datos o Redis no responden; reintentar después de `Retry-After` segundos | `{"message": "The service is temporarily unavailable. Try again later."}` |

Ejemplos de reglas de negocio que responden `422` sobre el campo afectado:

- `currency_id` de una divisa que no existe.
- Un precio adicional en la divisa base del producto. El precio base se cambia con `PUT /products/{id}`.
- Un nombre de divisa repetido.

## Seguridad

- **Autenticación JWT** (`php-open-source-saver/jwt-auth`) en todos los endpoints de productos y divisas. Los tokens tienen vencimiento, y se invalidan en logout y refresh mediante blacklist.
- **Rate limiting:** 60 requests por minuto por usuario (o por IP si no hay usuario) en toda la API. Login limitado a 5 intentos fallidos por email e IP.
- **Validación estricta** con FormRequests:
  - montos `decimal(12,2)` no negativos;
  - tasas mayores a 0 con 6 decimales;
  - largos máximos;
  - ids de ruta restringidos a enteros positivos.
- **Invariantes protegidas en el dominio,** además de la validación HTTP, con value objects que no se pueden construir en un estado inválido.
- **Mass assignment** controlado con `#[Fillable]` en los modelos. Las queries pasan por Eloquent con bindings, lo que evita SQL injection.
- **Respuestas siempre en JSON** en `/api`, sin páginas HTML ni redirecciones.
- **Headers de seguridad:** `X-Content-Type-Options`, `X-Frame-Options` y `Referrer-Policy`.
- **CORS** limitado a `api/*` y al origen configurado en `FRONTEND_URL`, sin cookies.
- **Contraseñas hasheadas** con bcrypt y nunca expuestas en las respuestas.
- **En producción, `APP_DEBUG=false`,** así los errores responden solo el `message`, sin stack trace ni rutas internas. La UI de documentación solo se sirve con `APP_ENV=local`.

## Concurrencia

**Locking pesimista.** Los casos de uso que leen, modifican y guardan corren en una transacción y bloquean la fila con `SELECT … FOR UPDATE` desde la lectura hasta el commit:

- actualizar un producto,
- registrar un precio,
- borrar un producto,
- actualizar o borrar una divisa.

Si dos requests modifican el mismo recurso, la segunda **espera** a que termine la primera y trabaja sobre los datos ya actualizados. Así no se pierde ningún cambio y no hace falta reintentar.

- **Lock sobre el aggregate root.** Todo cambio de precios pasa por el producto, así que bloquear la fila del producto alcanza. Además, como siempre se toma un solo lock, no puede haber deadlocks por adquirir locks en distinto orden.
- **`lock_timeout` de 5 s.** Ninguna request queda esperando indefinidamente un lock: si se supera, responde `409` y se puede reintentar.
- **Deadlocks.** Si PostgreSQL aborta una transacción por deadlock, se reintenta completa hasta 3 veces.
- **Borrar una divisa** bloquea su fila antes de chequear si está en uso. Un producto que empieza a usarla al mismo tiempo tiene que esperar el chequeo de la foreign key sobre esa fila, así que el chequeo y el borrado no se pueden intercalar.
- **Lecturas sin bloqueo.** Los `GET` no abren transacción ni toman locks.

La frontera transaccional es un puerto de la capa de aplicación (`TransactionManager`), implementado en infraestructura con las transacciones de Laravel.

**Races sobre constraints.** Dos requests que crean la misma divisa o el mismo email pueden pasar el chequeo de unicidad a la vez. La base es la fuente de verdad: los repositorios traducen la violación del índice único a la regla de negocio (`422`). Lo mismo con una divisa que empieza a usarse mientras se borra (`409`). Cualquier violación que no se haya traducido responde `409`, nunca un `500` con SQL.

**Idempotencia.** Los `POST` autenticados aceptan `Idempotency-Key`:

- Un reintento con la misma clave y el mismo body devuelve la respuesta original, con `Idempotent-Replayed: true`, en vez de crear un duplicado.
- Con otro body o en otro endpoint, responde `422`.
- Si la primera request sigue en proceso, responde `409`.

Las claves pertenecen a cada usuario y se guardan 24 horas. Solo se guardan las respuestas exitosas.

## Resiliencia

- **Base o Redis caídos:** responde `503` con `Retry-After: 30` y un mensaje genérico, sin host ni detalles de conexión. `/up` verifica los dos.
- **Deadlocks y locks:** las transacciones se reintentan hasta 3 veces si la base las aborta por deadlock, y ninguna espera un lock más de 5 s.
- **Errores de base no previstos:** se traducen a `409` (constraints y lock timeout) o `503` (conexión) en `Infrastructure/Shared/Persistence/DatabaseErrorRenderer`.

## Performance

- **Paginación** en todos los listados, resuelta en la base (`LIMIT/OFFSET`). Los precios se paginan sin cargar el agregado completo.
- **Índices** en `products.currency_id` y `product_prices.currency_id`: PostgreSQL no indexa las foreign keys por su cuenta, y se usan para saber si una divisa está en uso. `(product_id, currency_id)` tiene un índice único.
- **Eager loading** de los precios al leer productos, y **strict mode** de Eloquent fuera de producción: un N+1 o un atributo inexistente hace fallar el request en desarrollo y en los tests.
- **Cache en Redis** de lo que cambia poco: ver [Cache](#cache).
- **Respuestas condicionales:** los `GET` devuelven un `ETag` calculado del contenido y responden `304` sin cuerpo cuando el cliente ya tiene esa respuesta (`If-None-Match`). Son `Cache-Control: private`, porque dependen del usuario autenticado.

## Cache

Las lecturas del catálogo se sirven desde Redis. Los repositorios cacheados son decoradores en infraestructura (`CachedProductRepository`, `CachedCurrencyRepository`), así que el dominio y los use cases no saben que existe un cache.

| Qué | TTL | Variable |
|---|---|---|
| Listados de productos, divisas y precios (cada página) | 5 min | `CACHE_TTL_LISTINGS` |
| Producto y divisa individuales, chequeos de existencia | 10 min | `CACHE_TTL_RESOURCES` |

**Cuándo se renueva:**

- **Al vencer el TTL,** aunque nadie haya escrito por la API. Esto cubre cambios hechos por fuera, como seeders o SQL manual. Para verlos al instante: `php artisan cache:clear`.
- **Cuando se crea, modifica o borra un registro por la API.** Toda escritura renueva el namespace completo (`products` o `currencies`). Así un producto nuevo aparece enseguida en todas las páginas del listado, sin tener que saber en cuáles cae.

**Cómo funciona la invalidación.** Cada clave incluye la *generación* de su namespace, un token aleatorio (`products:g3f9a…:page:1:15`). Invalidar es reemplazar ese token:

- Las entradas viejas dejan de leerse de una vez, en una operación atómica, y Redis las borra solas al vencer su TTL. No hace falta recorrer claves con `KEYS`, que bloquea Redis.
- La invalidación corre **después del commit** de la transacción. Si corriera antes, una lectura concurrente podría volver a cachear los datos viejos. Con un rollback no se invalida nada.
- Redis está configurado para desalojar claves cuando se llena (`allkeys-lru`). Si desaloja el token, se genera uno nuevo y aleatorio, así que nunca se puede volver a leer una generación vieja.

**Qué nunca se cachea:**

- La lectura con lock (`SELECT … FOR UPDATE`): tiene que ver la fila real.
- El chequeo de nombre de divisa único.
- El chequeo de "¿esta divisa está en uso?".

**Qué se guarda.** Se cachean arrays de valores primitivos, no objetos PHP (`cache.serializable_classes` está desactivado), y las entidades se reconstruyen al leer. Deserializar objetos desde un store compartido se puede explotar para ejecutar código.

**Redis** (`docker-compose.yml`) corre como cache: no persiste a disco, tiene 256 MB con desalojo LRU y requiere contraseña. También guarda el rate limiting y las `Idempotency-Key`, por eso es una dependencia obligatoria, igual que la base:

- `/up` lo verifica.
- Si no responde, la API devuelve `503` con `Retry-After`.

## Mensajería asíncrona

Las tareas que no tienen que bloquear al cliente se procesan en background con **Amazon SQS**, mediante el driver nativo de colas de Laravel.

- En local, SQS lo emula **ElasticMQ** (`docker-compose.yml`) y no hace falta una cuenta de AWS.
- En producción se apunta a AWS SQS cambiando solo variables de entorno: se quita `SQS_ENDPOINT` y se configuran `SQS_PREFIX` y las credenciales. El código no cambia.

**Por qué SQS.** Entre SQS, RabbitMQ y Kafka, es el único con driver oficial de Laravel: jobs, reintentos, backoff, delays y `failed_jobs` funcionan sin paquetes de terceros. RabbitMQ necesita un paquete de la comunidad, y Kafka es un log de eventos que no encaja con el modelo de colas de Laravel (además de ser mucha más infraestructura).

### Ejemplos

| Ejemplo | Patrón | Cola |
|---|---|---|
| **Email de bienvenida** al registrarse | Listener encolado sobre un evento | `mail` |
| **Auditoría del catálogo** (`GET /products/{id}/history`) | Eventos de dominio publicados como mensajes; un consumidor los registra | `catalog-events` |
| **Importación masiva** (`POST /products/import`) | API asíncrona: `202 Accepted` + polling del estado | `imports` |

**Auditoría.** Los use cases publican eventos de dominio (`product.created`, `product.updated`, `product.price_set`, `product.deleted`) a través del puerto `EventPublisher`.

- La infraestructura los envuelve en un mensaje con el usuario que hizo el cambio.
- Cada consumidor es un listener encolado que recibe su propia copia. Agregar otro consumidor es agregar otro listener.
- SQS entrega *al menos una vez*, así que el consumidor es **idempotente**: inserta por `event_id`, que es único, e ignora los duplicados.

**Importación.**

1. Valida todas las filas antes de aceptar (los errores apuntan a la fila, como `products.3.price`) y responde `202` con `Location: /api/imports/{id}` y `Retry-After`.
2. Las filas quedan en la base y el mensaje lleva **solo el id** (*claim check*). Así se respeta el límite de 256 KB de SQS.
3. El worker procesa **una fila por transacción**, con el import bloqueado (`FOR UPDATE`), y avanza el progreso en la misma transacción. Si el worker se cae a mitad, el reintento sigue desde la fila siguiente. Si llega un mensaje duplicado, el segundo worker espera el lock y encuentra la fila hecha. Cada fila se procesa **exactamente una vez**.
4. Los errores de negocio (por ejemplo, una divisa que no existe) quedan en `errors` con el número de fila y no frenan el resto.
5. Si el job agota sus reintentos, el import queda `failed`.

```bash
curl -i -X POST http://localhost:8000/api/products/import -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -d '{"products": [{"name": "A", "description": "…", "price": 10, "currency_id": 1, "tax_cost": 1, "manufacturing_cost": 2}]}'
# HTTP/1.1 202 Accepted
# Location: http://localhost:8000/api/imports/bd2a3b3d-…
# Retry-After: 2

curl http://localhost:8000/api/imports/bd2a3b3d-… -H "Authorization: Bearer $TOKEN"
# {"data": {"status": "completed", "total": 1, "processed": 1, "succeeded": 1, "failed": 0, "errors": [], …}}
```

### Garantías y buenas prácticas

- **Se envía después del commit.** La conexión `sqs` tiene `after_commit = true` y los listeners son `ShouldQueueAfterCommit`. Si la transacción hace rollback, no se manda nada, y un worker nunca procesa datos que todavía no son visibles.
- **Reintentos con backoff** en cada job (`tries` y `backoff`). Los que agotan los intentos quedan en `failed_jobs`.
- **Dead-letter queue por cola.** Un mensaje que tumba al worker antes de que Laravel registre la falla pasa a `<cola>-dlq` después de 5 recepciones, en vez de reintentarse para siempre.
- **Visibility timeout mayor que el timeout del job.** Por ejemplo, `imports` tiene 360 s de visibilidad y el job 300 s, así SQS no entrega el mismo mensaje a otro worker mientras el primero sigue trabajando.
- **Long polling de 20 s:** menos requests vacíos, que en SQS se cobran.
- **Un worker por cola.** Con long polling, un worker que escucha varias colas espera hasta 20 s en cada cola vacía antes de pasar a la siguiente. Lo medimos: 21 s de latencia con un worker para todas las colas, contra menos de 1,5 s con uno por cola. Además cada cola escala por separado. La configuración de producción está en [`docker/supervisor/queue-workers.conf`](docker/supervisor/queue-workers.conf).

En los tests la cola es `sync`, así que los jobs corren en el momento, y se usa `Queue::fake()` para verificar qué se encola y en qué cola.

## Arquitectura

DDD por capas, con tres bounded contexts: **Catalog** (productos y sus precios), **Currency** (divisas) e **Identity** (usuarios y autenticación).

```
app/
├── Domain/            Reglas de negocio puras: entidades, value objects, excepciones e interfaces de repositorio.
│   ├── Catalog/       Product (aggregate root) + ProductPrice, Amount, ProductName…
│   ├── Currency/      Currency, ExchangeRate, CurrencyName…
│   ├── Identity/      User, Email, Password…
│   └── Shared/        Paginated
├── Application/       Use cases (uno por acción), DTOs y contratos que implementa la infraestructura.
├── Infrastructure/    Modelos Eloquent, mappers, repositorios, JWT, hashing, notificaciones.
├── Presentation/Http/ Controllers, FormRequests, API Resources, middleware y mapeo de excepciones a HTTP.
└── Providers/         Un service provider por contexto, que conecta interfaces con implementaciones.
```

La dirección de las dependencias es `Presentation → Application → Domain ← Infrastructure`. El dominio no depende de Laravel, y `tests/Unit/ArchitectureTest.php` lo verifica junto con el resto de las reglas de capas.

El flujo de una request es:

1. El FormRequest valida la entrada y arma un DTO con value objects.
2. El use case carga el agregado desde el repositorio, aplica la regla de negocio y lo guarda.
3. El API Resource serializa la entidad.

Las excepciones de dominio se traducen a HTTP en un único lugar: `Presentation/Http/Exceptions/DomainExceptionRenderer`.

Aunque el dominio está desacoplado, el proyecto usa lo que Laravel ya resuelve:

- Eloquent, con relaciones, casts, soft deletes y factories.
- FormRequests y API Resources con paginación estándar (`data`, `links`, `meta`).
- Traducciones en `lang/` y middleware de throttle.
- `$bindings` en los service providers, migraciones con foreign keys y seeders.

## Tests

```bash
php artisan test        # o: vendor/bin/pest
```

Los tests usan una base PostgreSQL separada, `beos_challange_test`; hay que crearla una vez:

```bash
docker exec postgres psql -U root -d beos_challange -c "CREATE DATABASE beos_challange_test;"
```

- **Unit:** value objects, entidades, DTOs y use cases (con repositorios en memoria, sin base de datos), y las reglas de arquitectura.
- **Feature:** cada endpoint con sus validaciones, códigos de estado y casos borde; la seguridad (401, rate limit, headers, mass assignment); los repositorios Eloquent y los adapters contra la base real; los seeders.

## Decisiones de diseño

- **Precios en otras divisas explícitos con upsert.** `POST /products/{id}/prices` recibe el precio en esa divisa. Si ya existía uno, lo reemplaza (una divisa tiene un solo precio por producto, y la base lo garantiza con un índice único).
- **La divisa base no tiene precio adicional.** El precio en la divisa base es `products.price`. Por la misma razón, un producto no puede cambiar su divisa base a una en la que ya tiene un precio registrado.
- **`exchange_rate`** son unidades de la divisa por cada unidad de la divisa de referencia (US Dollar = 1). Se guarda como dato; la API no convierte precios automáticamente porque el enunciado pide registrarlos.
- **Montos exactos.** Se guardan como `decimal(12,2)` (y `decimal(18,6)` las tasas), se operan con `brick/math` y se devuelven como string para evitar errores de redondeo de float.
- **PUT reemplaza el recurso completo,** así que todos los campos son obligatorios. No se expone PATCH.
- **Soft delete de productos** para conservar el historial. Una divisa que usa algún producto, aunque esté borrado, no se puede eliminar (`409`).
