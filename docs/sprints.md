# AgendaGo — Avance y trazabilidad

Registro de decisiones, avances y pendientes para retomar el desarrollo en otra sesión.
Última actualización: 2026-11-15.

## Estado de sprints

| Sprint | Nombre | Estado |
|---|---|---|
| 1 | Arquitectura multiempresa | Completado |
| 2 | Catálogo, profesionales y horarios | Completado |
| 3 | Motor de reservas robusto | Pendiente |
| 4 | WhatsApp multiempresa | Pendiente |
| 5 | Membresías y administración SaaS | Pendiente |
| 6 | Clientes y comunicaciones | Pendiente |

## Sprint 1 — Arquitectura multiempresa

Entregables y commits:
- Entidad Business y vinculación de datos de Viglam (`4173053`).
- Roles (superadministrador, administrador, profesional), usuarios por negocio y `BusinessPolicy` (`b1158be`).
- Aislamiento de reservas y consultas por negocio (`11f1ecd`).

Criterios de cierre verificados:
- Ningún usuario accede a recursos de otro negocio (pruebas de acceso cruzado).
- Las reservas existentes de Viglam se conservan (`ViglamBusinessSeederTest`).

## Sprint 2 — Catálogo, profesionales y horarios

Entregables y commits:
- Gestión de profesionales por negocio con límite de 5 activos (RN-002) (`53db3ac`).
- Catálogo de servicios por negocio, duraciones y precios configurables, asignación a profesionales (`4425da6`).
- Jornadas divididas, descansos y excepciones: vacaciones, bloqueos y horarios extra (`60b53d4`).
- Festivos configurables por negocio (`07694a3`).

Criterios de cierre verificados:
- Viglam puede configurar sus horarios reales (jornadas, descansos, vacaciones, bloqueos y festivos).
- Otro negocio puede configurar servicios distintos (aislamiento por negocio probado).

## Decisiones

- D-01: el servicio se asigna a un profesional (FK `professional_id`) y se agrupa por `business_id`; no se crea tabla pivote en este alcance.
- D-02: `weekday` va de 0 (lunes) a 6 (domingo) en `work_schedules`.
- D-03: las excepciones usan el tipo `vacation | block | extra`; `extra` exige horario de inicio y fin.
- D-04: los endpoints de catálogo cuelgan de `/businesses/{business}/...` y se autorizan con `BusinessPolicy::manage`; la gestión de profesionales y horarios usa `ProfessionalPolicy`/`ServicePolicy`.
- D-05: los límites y validaciones de configuración viven en capa de aplicación.

## Pendientes

- Consumir jornadas, descansos, festivos y excepciones en el cálculo de disponibilidad (Sprint 3).
- Protección de reservas simultáneas y estados de reserva (Sprint 3).
- Interfaces de administración (por ahora solo API JSON autenticada).
- Mapeo de número de WhatsApp por negocio (Sprint 4).
- Límite de 5 profesionales activos sin restricción a nivel de base de datos.
