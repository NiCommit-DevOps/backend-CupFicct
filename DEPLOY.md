# Backend CUP FICCT — Despliegue en Railway

API en **Laravel 12 / PHP 8.2**. Se construye con el `Dockerfile` incluido
(PHP-Apache + PostgreSQL). Al arrancar corre `migrate --force` y los seeders
(idempotentes): crea el esquema, roles, permisos, materias, horarios y el admin.

## Pasos

1. En tu proyecto de Railway: **+ New → Database → PostgreSQL**.
2. **+ New → GitHub Repo** → este repositorio. Railway detecta el `Dockerfile`.
3. **Settings → Networking → Generate Domain** (URL pública del API).
4. **Variables** del servicio:

   ```
   APP_NAME=CUP FICCT
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=                       # php artisan key:generate --show
   APP_URL=https://<backend>.up.railway.app

   JWT_SECRET=                    # php artisan jwt:secret --show

   DB_CONNECTION=pgsql
   DB_URL=${{ Postgres.DATABASE_URL }}
   DB_SSLMODE=prefer

   CORS_ALLOWED_ORIGINS=https://<frontend>.up.railway.app
   FRONTEND_URL=https://<frontend>.up.railway.app

   SESSION_DRIVER=file
   CACHE_STORE=file
   QUEUE_CONNECTION=sync

   ADMIN_EMAIL=admin@ficct.uagrm.edu.bo
   ADMIN_PASSWORD=Admin12345

   # PayPal (opcional, solo si se usa el pago)
   PAYPAL_MODE=sandbox
   PAYPAL_SANDBOX_CLIENT_ID=
   PAYPAL_SANDBOX_CLIENT_SECRET=
   ```

## Generar claves (en local, dentro de esta carpeta)

```bash
php artisan key:generate --show     # -> APP_KEY
php artisan jwt:secret --show       # -> JWT_SECRET
```

## Verificación

`https://<backend>.up.railway.app/api/v1/ping` → `{"status":"ok"}`

## Notas

- Los `.env` reales no se versionan; solo `.env.example` como referencia.
- Los seeders son idempotentes: reiniciar no duplica datos.
- **No** se ejecuta `migrate:fresh` en producción (borraría todo).
- Local: copia `.env.example` → `.env` y usa `composer run dev`.
