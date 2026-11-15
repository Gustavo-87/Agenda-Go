# AgendaGo — Avance y trazabilidad

Registro de decisiones, avances y pendientes para retomar el desarrollo en otra sesión.
Última actualización: 2026-11-15.

## Estado de sprints

| Sprint | Nombre | Estado |
|---|---|---|
| 1 | Arquitectura multiempresa | Completado |
| 2 | Catálogo, profesionales y horarios | Completado |
| 3 | Motor de reservas robusto | Completado |
| 4 | WhatsApp multiempresa | Completado (verificación Meta pendiente) |
| 5 | Membresías y administración SaaS | Completado (renovación automática pendiente de proveedor) |
| 6 | Clientes y comunicaciones | Completado (push real pendiente de proveedor) |
| 7 | Pagos de servicios y reportes | Completado (integración de pago pendiente de proveedor) |
| 8 | Seguridad y preparación productiva | Código completo; criterios de cierre externos pendientes |
| 9 | Piloto comercial Viglam | No iniciado (requiere acciones con Viglam) |

## Sprint 1 — Arquitectura multiempresa

Commits: `4173053` (entidad Business y Viglam), `b1158be` (roles y autorización), `11f1ecd` (aislamiento de reservas y consultas).
Criterios verificados: sin acceso entre negocios; reservas de Viglam conservadas.

## Sprint 2 — Catálogo, profesionales y horarios

Commits: `53db3ac` (profesionales, RN-002), `4425da6` (servicios), `60b53d4` (jornadas, descansos, excepciones), `07694a3` (festivos).
Criterios verificados: Viglam configura sus horarios reales; otros negocios configuran servicios distintos.

## Sprint 3 — Motor de reservas robusto

Commit: `a631bb6`.
- Disponibilidad centralizada (`DisponibilidadService`): jornadas, descansos, vacaciones, bloqueos, festivos, excepciones, anticipación mínima/máxima y ocupación.
- Estados de reserva (`AppointmentStatus`) con transiciones validadas: pendiente → confirmada/cancelada/expirada; confirmada → completada/ausente/cancelada.
- Confirmación automática o manual por negocio (RN-021), vencimiento de solicitudes pendientes (`appointments:expire`, RN-022) y cancelación/reprogramación (RN-025).
- Protección de doble reserva con verificación en transacción y bloqueo pesimista (`lockForUpdate`).
Criterios verificados: no se permiten reservas incompatibles; la prueba de solicitudes simultáneas pasa.

## Sprint 4 — WhatsApp multiempresa

Commit: `2d7e367`.
- Identificación del negocio por Phone Number ID (`businesses.whatsapp_phone_number_id`) con reserva al canal configurado.
- Webhooks idempotentes (deduplicación por `wa_message_id`) y trazabilidad de mensajes (`whatsapp_messages`).
- Flujo de reserva desacoplado del canal (contexto de negocio por parámetro).
- Notificaciones de confirmación y cancelación con reintentos (`EnviarNotificacionWhatsApp`, `tries=3`).
Pendiente externo: verificación con Meta (habilitación comercial, facturación) — ver `docs/despliegue.md`.

## Sprint 5 — Membresías y administración SaaS

Commit: `9c6ccb4`.
- Panel del superadministrador (`/admin`): altas, activación manual, suspensión, membresías, pagos y renovación manual (RN-003..RN-008).
- Cinco días de gracia y bloqueo de nuevas reservas (RN-005/006), sin pérdida de histórico (RN-007).
- Aviso de vencimiento por WhatsApp (`memberships:notify-expiring`, RN-010).
Pendiente externo: renovación automática requiere proveedor de cobro autorizado.

## Sprint 6 — Clientes y comunicaciones

Commit: `635a2b6`.
- Ficha e historial de clientes por negocio (`customers`, `appointments.customer_id`).
- Asistencia y ausencias (`completed`/`absent`) con acumulado de ausencias y alertas al profesional (RN-027).
- Consentimiento de tratamiento de datos (`data_consent`, `data_consent_at`).
- Recordatorios de 24 horas (`appointments:remind`) y trazabilidad de comunicaciones.
- PWA (`public/manifest.webmanifest`, `public/sw.js`, iconografía `public/icons/`) con compatibilidad de notificaciones push.
Pendiente externo: suscripción push real requiere proveedor y claves VAPID.

## Sprint 7 — Pagos de servicios y reportes

Commit: `94774dc`.
- Pagos de servicios por negocio (`service_payments`), separados de membresías (RN-042), con verificación y comprobante digital.
- Indicadores operativos y reportes filtrables (`/businesses/{business}/reports/summary`).
- Exportación CSV, Excel (PhpSpreadsheet) y PDF (Dompdf) de citas.
Pendiente: integración de pago completo requiere proveedor.

## Sprint 8 — Seguridad y preparación productiva

Commit: `774a1c0`.
- 2FA para administradores (RN-043): desafío obligatorio por correo en el login, verificación de un solo uso.
- Auditoría de operaciones relevantes (`audit_logs`).
- Copias de seguridad locales (`backup:database`) y procedimiento de restauración documentado.
- Prueba integral de aceptación del ciclo operativo (`AcceptanceFlowTest`).
- Documentación de despliegue y soporte (`docs/despliegue.md`).
Criterios de cierre pendientes (externos): copias restaurables verificadas, integraciones verificadas y aprobación de pruebas con Viglam.

## Decisiones

- D-01: servicio asignado a un profesional (FK `professional_id`), agrupado por `business_id`.
- D-02: `weekday` de 0 (lunes) a 6 (domingo).
- D-03: excepciones `vacation | block | extra`; `extra` exige horario.
- D-04: catálogo bajo `/businesses/{business}/...` con `BusinessPolicy::manage`.
- D-05: validaciones de configuración en capa de aplicación.
- D-06: sin jornadas configuradas se conserva la ventana MVP 09:00–18:00.
- D-07: políticas de reserva por negocio con valores por defecto configurables (aviso 60 min, anticipación 30 días, TTL pendientes 30 min).
- D-08: negocio sin membresías registradas conserva su comportamiento histórico; la membresía rige al registrarse.
- D-09: 2FA por código de correo (sin dependencias externas); el panel `/admin` exige verificación.
- D-10: exportación de reportes en CSV, Excel (PhpSpreadsheet) y PDF (Dompdf), con aislamiento por negocio.

## Pendientes para continuar

1. Sprint 9 — Piloto comercial Viglam: requiere configuración productiva, número comercial habilitado, capacitación y acta de aceptación (acciones externas).
2. Verificación real de WhatsApp Cloud API con Meta (pruebas con número comercial y facturación).
3. Selección de proveedor de pagos (renovación automática de membresías y recaudo de servicios).
4. Copias en almacenamiento externo y ejercicios de restauración.
5. Suscripción push real (VAPID / proveedor externo).
6. Extender 2FA/auditoría si el alcance lo exige en nuevas rutas.
