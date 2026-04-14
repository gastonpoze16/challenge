# challenge

## Bitacora de trabajo

### 2026-04-08

#### Paso 1 - Estructura inicial del repositorio
- Se crearon las carpetas base del proyecto:
  - `frontend`
  - `backend`

#### Paso 2 - Setup inicial de backend (Laravel)
- Se reviso el onboarding HTML para alinear el arranque con la arquitectura definida:
  - `HTTP -> Service -> Repository Interface -> Eloquent`
- Se creo un proyecto Laravel base dentro de `backend` usando Composer.
- Se generaron automaticamente los elementos iniciales de Laravel:
  - archivo `.env`
  - `APP_KEY`
  - base `database/database.sqlite`
  - migraciones iniciales ejecutadas
- Se validó la instalacion de framework:
  - `Laravel Framework 13.4.0`
- Se prepararon carpetas para la arquitectura por capas dentro de `backend/app`:
  - `Services`
  - `Repositories/Contracts`
  - `Repositories/Eloquent`
- Se corrigio una carpeta `app` creada por error en la raiz del repo, dejando la estructura limpia.

#### Paso 3 - Cambio de motor de base de datos
- Se actualizo la configuracion de `backend/.env` para usar `mysql` en lugar de `sqlite`.
- Configuracion aplicada:
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_PORT=3306`
  - `DB_DATABASE=challenge`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=`

#### Paso 4 - Backend Foundations (estructura sin endpoints)
- Se crearon los artefactos base de la arquitectura por capas para Week 1:
  - `app/Http/Controllers/WebhookController.php`
  - `app/Http/Requests/StorePaymentWebhookRequest.php`
  - `app/Services/PaymentWebhookService.php`
  - `app/Repositories/Contracts/EventLogRepositoryInterface.php`
  - `app/Repositories/Contracts/PaymentRepositoryInterface.php`
  - `app/Repositories/Eloquent/EloquentEventLogRepository.php`
  - `app/Repositories/Eloquent/EloquentPaymentRepository.php`
- Se actualizaron y prepararon los modelos:
  - `app/Models/EventLog.php`
  - `app/Models/Payment.php`
- Se crearon y definieron migraciones base:
  - `database/migrations/*_create_event_logs_table.php`
  - `database/migrations/*_create_payments_table.php`
- Se registraron bindings de interfaces a implementaciones en `AppServiceProvider`.
- No se agregaron rutas ni endpoints aun, como acordamos.

#### Paso 5 - Primer deliverable de Backend Foundations
- Se habilitó en Laravel el archivo `routes/api.php` desde `bootstrap/app.php`.
- Se configuró el prefijo de API vacio para exponer rutas sin `/api`.
- Se creó la ruta:
  - `POST /webhooks/payment` -> `WebhookController@store`
- Se implementó `WebhookController::store()` con respuesta JSON temporal.
- Se conectó `StorePaymentWebhookRequest` al endpoint para validar entrada.
- Validaciones aplicadas:
  - `event_id`: required, string
  - `payment_id`: required, string
  - `event`: required e incluido en tipos permitidos (`payment.created`, `payment.completed`, `payment.failed`, `payment.refunded`)
  - `amount`: required, numeric, `>= 0`
  - `currency`: required, 3 letras ISO en mayuscula
  - `user_id`: nullable, integer
  - `timestamp`: required, date

#### Paso 6 - Persistencia del webhook en MySQL
- Se conectó `WebhookController` con `PaymentWebhookService` para procesar y persistir cada request valida.
- Flujo implementado en `PaymentWebhookService` dentro de transaccion:
  - siempre inserta una fila en `event_logs` (incluyendo reintentos/duplicados)
  - luego revisa idempotencia contra `payments.last_event_id`
  - si `event_id` es duplicado para ese `payment_id`, no actualiza `payments`
  - si es nuevo, hace upsert de estado en `payments`
- Resultado del endpoint:
  - devuelve JSON confirmando si se actualizo o no la tabla `payments`

#### Paso 7 - Reorganizacion de controller de webhook
- Se renombró `WebhookController` a `PaymentWebhookController`.
- Se movió a una estructura mas especifica:
  - `app/Http/Controllers/Api/Webhooks/PaymentWebhookController.php`
- Se actualizó `routes/api.php` para usar el nuevo controller y namespace.
- La ruta publica se mantiene igual:
  - `POST /webhooks/payment`

#### Paso 8 - Convencion de servicios invocables
- Se actualizó `PaymentWebhookService` para seguir la convención invocable usando `__invoke(array $payload)`.
- Se actualizó `PaymentWebhookController` para llamar el servicio como invocable:
  - `($this->paymentWebhookService)($request->validated())`
- Se mantiene el mismo comportamiento funcional del flujo de persistencia e idempotencia.

#### Paso 9 - Errores de validacion mas claros en API
- Se mejoró `StorePaymentWebhookRequest` para devolver respuestas de validación más claras.
- Se agregaron mensajes personalizados por campo/regla (por ejemplo:
  - `amount must be greater than or equal to 0.`).
- Se estandarizó la respuesta de error `422` con estructura JSON:
  - `message`
  - `errors`

#### Paso 10 - Endpoint GET /payments
- Se creó `PaymentController` en:
  - `app/Http/Controllers/Api/Payments/PaymentController.php`
- Se creó servicio invocable `ListPaymentsService` en:
  - `app/Services/ListPaymentsService.php`
- Se agregó la ruta:
  - `GET /payments` -> `PaymentController@index`
- Comportamiento implementado:
  - devuelve el estado actual de `payments` ordenado por `updated_at` descendente (newest first), usando el repositorio.
  - respuesta JSON con `data` y `meta` de paginación.

#### Paso 11 - Endpoint GET /payments/{id}/events
- Se creó servicio invocable `ListPaymentEventsService` en:
  - `app/Services/ListPaymentEventsService.php`
- Se agregó `PaymentController::events` que usa el repositorio de event logs.
- Se agregó la ruta:
  - `GET /payments/{id}/events` -> `PaymentController@events`
- El parámetro `{id}` es el `payment_id` de negocio (el mismo string que en webhooks), no el `id` autoincremental de la tabla.
- Respuesta: todas las filas de `event_logs` para ese pago, ordenadas por `timestamp` ascendente (ya definido en `EloquentEventLogRepository::findByPaymentId`).
- Respuesta JSON: `{ "data": [ ... ] }`
- **404** solo si no existe ninguna fila en `payments` con ese `payment_id` (no se restringe por `payments.user_id` respecto al usuario de la sesión: el operador del dashboard puede auditar cualquier pago existente).

#### Paso 12 - Errores HTTP 400 y 422 con JSON consistente
- Se configuró `bootstrap/app.php` con manejadores para rutas de API (`webhooks/*`, `payments`, `payments/*`):
  - **400**: `BadRequestHttpException` (incluye JSON inválido; Symfony envuelve `JsonException` en `BadRequestHttpException`).
    - Formato: `{ "message": "...", "errors": { "body": [ "..." ] } }` cuando el payload JSON es inválido.
    - Otros bad request en esas rutas: `{ "message": "Bad request.", "errors": { "request": [ "..." ] } }`.
  - **422**: `ValidationException` unificada con el mismo estilo que el FormRequest:
    - `{ "message": "Validation failed.", "errors": { ... } }`.
- El `StorePaymentWebhookRequest` sigue usando su propio `failedValidation` con la misma forma (422).

#### Paso 13 - Flujo de servicio: EventLog siempre + upsert idempotente de Payment
- Se ajustó `PaymentWebhookService` al requisito del onboarding:
  - siempre se persiste una fila en `event_logs` (incluye reintentos y duplicados);
  - el `payments` solo se hace upsert si el par `(payment_id, event_id)` **no** había aparecido antes en `event_logs` (evento “nuevo” para ese pago).
- Se agregó `EventLogRepositoryInterface::existsForPaymentAndEventId()` y su implementación en Eloquent.
- Nota: la versión anterior que comparaba solo `payments.last_event_id` con el payload no cubría el caso “reintento de un `event_id` viejo” cuando ya había eventos más nuevos aplicados.

#### Paso 14 - Structured logging de webhooks entrantes
- Se agregó logging estructurado en `PaymentWebhookService` con `Log::info` al recibir cada webhook:
  - contexto: `event_id`, `payment_id`, `user_id`, `event`.
- Se agregó `Log::warning` para detectar duplicados por idempotencia:
  - contexto: `event_id`, `payment_id`, `user_id`.
- Se agregó `Log::info` al actualizar estado de `payments`:
  - contexto: `event_id`, `payment_id`, `user_id`, `last_event_id`.
- Con esto ya queda trazabilidad por request y por resultado de procesamiento en logs.

#### Paso 15 - Frontend Nuxt SSR (Vue) para Week 1
- Se inicializó `frontend` con Nuxt 4 en modo SSR.
- Se configuró `runtimeConfig.public.apiBase` para apuntar al backend Laravel (`http://127.0.0.1:8000` por defecto).
- Se implementó dashboard en `frontend/app/pages/index.vue`:
  - lista de pagos con auto-refresh por polling (cada 5 segundos; luego sustituido por WebSocket en **Paso 17**),
  - click en pago para abrir su historial.
- Se implementó vista de detalle en `frontend/app/pages/payments/[paymentId].vue` para mostrar todos los eventos del pago.
- Se agregaron endpoints proxy en Nuxt para evitar problemas de CORS en navegador:
  - `frontend/server/api/payments.get.ts`
  - `frontend/server/api/payments/[paymentId]/events.get.ts`
- Se añadió composable de estados de pago (hoy `usePaymentEventTypes`, catálogo vía API).
- Se verificó build SSR de Nuxt con `npm run build` exitoso.

#### Paso 16 - Pagination (`limit` + `page`) en backend y frontend
- Backend (`GET /payments`):
  - se validan query params `limit` y `page`,
  - el repository ahora pagina con `paginate(..., 'page', $page)` de forma explícita,
  - respuesta `meta` incluye `current_page`, `per_page`, `total`, `last_page`, `from`, `to`.
- Frontend (Nuxt):
  - la lista consume `/api/payments?limit=...&page=...`,
  - se agregó UI de paginación con botones **Previous/Next**,
  - se muestra rango actual (`from-to`) sobre total.

#### Paso 17 - WebSocket (Laravel Reverb) para refrescar la lista de payments
- **Objetivo**: dejar de usar `setInterval` y refrescar la lista cuando haya novedades, sin enviar datos sensibles por el socket (solo una señal vacía; el cliente vuelve a hacer `GET /payments`).
- **Backend**:
  - dependencia `laravel/reverb` y configuración publicada (`config/broadcasting.php`, `config/reverb.php`, `routes/channels.php`).
  - `.env`: `BROADCAST_CONNECTION=reverb` y variables `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT` (por defecto Reverb usa **8081** para no chocar con el HTTP del backend si corre en **8080**).
  - evento `App\Events\PaymentsListRefreshBroadcast`: canal público `payments`, evento `refresh`, payload vacío (`broadcastWith()`), `ShouldBroadcastNow` (sin cola).
  - en `PaymentWebhookService`, tras cada upsert exitoso (pago nuevo o actualizado), se llama a `broadcast(new PaymentsListRefreshBroadcast())` dentro de `try/catch` para no fallar el webhook si Reverb no está disponible.
  - script `composer run dev`: se agregó el proceso `php artisan reverb:start` en `concurrently`.
- **Frontend (Nuxt)**:
  - dependencias `laravel-echo` y `pusher-js`.
  - plugin cliente `frontend/app/plugins/echo.client.ts` (Echo + Reverb).
  - `nuxt.config.ts`: `runtimeConfig.public` con `reverbKey`, `reverbHost`, `reverbPort`, `reverbScheme` (alineables con `NUXT_PUBLIC_REVERB_*`).
  - `frontend/app/pages/index.vue`: suscripción al canal `payments` y listener `.refresh` que ejecuta el mismo `refresh()` que ya usaba la lista paginada.
- **Operación**: además de Laravel (`php artisan serve` u otro), hay que tener corriendo **Reverb** (`php artisan reverb:start`) para que el broadcast llegue al navegador.
- **Error consola `4001 Application does not exist`**: la clave del cliente Echo no coincide con la app registrada en Reverb. El `REVERB_APP_KEY` de `backend/.env` tiene que ser **exactamente** el mismo que `NUXT_PUBLIC_REVERB_APP_KEY` en el front (o el default `challenge-local-key` en ambos). Tras cambiar `.env`, reiniciar `reverb:start` y, si aplica, `php artisan config:clear`.
- **Causa típica del 4001**: variables `REVERB_*` **duplicadas** en `backend/.env` (por ejemplo un bloque viejo de `reverb:install` al final). Dotenv aplica **la última** definición, así que el servidor Reverb puede estar usando otra clave que el Nuxt. Debe haber **un solo bloque** `REVERB_APP_ID`, `REVERB_APP_KEY`, etc.

### 2026-04-09

#### Paso 18 - Autenticación: login, logout y rutas protegidas (backend + frontend)
- **Backend (Laravel Sanctum)**:
  - dependencia `laravel/sanctum`, migración `personal_access_tokens`, `config/sanctum.php`.
  - modelo `User` con `Laravel\Sanctum\HasApiTokens`.
  - `App\Http\Controllers\Api\Auth\AuthController`: `POST /login` (email/password, token Sanctum), `POST /logout` (revoca token actual), `GET /me` (usuario autenticado).
  - rutas protegidas con `auth:sanctum`: `GET /payments`, `GET /payments/{id}/events`.
  - **público** (sin token): `POST /webhooks/payment` (integraciones externas).
  - `bootstrap/app.php`: rutas `login`, `logout`, `me` incluidas en el estilo JSON de API; manejador de `AuthenticationException` → `401` con `{ "message": "Unauthenticated." }`.
- **Datos de prueba** (`DatabaseSeeder`): usuario `admin@example.com` / `password` (ejecutar `php artisan db:seed` si hace falta).
- **Frontend (Nuxt)**:
  - `app/composables/useAuth.ts`: cookie `auth_token`, estado `user`, `login` / `logout` / `fetchUser`, `authHeaders()` para Bearer.
  - `app/pages/login.vue` (sin layout, middleware `guest`); formulario y credenciales demo en pantalla.
  - middleware `auth` (redirige a `/login` si no hay sesión) y `guest` (si ya hay usuario, a `/`).
  - `app/layouts/default.vue`: barra superior con email y botón **Logout** en páginas autenticadas.
  - `app.vue`: `<NuxtLayout>` envolviendo `<NuxtPage />`.
  - `index.vue` y `payments/[paymentId].vue`: `middleware: 'auth'` y `$fetch` a `/api/...` con header `Authorization`.
  - proxies Nitro: `server/api/login.post.ts`, `server/api/logout.post.ts`, `server/api/me.get.ts`; `payments` y `events` reenvían `Authorization` vía `server/utils/proxyAuth.ts` (`proxyAuthHeaders`).

#### Paso 19 - Login: mensajes de error visibles en la UI (no solo `401` en red)
- El proxy `server/api/login.post.ts` atrapaba el fallo de `$fetch` hacia Laravel y no reenviaba el JSON del backend al navegador; ahora se usa `createError` con el mismo `statusCode` y `data` (`message` / `errors`) para que el cliente pueda leerlos.
- `app/utils/apiError.ts`: helper `getApiErrorMessage()` que prioriza `errors.*` de validación y luego `message`.
- `login.vue`: muestra el texto en español (fallback si no hay cuerpo) y `role="alert"` en el párrafo de error.

#### Paso 20 - `redirectGuestsTo`: evitar `Route [login] not defined` y 500 en rutas API
- Laravel aplica por defecto `redirectGuestsTo(fn () => route('login'))`; esta API no define una ruta **nombrada** `login` (solo existe `POST /login`).
- Si se abre en el navegador una ruta protegida (`GET /payments`, etc.) **sin token**, el middleware intentaba generar la URL de `login` y lanzaba `RouteNotFoundException` → **500** (Ignition) en lugar de **`401` JSON**.
- En `bootstrap/app.php` → `withMiddleware` se sobrescribe con `redirectGuestsTo(fn () => null)` para que no se intente `route('login')` y el flujo llegue al `renderable` de `AuthenticationException` ya existente (`{ "message": "Unauthenticated." }`, 401).

#### Paso 21 - Filtros en `GET /payments` (status/event, fechas, usuario de la transacción, moneda)
- **Backend** (`PaymentController@index` → `ListPaymentsService` → `EloquentPaymentRepository::list`):
  - Query params opcionales:
    - `event` o `status` (mismo significado): filtra por tipo de evento del pago (código alineado al webhook: `payment.created`, `payment.completed`, `payment.failed`, `payment.refunded`). Si se envían ambos, prevalece `event`.
    - `date_from` y `date_to`: rango inclusivo sobre `payments.updated_at` (comparación por fecha).
    - `currency`: código ISO de 3 letras; se normaliza a mayúsculas.
    - **`user_id`**: entero opcional (`min:1`). Si está presente, filtra por **`payments.user_id`**, es decir el **usuario de la transacción** que viene en el payload del webhook, **no** el usuario autenticado que opera el dashboard. Si **no** se envía `user_id`, la lista incluye **todos** los pagos (siempre con sesión Sanctum válida).
  - Validación: si vienen ambas fechas, `date_to` no puede ser anterior a `date_from` (422 con mensaje claro).
  - Coexiste con la paginación existente (`limit`, `page`).
- **Frontend**:
  - `server/api/payments.get.ts`: reenvía al backend todos los query params anteriores (incluido `user_id`) además de `limit` y `page`.
  - `app/pages/index.vue`: barra de filtros (campo **User ID (transaction)**, select de estado/evento, fechas, moneda), botones **Apply** / **Clear**; la query de la ruta alimenta el `$fetch` y el cache key de `useAsyncData`. La tabla muestra la columna `user_id` del pago.

#### Paso 22 - Admin: reembolso manual vía webhook interno (`payment.refunded`)
- **Objetivo**: que un usuario autenticado pueda disparar el mismo procesamiento que `POST /webhooks/payment` con evento `payment.refunded`, sin exponer un HTTP público extra (se reutiliza `PaymentWebhookService`).
- **Backend**:
  - `App\Services\ManualRefundPaymentService`: arma el payload (`event_id` único `refund-{uuid}`, `payment_id`, `event` = `payment.refunded`, `amount` / `currency` / `user_id` desde la fila `payments`, `timestamp` ISO) y llama al servicio del webhook.
  - `App\Http\Controllers\Api\Admin\AdminPaymentRefundController`: `POST /admin/payments/{paymentId}/refund` (Sanctum), `{paymentId}` = `payment_id` de negocio; **404** solo si el pago no existe (el `user_id` de la fila es el de la transacción, no se compara con `auth()->id()`).
  - `bootstrap/app.php`: rutas `admin/*` incluidas en el estilo JSON de errores de API.
- **Frontend**:
  - proxy Nitro `server/api/payments/[paymentId]/refund.post.ts` hacia el backend con `Authorization`.
  - `app/pages/index.vue` (dashboard): en cada fila, enlace **Refund** junto a **View events**; `POST` al proxy, refresco de la lista y banner de éxito/error (`getApiErrorMessage`). La vista de eventos (`payments/[paymentId].vue`) no incluye esta acción.

#### Paso 23 - SproutKit (`@tithely/sproutkit-vue`) por ruta local + PrimeVue
- **Documentación oficial**: [SproutKit Vue — Storybook / docs](https://vue.sproutkit.io/?path=/docs/introduction--docs). Los ejemplos de componentes (`SpSelect`, `SpButton`, etc.) siguen esa referencia; la integración en este repo es Nuxt 4 + Vite.
- **Regla de instalación**: SproutKit **no** se instala desde el registro npm en este proyecto. Siempre va por **`file:`** a tu copia local (p. ej. en Descargas). No reemplazar por `npm i @tithely/sproutkit-vue` sin `file:`, para no desalinear versiones ni rutas.
- **`frontend/package.json`**: `"@tithely/sproutkit-vue": "file:../../../Downloads/sproutkit-vue"` (desde `frontend/`, tres niveles arriba = home del usuario, luego `Downloads/sproutkit-vue`). Si movés el repo o la carpeta, ajustá esa ruta relativa o copiá el repo de SproutKit dentro del monorepo y usá `file:../libs/sproutkit-vue`.
- **PrimeVue v4**: SproutKit está construido sobre PrimeVue; se añadieron `primevue`, `@primeuix/themes` y el módulo de desarrollo `@primevue/nuxt-module` con preset **Aura** en `nuxt.config.ts`.
- **Estilos (según [README de SproutKit](https://github.com/tithely/sproutkit-vue/blob/main/README.md))**: `@import "tailwindcss"`, `@import "tailwindcss-primeui"`, `@import "@tithely/sproutkit-vue/style.css"` y `@source` con **ruta relativa** a `node_modules/@tithely/sproutkit-vue/dist/index.js` desde `app/assets/css/main.css`, más `@source` hacia `app/`. Plugin `@tailwindcss/vite` en `nuxt.config.ts`; `css: ['~/assets/css/main.css']` (`~` = directorio `app/` en Nuxt 4).
- **Registro de componentes**: `app/plugins/sproutkit.ts` registra en la app todos los exportados cuyo nombre empieza por `Sp` (sin depender de auto-import de Nuxt para cada uno).
- **UI refactorizada** (ejemplos de uso): `login.vue` (`SpLabel`, `SpInput`, `SpButton`), `layouts/default.vue` (logout), `index.vue` (filtros, paginación, acciones por fila), `payments/[paymentId].vue` (volver al listado).
- **Nota sobre `npm install`**: el `package.json` original de SproutKit en Descargas traía `"prepare": "husky"`, que falla si no hay `husky` global al instalar como dependencia `file:`. Para este challenge se **eliminó esa línea** del `package.json` de `~/Downloads/sproutkit-vue`. Si trabajás en ese repo con Git hooks, podés volver a añadir `prepare` y tener `husky` instalado, o usar solo una copia “consumo” del `dist/` dentro del proyecto.

#### Paso 24 - Estados de pago desde BD (`payment_event_types`)
- **Tabla** `payment_event_types`: `code` (único), `label`, `sort_order`, `is_refunded`. **Seeder** `PaymentEventTypeSeeder` (`updateOrCreate` por `code`) invocado desde `DatabaseSeeder`; tras migración en BD existente: `php artisan db:seed --class=PaymentEventTypeSeeder`.
- **Persistencia en `payments` y `event_logs`**: columna **`payment_event_type_id`** (FK a `payment_event_types`), no el string `payment.*` duplicado. El **webhook y el contrato externo** siguen enviando el **código** (`event`); el backend resuelve el id al persistir. La **API JSON** sigue exponiendo el atributo **`event`** (código) vía accessor en los modelos, para no romper el front.
- **API** `GET /api/payment-event-types` (Sanctum): lista ordenada para el dashboard. **Front**: proxy `server/api/payment-event-types.get.ts` y composable `usePaymentEventTypes` (filtro del select, `toStatusLabel`, `isRefundedStatus`).
- **Validación**: `PaymentEventType::codes()` con cache en Redis/archivo según `CACHE_STORE`; se invalida al guardar/borrar tipos. Usan esa lista `PaymentController::index` (filtros `event`/`status`) y `StorePaymentWebhookRequest` (webhook).
- **Reembolso manual**: `ManualRefundPaymentService` bloquea si el tipo del pago actual tiene `is_refunded=true`; el campo `event` del payload interno usa el `code` de la fila marcada `is_refunded` (debe existir exactamente una para reembolsos).

### 2026-04-10

#### Paso 25 - Refactor de `index.vue`: extracción de composables
- El archivo `frontend/app/pages/index.vue` tenía ~480 líneas mezclando lógica de filtros, data fetching, refund y template.
- Se extrajo la lógica en composables y tipos reutilizables:
  - `app/types/payment.ts`: tipos `PaymentRow`, `PaymentsMeta`, `PaymentsResponse`.
  - `app/composables/usePaymentFilters.ts`: form reactivo de filtros, modelos de fecha, validación de currency, construcción del query string, paginación.
  - `app/composables/usePaymentsList.ts`: `useAsyncData` para la lista, computed de `payments`/`meta`, suscripción WebSocket para auto-refresh.
  - `app/composables/usePaymentRefund.ts`: acción de refund, estado de loading, banner de resultado, `canRefund`.
- `index.vue` quedó en ~190 líneas: solo conecta composables y define el template.

#### Paso 26 - Catálogo de eventos 100 % desde BD (sin fallbacks hardcodeados)
- `usePaymentEventTypes` ahora es `async` con `await useAsyncData(...)`, asegurando que los datos estén disponibles en SSR antes del render.
- Se eliminaron `FALLBACK_EVENT_LABELS` y el fallback `'payment.refunded'` en `isRefundedStatus`; toda la información viene de la API (`GET /api/payment-event-types`).
- Se propagó `await` a los consumidores: `index.vue`, `payments/[paymentId].vue`, `usePaymentRefund.ts`.

#### Paso 27 - Fix warnings de lifecycle hooks en async setup
- `onMounted` y `onBeforeUnmount` en `usePaymentsList` estaban **después** del `await`, lo cual hace que Vue pierda el contexto de la instancia del componente.
- Se movieron los hooks **antes** del `await`; `refreshFn` se declara como variable, los hooks la referencian vía closure, y tras el `await` se le asigna el `refresh` real.

#### Paso 28 - Webhook solo encola, no procesa inline
- **Objetivo**: el endpoint `POST /webhooks/payment` ya no procesa nada inline; solo valida, encola y devuelve `202 Accepted` inmediatamente.
- **Controller** `PaymentWebhookController`: simplificado a `ProcessPaymentWebhookJob::dispatch($request->validated())` + `response 202`. Ya no inyecta `PaymentWebhookService`.
- **Infraestructura**: `QUEUE_CONNECTION=database` ya estaba configurado; migración `create_jobs_table` (tablas `jobs`, `job_batches`, `failed_jobs`) ya existía.

#### Paso 29 - Job/Worker procesa el evento desde la cola y persiste vía repositorio
- **Job** `App\Jobs\ProcessPaymentWebhookJob` (`ShouldQueue`):
  - Recibe el payload validado en el constructor (se serializa en la tabla `jobs`).
  - En `handle()`, Laravel inyecta `PaymentWebhookService`; el job invoca `($service)($this->payload)`.
  - `PaymentWebhookService` persiste vía `EventLogRepositoryInterface` → `EloquentEventLogRepository` y `PaymentRepositoryInterface` → `EloquentPaymentRepository` (misma lógica de idempotencia, upsert y broadcast WebSocket que antes).
  - 5 reintentos con backoff progresivo (5s, 15s, 60s, 5min).
  - `failed()` loguea si agota todos los intentos.
- **Worker**: para procesar jobs encolados, correr `php artisan queue:work`.

#### Paso 30 - Structured logging con queue context y retry count
- Cada log del Job ahora incluye contexto de cola vía helper privado `queueContext()`: `attempt`, `max_tries`, `queue`, `job_id`, además de `event_id` y `payment_id`.
- 3 puntos de log en el Job:
  - `Log::info('ProcessPaymentWebhookJob starting', ...)` — al iniciar cada intento.
  - `Log::info('ProcessPaymentWebhookJob completed', ...)` — tras procesamiento exitoso.
  - `Log::error('ProcessPaymentWebhookJob failed permanently', ...)` — cuando agota todos los reintentos (incluye `exception`).
- Esto se suma al structured logging existente en `PaymentWebhookService` (`Log::info` al recibir webhook, `Log::warning` en duplicados, `Log::info` en upsert exitoso).
- Todos los logs corren dentro del worker (no en el endpoint HTTP), con datos clave-valor que permiten filtrar y monitorear en herramientas como ELK, CloudWatch, etc.

#### Paso 31 - Mensajes del frontend en inglés
- Se cambiaron todos los mensajes de usuario que estaban en español a inglés: errores de API, validaciones de filtros, banners de refund, empty state de la tabla, y mensaje de login fallido.

#### Paso 32 - Rate limiting en el webhook endpoint
- **Rate limiter** `webhook` definido en `AppServiceProvider::boot()`: 10000 requests por minuto por IP (`Limit::perMinute(10000)->by($request->ip())`).
- Aplicado como middleware `throttle:webhook` en la ruta `POST /webhooks/payment` (`routes/api.php`).
- Respuesta `429 Too Many Requests` con JSON consistente (`{ "message": "Too many requests. Please try again later." }`) y headers estándar (`Retry-After`, `X-RateLimit-*`) manejada en `bootstrap/app.php` vía `TooManyRequestsHttpException`.
- Protege contra picos de tráfico: si un integrador envía más de 60 webhooks/minuto desde la misma IP, recibe 429 sin consumir recursos de cola ni base de datos.

#### Paso 33 - Tests unitarios y de integración (backend)
- Se crearon **8 archivos de test** en `backend/tests/Feature/` cubriendo todas las funcionalidades del backend:
  - `WebhookEndpointTest` — endpoint devuelve 202, despacha job, validaciones (campos requeridos, event code inválido, currency, amount negativo), normalización de currency a mayúsculas.
  - `ProcessPaymentWebhookJobTest` — job persiste payment y event_log, idempotencia, logging estructurado (start/complete/failed), configuración de retry y backoff.
  - `PaymentWebhookServiceTest` — crea event_log y payment, duplicados no actualizan payment, upsert con nuevo evento, broadcast en evento nuevo (no en duplicado), excepción con event code desconocido, FK correcta a payment_event_types.
  - `ManualRefundServiceTest` — refund cambia estado a refunded, refund de pago ya refunded lanza ValidationException, refund crea event_log.
  - `PaymentControllerTest` — listado requiere auth, paginación, filtro por event, filtro por currency, solo muestra pagos propios, historial de eventos, 404 para pagos ajenos, validación de rango de fechas.
  - `AuthControllerTest` — login con credenciales válidas e inválidas, validación de campos, /me con y sin token, logout revoca token.
  - `AdminRefundEndpointTest` — requiere auth, 404 para pago inexistente, 404 para pago de otro usuario, refund exitoso, refund de pago ya refunded devuelve 422.
  - `RepositoryTest` — upsert crea y actualiza, findByPaymentId, list con paginación y filtros, store de event_log, findByPaymentId de logs, existsForPaymentAndEventId.
  - `PaymentEventTypeTest` — codes() devuelve todos los códigos, cache se invalida al crear, endpoint requiere auth, endpoint devuelve tipos.
- Se eliminaron los `ExampleTest` por defecto.
- **Configuración** (`phpunit.xml`): SQLite `:memory:`, `QUEUE_CONNECTION=sync`, `BROADCAST_CONNECTION=null`, `CACHE_STORE=array`.
- **Ejecutar tests**:
  ```bash
  cd backend
  php artisan test
  ```

#### Paso 34 - Refactor: trait compartido para tests backend
- Se creó `tests/Helpers/PaymentTestHelper.php` con un trait que centraliza la creación de datos de test:
  - `validWebhookPayload($overrides)` — payload de webhook con defaults y merge.
  - `createPayment($overrides)` — crea `Payment` resolviendo el `payment_event_type_id` automáticamente desde el código de evento.
  - `createAuthenticatedUser()` — crea `User`, token y headers de auth en una sola línea.
- Se eliminaron métodos `createPayment()` y `authHeaders()` duplicados en 6 archivos de test.
- Mismos 49 tests, mismas 125 assertions, significativamente menos líneas y sin duplicación.

#### Paso 35 - Tests frontend (Vitest + @nuxt/test-utils)
- **Setup**: Vitest con entorno `nuxt` + `happy-dom`, configurado en `vitest.config.ts`.
- Se extrajeron las funciones puras `parseYmd`, `formatYmd`, `normalizeCurrencyQuery` de `usePaymentFilters` a `utils/paymentFiltersHelpers.ts` para testabilidad.
- Se crearon **7 archivos de test** en `frontend/tests/`:
  - `utils/apiError.test.ts` (10 tests) — extracción de mensajes de error, fallbacks, prioridad errors > message > statusMessage, ignorar "Unauthorized".
  - `utils/formatPaymentDateTime.test.ts` (5 tests) — formateo de fechas ISO, strings inválidos, locale consistente en-US.
  - `utils/paymentFiltersHelpers.test.ts` (14 tests) — parseYmd (formatos válidos/inválidos), formatYmd (padding, null), normalizeCurrencyQuery (uppercase, trim, validación 3 letras).
  - `composables/usePaymentEventTypes.test.ts` (5 tests) — filterSelectOptions, toStatusLabel, isRefundedStatus con mocks de useAsyncData.
  - `composables/usePaymentFilters.test.ts` (13 tests) — limit/page defaults y clamping, queryString, applyFilters con validación de currency, clearFilters, goToPage, onPaginatorPage.
  - `composables/usePaymentRefund.test.ts` (6 tests) — canRefund para estados refunded/no-refunded, triggerRefund (skip refunded, API call, success/error banners).
  - `composables/useAuth.test.ts` (8 tests) — authHeaders con/sin token, login/logout, fetchUser, manejo de errores.
- **61 tests en total**, todos pasando.
- **Ejecutar tests**:
  ```bash
  cd frontend
  npm test
  ```

### Week 4 — Fullstack Track

#### Paso 36 - DB indexes + optimización de queries (fs-6, fs-7)
- **Migración** `2026_04_13_100000_add_performance_indexes`:
  - `payments.updated_at` — usado en `ORDER BY` y filtros de rango de fechas.
  - `payments.currency` — usado en filtro de moneda.
  - `event_logs.timestamp` — usado en `ORDER BY` al listar historial de eventos.
  - `event_logs.(payment_id, event_id)` — índice compuesto para el check de idempotencia (`existsForPaymentAndEventId`).
- **Optimización de query**: el filtro por evento en `EloquentPaymentRepository::list()` usaba `whereHas('eventType', ...)` que genera un subquery correlacionado. Se reemplazó por resolución directa del `payment_event_type_id` via `PaymentEventType::where('code', ...)->value('id')` y filtro directo por FK — elimina el subquery, aprovecha el índice de la FK.
- **Eager loading** ya existente en ambos repositorios (`with('eventType')`) — sin N+1.
- 49 tests backend pasando sin regresiones.

#### Paso 37 - GET /payments/metrics + dashboard con counters y charts (fs-1, fs-2)
- **Backend**:
  - Nuevo servicio invocable `PaymentMetricsService` que retorna:
    - `total`: cantidad total de filas en `payments` (**todos** los pagos visibles para la herramienta; el filtro por `user_id` de transacción aplica solo en `GET /payments` y `GET /payments/export`).
    - `by_status`: array con conteo por cada tipo de evento (incluyendo tipos con count 0), ordenado por `sort_order`.
    - `by_day`: array con conteo agrupado por día (`DATE(updated_at)`).
    - `by_currency`: array con conteo agrupado por moneda, ordenado por cantidad descendente.
  - Nuevo controller `PaymentMetricsController` en `Api/Payments/PaymentMetricsController.php`.
  - Ruta `GET /payments/metrics` protegida por `auth:sanctum` (sin query params de filtro).
  - Tests en `PaymentMetricsEndpointTest`: auth, totales, by status/day/currency, alcance global, vacío sin datos.
- **Frontend**:
  - Proxy Nitro `server/api/payment-metrics.get.ts` hacia `GET /payments/metrics`.
  - `paymentsApi.metrics()` y `useMetricsStore.fetch()` sin query string.
  - Composable `usePaymentMetrics.ts`: fetch inicial de métricas al entrar a la página; sin suscripción WebSocket (la lista del dashboard sí se refresca en vivo).
  - Tipos `StatusMetric`, `DayMetric`, `CurrencyMetric`, `MetricsResponse` en `types/payment.ts`.
  - Dashboard (`index.vue`): sección de métricas arriba de la tabla con:
    - **Counters**: card de total + card por cada status.
    - **Doughnut chart**: distribución por status (PrimeVue Chart + chart.js).
    - **Bar chart**: volumen de payments por día.
    - **Doughnut chart**: distribución por moneda.
  - Colores asignados dinámicamente por índice desde un palette genérico (escalable a nuevos tipos o monedas).
  - Métricas se actualizan automáticamente con WebSocket (misma señal que la tabla).
  - `chart.js` agregado como dependencia.
- **Ejecutar tests**:
  ```bash
  cd backend && php artisan test    # 56 tests
  cd frontend && npm test           # 61 tests
  ```

#### Paso 38 - CSV export con filtros activos (fs-5)
- **Backend**:
  - Nuevo método `listAll(filters)` en `PaymentRepositoryInterface` y `EloquentPaymentRepository`: misma lógica de filtros que `list()` pero sin paginación (retorna `Collection`). Se extrajo la lógica de filtros a `buildFilteredQuery()` para reutilizar entre ambos métodos.
  - Nuevo servicio invocable `ExportPaymentsCsvService`: recibe filtros, obtiene todos los payments via repositorio, genera `StreamedResponse` con CSV (columnas: `payment_id`, `status`, `amount`, `currency`, `user_id`, `updated_at`).
  - Nuevo controller `PaymentExportController` en `Api/Payments/PaymentExportController.php`.
  - Ruta `GET /payments/export` protegida por `auth:sanctum`, acepta mismos query params de filtro que `GET /payments` (incluido `user_id` de transacción).
  - Tests en `PaymentExportTest`: auth, CSV, filtros por event/currency, alcance global vs filtro `user_id`.
- **Frontend**:
  - Proxy Nitro `server/api/payments/export.get.ts` que reenvía filtros y devuelve el CSV como texto.
  - Botón **"Export CSV"** en el dashboard (`index.vue`): descarga el CSV respetando los filtros activos. Usa `$fetch` con `responseType: 'text'` + `Blob` + `URL.createObjectURL` para disparar la descarga.
- **Ejecutar tests**:
  ```bash
  cd backend && php artisan test    # 61 tests
  cd frontend && npm test           # 61 tests
  ```

#### Paso 39 - Benchmark de latencia del dashboard (fs-8)
- **Comando** `php artisan app:benchmark` — seedea N payments, dispara M requests por endpoint y reporta avg/min/max/p95 en milisegundos.
  - Opciones: `--payments=500`, `--requests=50`, `--base-url=http://127.0.0.1:8000`.
  - Mide **GET** (lectura), **POST** (escritura webhook), y **Round-trip** (POST webhook → payment visible en GET, requiere `queue:work` corriendo).
- **Resultados** (MySQL local, MacOS, `php artisan serve`, 500 payments, 20 requests/endpoint):

| Endpoint | Avg (ms) | Min (ms) | Max (ms) | P95 (ms) |
|---|---|---|---|---|
| `GET /payments` (paginado) | 10.4 | 8.3 | 26.5 | 20.9 |
| `GET /payments` (filtrado) | 13.8 | 10.2 | 27.3 | 24.3 |
| `GET /payments/metrics` | 17.0 | 14.9 | 25.2 | 18.8 |
| `GET /payments/export` (todos) | 97.6 | 93.9 | 104.1 | 101.0 |
| `GET /payments/export` (filtrado) | 31.0 | 29.2 | 35.3 | 32.7 |
| `POST /webhooks/payment` | 24.3 | 8.1 | 195.3 | 42.7 |
| **Round-trip POST→GET** | **112.1** | 43.6 | 157.2 | **129.2** |

- **Análisis**:
  - Los endpoints GET paginados se mantienen **< 25ms p95** gracias a indexes y eager loading.
  - `POST /webhooks/payment` promedia **24ms** porque solo valida, encola el job y devuelve `202 Accepted` — no bloquea.
  - El **round-trip completo** (POST webhook → job procesado → payment persistido → visible en GET) tiene un **p95 de ~129ms**, confirmando que el flujo end-to-end está **muy por debajo del umbral de 2 segundos**.
  - El export CSV sin filtros (~98ms) escala con la cantidad de datos; con filtros baja a ~31ms.
- **Ejecutar benchmark** (requiere `php artisan serve` y `php artisan queue:work` activos en terminales separadas):
  ```bash
  cd backend
  php artisan queue:work --sleep=0.1   # terminal 1
  php artisan app:benchmark --payments=500 --requests=20  # terminal 2
  ```

---

#### Paso 40 - Módulo API + Pinia stores en frontend
- **Dependencias**: `@pinia/nuxt` y `pinia` instalados; módulo `@pinia/nuxt` registrado en `nuxt.config.ts`.
- **Módulo API** (`app/api/`) — capa centralizada de llamadas HTTP tipadas que reemplaza `$fetch` sueltos:
  - `client.ts`: wrappers `apiGet<T>()` y `apiPost<T>()` que inyectan `Authorization: Bearer <token>` automáticamente desde la cookie `auth_token`.
  - `auth.ts`: `authApi.login()`, `.me()`, `.logout()`.
  - `payments.ts`: `paymentsApi.list()`, `.events()`, `.refund()`, `.metrics()`, `.exportCsv()`.
  - `eventTypes.ts`: `eventTypesApi.list()`.
- **Pinia Stores** (`app/stores/`) — manejo de estado centralizado con persistencia:
  - `auth.ts` (`useAuthStore`): `token` (cookie persistida 7 días), `user`, `isAuthenticated` + acciones `login`, `logout`, `fetchUser`.
  - `payments.ts` (`usePaymentsStore`): `payments`, `meta`, `loading`, `error`, `refundBanner`, `refundingPaymentId` + acciones `fetchList`, `refund`.
  - `eventTypes.ts` (`useEventTypesStore`): `types`, `loaded` + acción `fetch` + helpers `filterSelectOptions`, `toStatusLabel`, `isRefundedStatus`.
  - `metrics.ts` (`useMetricsStore`): `total`, `byStatus`, `byDay`, `byCurrency`, `loading` + acción `fetch` + computeds `statusChartData`, `dailyChartData`, `currencyChartData`.
- **Composables refactorizados** como thin wrappers sobre los stores (misma API pública, sin romper páginas):
  - `useAuth` → delega a `useAuthStore`.
  - `usePaymentEventTypes` → delega a `useEventTypesStore`.
  - `usePaymentRefund` → delega a `usePaymentsStore` + `useEventTypesStore`.
  - `usePaymentMetrics` → delega a `useMetricsStore`.
  - `usePaymentsList` → usa `paymentsApi` directamente + sincroniza con `usePaymentsStore`.
- **Páginas actualizadas**: `index.vue` y `payments/[paymentId].vue` usan `paymentsApi` del módulo API en vez de `$fetch` directo.
- **Tests actualizados**: los mocks ahora apuntan a los stores en vez de `$fetch`/`useAsyncData` directo.
- **Build exitoso + 61 frontend tests pasando, 61 backend tests pasando** (cero regresiones).

---

> A partir de este punto, cada cambio nuevo se ira registrando aqui (incluida esta bitácora: **actualizar el README con cada tarea o entrega relevante**).