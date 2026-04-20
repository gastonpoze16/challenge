# DevOps — guía del repositorio

Esta guía resume cómo el challenge cumple con **Dockerización**, **CI/CD**, **entornos separados**, **Datadog** y **health checks**.

La configuración por entorno (dev / staging / prod) definila en **`backend/.env`** en cada servidor, en **`.env.compose`** para Compose local, y en **variables/secrets** de GitHub Environments si usás CD; no hay archivos de ejemplo extra bajo `docker/env/` en el repo.

## 1. Docker (backend, base de datos, worker)

| Pieza | Dónde | Rol |
|-------|--------|-----|
| **API PHP** | `docker-compose.yml` → servicio `app` | `php artisan serve` en **8000** |
| **MySQL** | servicio `mysql` | Datos; puerto host típico **3307** |
| **Worker** | servicio `queue` | `php artisan queue:work` |
| **Reverb** | servicio `reverb` | WebSockets **8081** |
| **Frontend** | servicio `frontend` (opcional en dev) | Nuxt dev en **3000** |

- Arranque local: `docker compose --env-file .env.compose up --build` (plantilla: `docker/compose.env.example` → copiar como `.env.compose` en la raíz).
- Imagen del backend: `backend/Dockerfile` (incluye healthcheck HTTP a `GET /up`).

**Producción en EC2 (script del repo):** `scripts/ec2/deploy-backend.sh` construye la imagen y levanta contenedores con `docker run` (equivalente operativo al stack; no usa el `docker-compose.yml` completo del servidor salvo que lo configures vos).

## 2. CI/CD (GitHub Actions)

Workflow: [`.github/workflows/ci.yml`](../.github/workflows/ci.yml).

| Job | Cuándo |
|-----|--------|
| Tests backend (PHPUnit) | Push / PR a `main`, `master`, `staging` |
| Tests frontend (Vitest) | Igual |
| Deploy backend | Push a `main`/`master` → entorno **production**; push a `staging` → entorno **staging** |
| Deploy frontend | Misma regla, si `DEPLOY_FRONTEND_HOST` está definido en ese entorno |

Los scripts de despliegue: [`scripts/ec2/deploy-backend.sh`](../scripts/ec2/deploy-backend.sh), [`scripts/ec2/deploy-frontend.sh`](../scripts/ec2/deploy-frontend.sh).

## 3. Entornos separados (dev / staging / prod)

Modelo recomendado: **tres capas lógicas**, misma base de código, **URLs y secretos distintos**.

| Entorno | Uso típico | Automatización en CI |
|---------|------------|----------------------|
| **dev** | Máquina local (`docker compose`, `npm run dev`) | No hay deploy automático desde GitHub |
| **staging** | Rama `staging`, datos de prueba | Job de deploy con **GitHub Environment** `staging` |
| **prod** | Rama `main` o `master` | Job de deploy con **GitHub Environment** `production` |

### Configuración en GitHub

1. **Settings → Environments** → crear **`staging`** y **`production`** (opcional: aprobadores en prod).
2. En **cada entorno**, definir las **variables** que usan los jobs (mismos nombres que en repo, valores distintos por host):

   - `DEPLOY_BACKEND_HOST`, `DEPLOY_BACKEND_USER`, `DEPLOY_CHALLENGE_ROOT`
   - Opcional: `DEPLOY_FRONTEND_HOST`, `DEPLOY_FRONTEND_USER`

3. El **secret** `DEPLOY_SSH_PRIVATE_KEY` puede ser el mismo a nivel **repositorio** o duplicado por entorno si usás claves distintas.

Las variables del **entorno** tienen prioridad sobre las variables del repositorio cuando el job declara `environment: staging` u `production`.

## 4. Datadog (APM, logs, dashboards)

### Backend (PHP / Laravel)

- Imagen: **dd-trace-php** instalado en `backend/Dockerfile`.
- Variables: comentadas en `backend/.env.example` (`DD_*`).
- En el servidor: [Datadog Agent](https://docs.datadoghq.com/agent/) en el host; desde contenedores, `DD_AGENT_HOST` / `DD_TRACE_AGENT_URL` apuntando al Agent.
- Logs JSON: canal `stderr_json` en `config/logging.php` (ver README principal).
- En CI los tests usan `DD_TRACE_ENABLED=false`.

Convención: alinear **`DD_ENV`** con el entorno (`development`, `staging`, `production`) en `backend/.env` o variables del contenedor.

### Dashboards

En Datadog: **APM → Services** filtrando por `DD_SERVICE`, o **Dashboards → New dashboard** desde plantillas **APM** / **Host**.

### Frontend

El front Nuxt no lleva tracer APM en el repo por defecto; opcional: [Browser RUM](https://docs.datadoghq.com/real_user_monitoring/) en `nuxt.config` y API keys vía variables de entorno en build.

## 5. Health checks

| Ruta | Significado |
|------|-------------|
| `GET /up` | Liveness (framework); sin comprobar BD. |
| `GET /health` | Readiness: comprueba BD vía `HealthReadinessService`; **503** si falla. |

En Docker: `HEALTHCHECK` en `backend/Dockerfile` y bloques `healthcheck` en `docker-compose.yml` (mysql, app, queue, reverb, frontend).

Monitores externos (balanceador, Datadog Synthetic): usar **`/health`** para señal “listo para tráfico”.

## Checklist rápido

- [ ] Local: `docker compose up`, migraciones, front con `NUXT_PUBLIC_*` coherentes.
- [ ] GitHub: Environments `staging` / `production` + variables por host.
- [ ] EC2: repo clonado, `backend/.env` con `APP_ENV` y `DD_*` acordes al entorno.
- [ ] Agent Datadog en el host si querés APM/logs en la nube.
- [ ] Security groups: **22** (deploy SSH), **8000** / **3000** / **80** / **443** según expongas.
