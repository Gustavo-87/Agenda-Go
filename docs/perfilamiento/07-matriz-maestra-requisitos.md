# AgendaGo — Matriz maestra de requisitos

Documento vinculante que consolida la entrevista de perfilamiento y los documentos
aprobados en un solo punto de verdad para las próximas implementaciones.

- Fuentes: `01-decisiones-aprobadas.md`, `02-requisitos-funcionales.md`, `03-requisitos-no-funcionales.md`, `04-reglas-negocio.md`, `05-arquitectura.md`, `06-plan-sprints.md`, `../../../sprints.md` y el código/pruebas del repositorio.
- Estados: **Implementado** (código + prueba), **Parcial**, **Pendiente**, **Condicionado** (depende de una decisión o proveedor externo).
- Regla de oro: este documento no crea decisiones nuevas; registra las aprobadas, su trazabilidad y lo que falta por confirmar.

## A. Identificadores preservados de la entrevista original

Los IDs de la entrevista se conservan tal cual y se relacionan con los IDs posteriores
sin duplicar requisitos:

| ID original (entrevista) | ID consolidado | Requisito |
|---|---|---|
| RF-MEM-01 | RF-MEM-001 | Plan mensual único |
| RF-MEM-02 | RF-MEM-002 | Pagos de membresía y primera vigencia |
| RF-MEM-03 | RF-MEM-003 | Renovaciones |
| RF-MEN-01 | RF-MEN-001 (nuevo en esta matriz) | Mensajería incluida y excedentes |

Observación: `02-requisitos-funcionales.md` usa numeración de tres dígitos (RF-MEM-001…);
la entrevista usaba dos dígitos. No se reescriben los documentos de origen.

## B. Matriz de requisitos

### B1. Plataforma SaaS

| ID | Decisión y regla verificable | Origen | Estado | Evidencia | Validación manual |
|---|---|---|---|---|---|
| RN-000 / Dec-01 | SaaS multisectorial: cada negocio define su sector y opera aislado. | 01 §14, 02 RF-SIS | Implementado | `businesses.sector`; `BusinessPolicy`; `BusinessIsolationTest` | — |
| RF-MEM-01 → RF-MEM-001 | Un plan mensual único con todas las funcionalidades contratadas. | 02 §2 | Implementado | `create_memberships_table`; `MembershipAdministrationTest` | — |
| RN-002 | Máximo 5 profesionales activos por negocio; crear/reactivar respeta el límite. | 04 | Implementado | `ProfessionalController::assertLimiteProfesionalesActivos`; `ProfessionalManagementTest` | — |
| Dec-06 | El superadministrador gestiona la plataforma, nunca las agendas de sus clientes. | 01, 04 RN-013 | Implementado | `BusinessPolicy` (sin bypass), guardas en Agenda/Clientes, `SaaSAdministrationTest` | — |
| Dec-07 | El administrador gestiona profesionales, catálogo, precios, horarios, reservas, clientes y reportes; el profesional controla su disponibilidad y citas autorizadas. | 01, 04 RN-014/015 | Implementado | Gestión/Agenda/Clientes/Reportes; "Mi disponibilidad" en Configuración; `ScheduleConfigurationTest`, `ProfessionalManagementTest` | — |

### B2. Membresías

| ID | Decisión y regla verificable | Origen | Estado | Evidencia | Validación manual |
|---|---|---|---|---|---|
| RF-MEM-02 → RF-MEM-002 | La primera vigencia comienza solo con pago confirmado y servicio habilitado; la configuración inicial es gratuita y no consume vigencia. | 02 §2, 04 RN-003/004 | Parcial | `AdminController::verifyPayment` activa negocio y membresía; `MembershipAdministrationTest`, E2E | Definir/validar la fase de configuración gratuita (hoy el negocio inactivo puede configurarse) |
| RF-MEM-03 → RF-MEM-003 | Renovación manual o cobro automático previamente autorizado. | 02 §2, 04 RN-008 | Parcial | `AdminController::renew`; `MembershipAdministrationTest` | Cobro automático condicionado a proveedor de pagos **no aprobado** |
| RF-MEM-004 | 5 días de gracia tras el vencimiento y luego suspensión de nuevas reservas, conservando datos. | 02 §2, 04 RN-005/006/007 | Implementado | `Business::puedeReservar()`; `MembershipAdministrationTest` | — |
| RF-MEM-005 | Aviso por WhatsApp 5 días antes del vencimiento, salvo renovación confirmada. | 02 §2, 04 RN-010 | Implementado | `NotifyExpiringMemberships` (con salto de renovadas); prueba del comando | Envío real pendiente de verificación Meta |
| RF-MEM-006 | Cancelación sin permanencia mínima; el servicio se conserva hasta finalizar el período pagado. | 02 §2, 04 | Implementado | `AdminController::cancelMembership`; `Business::puedeReservar` (cancelada conserva servicio); `MembershipAdministrationTest` | — |
| Dec-15 | Retención de datos 90 días tras la cancelación, salvo obligación legal; exportación bajo solicitud al superadministrador. | 01 | Pendiente | — | Definir proceso de retención/exportación |

### B3. Mensajería incluida

| ID | Decisión y regla verificable | Origen | Estado | Evidencia | Validación manual |
|---|---|---|---|---|---|
| RF-MEN-01 → RF-MEN-001 | La membresía incluye mensajería; los excedentes requieren autorización expresa. Mostrar consumo incluido, utilizado, disponible y autorizado. | Entrevista, 01 | Condicionado | Trazabilidad parcial: `whatsapp_messages` (entrantes/salientes) | La **cuota incluida no está aprobada**; falta panel de consumo y mecanismo de autorización de excedentes |
| Dec-04 | Cada negocio utiliza su propio número de WhatsApp (identificación por Phone Number ID). | 01, 04 | Implementado | `businesses.whatsapp_phone_number_id`; `WhatsAppMultiempresaTest` | Activación de números por negocio pendiente con Meta |

### B4. Reservas y agenda

| ID | Decisión y regla verificable | Origen | Estado | Evidencia | Validación manual |
|---|---|---|---|---|---|
| RF-RES-001 | El cliente reserva por WhatsApp como canal principal. | 02 §5, 01 | Implementado (integración) | `ReservaWhatsAppService`, `FlujoReservasService`, `WhatsAppMultiempresaTest` | Verificación real con Meta **condicionada** |
| RF-RES-002 | Confirmación automática o manual, configurable por negocio. | 02 §5, 04 RN-021 | Implementado | `businesses.booking_auto_confirm`; `BookingEngineTest` | — |
| RN-018 | Disponibilidad sin solapamientos: duración, jornadas, descansos, festivos, bloqueos y excepciones. | 04 RN-019 | Implementado | `DisponibilidadService`; `BookingEngineTest`, `ScheduleConfigurationTest` | — |
| RF-RES-003 | Las solicitudes pendientes vencen a las 2 horas o antes del inicio de la cita. | 02 §5, 04 RN-022 | Implementado | `AppointmentController` (`min(TTL, inicio)` en hora local), `ExpirePendingAppointments`; `BookingEngineTest` | — |
| RF-RES-005 | Reprogramaciones exclusivamente por usuarios autorizados del negocio. | 02 §5, 04 RN-025 | Implementado | `AppointmentController::reschedule` + `BusinessIsolationTest` | — |
| RF-RES-004 | Cancelaciones según política configurable del negocio. | 02 §5 | Parcial | Cancelación operativa implementada | Falta política configurable (motivos, plazos) |
| RF-RES-007 | Alertas por antecedentes de ausencia, sin bloqueo automático. | 02 §5, 04 RN-027 | Implementado | Umbral 2 ausencias + aviso; `CustomerCommunicationsTest` | — |
| RF-RES-006 | Anticipación mínima y máxima configurable por negocio. | 02 §5, 04 RN-020 | Implementado | `booking_min_notice_minutes` / `booking_max_advance_days`; `BookingEngineTest` | — |

### B5. Clientes y comunicaciones

| ID | Decisión y regla verificable | Origen | Estado | Evidencia | Validación manual |
|---|---|---|---|---|---|
| RF-CLI-001 | Historial de clientes separado por negocio (citas, servicios, cancelaciones, ausencias). | 02 §6 | Implementado | `customers` + `CustomerController`; `CustomerCommunicationsTest` | — |
| RF-CLI-002 | Aviso y autorización de tratamiento de datos verificable. | 02 §6 | Implementado | `data_consent`/`data_consent_at`; `CustomerCommunicationsTest` | — |
| Dec-12 | Consentimiento comercial independiente del tratamiento de datos. | 01 | Pendiente | — | Definir opt-in comercial (campañas) |
| Dec-10 | Recordatorio 24 horas antes con confirmación de asistencia o cancelación; avisos a profesionales por WhatsApp y plataforma. | 01, 02 §7 | Parcial | `appointments:remind`; avisos de ausencia por WhatsApp | Falta confirmación por respuesta del cliente y avisos "en plataforma" (bandeja) |
| RF-COM-* | Registro y trazabilidad de comunicaciones. | 01, 02 §7 | Implementado | `whatsapp_messages` | — |

### B6. Pagos de servicios

| ID | Decisión y regla verificable | Origen | Estado | Evidencia | Validación manual |
|---|---|---|---|---|---|
| RF-PAG-001 | Sin anticipos: pago online opcional por el valor completo. | 02 §8, 01 | Parcial | `service_payments` (monto completo) | Pago online **condicionado** a pasarela no aprobada |
| RF-PAG-002 | Recaudo directo al negocio correspondiente. | 02 §8, 04 RN-037 | Implementado | Pagos asociados al negocio; `ServicePaymentsReportsTest` | — |
| RF-PAG-003 | Verificación de transacciones y comprobantes digitales. | 02 §8 | Implementado | `PaymentController::verify`/`receipt`; pruebas | — |
| RN-042 | Pagos de servicios y membresías registrados separadamente. | 04 | Implementado | Tablas separadas; `ServicePaymentsReportsTest` | — |
| Dec-11 | Facturación electrónica a cargo del negocio. | 01 | Implementado (límite de alcance) | El sistema no factura; solo emite comprobante interno | Confirmar con Viglam su proceso de facturación |

### B7. Reportes e integraciones

| ID | Decisión y regla verificable | Origen | Estado | Evidencia | Validación manual |
|---|---|---|---|---|---|
| RF-REP-* | Indicadores operativos, reportes filtrables y exportaciones Excel/PDF. | 01, 02 §9 | Implementado | `ReportController`; `ServicePaymentsReportsTest` | — |
| Dec-13 | Google Calendar opcional y unidireccional (AgendaGo → Google). | 01 §12, 02 §10 | Implementado | `SyncAppointmentToGoogleCalendar`; documentación | Conexión real pendiente por profesional |
| Dec-13 | PWA y notificaciones push compatibles. | 01 | Parcial | manifest, service worker, iconos | Push real **condicionado** a proveedor/VAPID |

### B8. Seguridad y operación

| ID | Decisión y regla verificable | Origen | Estado | Evidencia | Validación manual |
|---|---|---|---|---|---|
| RN-043 / Dec-14 | 2FA obligatorio para administradores. | 04, 01 | Implementado | Desafío OTP en login; `TwoFactorAuthenticationTest` | — |
| Dec-14 | Auditoría de operaciones relevantes. | 01 | Implementado | `audit_logs`; registros en Admin/Pagos | — |
| Dec-14 | Alojamiento en la nube y respaldos diarios con retención de 30 días y almacenamiento externo. | 01, 03 | Parcial | `backup:database` diario; guía de despliegue | Retención 30 días, copia externa y restauración: **pendientes operativos** |
| Dec-15 | Exportación de datos bajo solicitud al superadministrador. | 01, 04 RN-048 | Pendiente | — | Definir proceso (exportaciones existen por negocio) |
| Dec-16 | Soporte lunes a sábado 8:00–18:00 con prioridad a incidentes críticos. | 01 | Condicionado | — | Compromiso operativo/comercial; **SLA no aprobado** |

### B9. Parámetros de Viglam (piloto)

| ID | Decisión y regla verificable | Origen | Estado | Evidencia | Validación manual |
|---|---|---|---|---|---|
| RV-001/002 | Viglam; Stefania; jornada lunes a sábado 08:00–12:00 y 14:00–18:00. | 04 §RV | Parcial | Todo configurable (jornadas partidas y descansos); `ViglamBusinessSeeder` vincula datos existentes | Preconfigurar jornadas reales en el piloto |
| RV-003/005 | Domingos y lunes festivos colombianos bloqueados por defecto. | 04 §RV | Parcial | Domingos quedan cerrados al configurar lun–sáb; festivos manuales | Falta precarga de festivos colombianos |
| RV-006/007 | Servicios habituales de 120 minutos y combinado de 180 minutos. | 04 §RV | Parcial | Duraciones configurables (5–720 min) | Crear los servicios reales de Viglam en el piloto |
| RV-004 | Excepciones configurables (vacaciones, bloqueos, extras). | 04 §RV | Implementado | `ScheduleException`; `ScheduleConfigurationTest` | — |

## C. Decisiones NO aprobadas (no implementar sin confirmación)

- Precio mensual de la membresía y moneda/impuestos aplicables.
- Cuota de mensajería incluida y tarifa de excedentes.
- Alertas de consumo al 50 %, 80 % y 100 %.
- Proveedor Meta definitivo, modelo de facturación de WhatsApp y `messaging_account_id`.
- Pasarela de pagos y proveedor de cobro automático.
- SLA contractual de soporte.
- Proveedor de push (VAPID) y almacenamiento externo de respaldos.

## D. Contradicciones, omisiones y requisitos sin pruebas

- **D1 (resuelta):** RF-RES-003 aplicado: el vencimiento es `min(TTL, inicio)` con TTL máximo de 2 horas (migración de límite y validación `max:120`). Corregido también el defecto de zona horaria del motor de disponibilidad (franjas y vencimientos en hora local `America/Bogota`).
- **D2 (nomenclatura):** la entrevista usa RF-MEM-01..03 y RF-MEN-01; el documento aprobado usa RF-MEM-001..006. Se conservan ambos con la tabla de alias (sección A); no se duplican requisitos.
- **D3 (resuelta):** cada profesional gestiona su disponibilidad y bloqueos desde Configuración ("Mi disponibilidad"), con los mismos permisos y aislamiento del negocio.
- **D4 (resuelta):** flujo explícito de cancelación de membresía sin permanencia; el servicio se conserva hasta el fin del período pagado.
- **D5 (sin pruebas aún / pendientes):** consentimiento comercial, retención de 90 días, panel de consumo de mensajería, precarga de festivos y confirmación de asistencia por respuesta del cliente.

## E. Referencias cruzadas

- Plan y avance: `06-plan-sprints.md`, `../../sprints.md` (estado de implementación por sprint).
- Operación y despliegue: `../../despliegue.md` (respaldos, 2FA, integraciones externas).
- Este documento es la referencia maestra de requisitos; cualquier cambio de alcance debe registrarse aquí y en `01-decisiones-aprobadas.md`.
