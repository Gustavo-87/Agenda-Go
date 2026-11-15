# AgendaGo — Despliegue y soporte

Guía operativa para desplegar y mantener AgendaGo (Sprint 8).
Última actualización: 2026-11-15.

## Requisitos

- PHP 8.5, Composer 2, Node 20+ (Vite), base de datos (SQLite/MySQL/PostgreSQL).
- Cola de trabajos y planificador de tareas activos.

## Configuración

1. `composer install --no-dev` y `npm ci && npm run build`.
2. Copiar `.env.example` a `.env`, definir `APP_NAME=AgendaGo` y `php artisan key:generate`.
3. `php artisan migrate --force`.
4. Variables sensibles (`DB_*`, `WHATSAPP_*`, credenciales de correo) solo en `.env` o gestor de secretos. Nunca en el repositorio.

## Colas y tareas programadas

- Cola recomendada: `database` o `redis` (los reintentos de notificaciones usan `tries=3`).
- Planificador (`php artisan schedule:run` cada minuto):
  - `appointments:expire` cada 5 minutos — expira solicitudes pendientes vencidas.
  - `appointments:remind` cada hora — recordatorios de 24 horas.
  - `memberships:notify-expiring` diario 08:00 — aviso de vencimiento de membresías (RN-010).
  - `backup:database` diario 02:00 — copia de seguridad local.

## Copias de seguridad y restauración

- `php artisan backup:database` copia el archivo SQLite a `storage/app/backups/`.
- Restauración: detener la aplicación, reemplazar el archivo de datos por la copia y verificar con `php artisan migrate:status` + pruebas de humo.
- Pendiente externo: replicar las copias en almacenamiento externo y agendar ejercicios de restauración periódicos.

## Seguridad

- 2FA obligatorio para administradores y superadministradores (RN-043): el login siempre desafía código por correo.
- Permisos: `BusinessPolicy`, `ProfessionalPolicy` y `ServicePolicy` aíslan cada negocio; el panel `/admin` exige superadministrador.
- Auditoría: `audit_logs` registra altas, activaciones, suspensiones y verificación de pagos.
- Revisión de credenciales: verificar que ningún secreto esté versionado antes de cada despliegue.

## Monitoreo y errores

- Los fallos se registran con `report()` y en `failed_jobs`.
- Recomendado: monitoreo externo de errores y disponibilidad (pendiente de selección de proveedor).

## Estado de integraciones externas (no verificadas)

- WhatsApp Cloud API: código multiempresa implementado (identificación por Phone Number ID, idempotencia, trazabilidad de mensajes). **No verificado con Meta**; requiere habilitación comercial y validación de facturación.
- Pasarela de pagos: **sin proveedor definido** (los pagos de servicios se registran manualmente).
- Copias externas y restauraciones: pendientes de infraestructura.
- Aprobación de pruebas y piloto con Viglam: pendiente (Sprint 9).

## Soporte

- Registro de operaciones relevantes: tabla `audit_logs`.
- Trazabilidad de comunicaciones: tabla `whatsapp_messages` (entrantes/salientes con estado).
- Reportes exportables en CSV, Excel y PDF por negocio (`/businesses/{business}/reports/...`).
- Ante incidentes: revisar `storage/logs`, `failed_jobs` y `audit_logs`; reproducir con datos sintéticos.
