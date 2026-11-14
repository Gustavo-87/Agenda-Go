# AgendaNails — Requisitos funcionales

Estado: perfilamiento aprobado, pendiente de implementación y validación.
Alcance: plataforma SaaS multisectorial para negocios que trabajan mediante citas.
Primer cliente: Viglam.

## Convenciones

Prioridad:
- P0: indispensable para operación comercial.
- P1: necesaria para completar el producto.
- P2: evolución posterior.

Estado:
- Implementado: funcionalidad existente, pendiente de auditoría SaaS.
- Parcial: existe implementación inicial.
- Pendiente: requiere desarrollo.
- Condicionado: depende de integración o autorización externa.

## 1. Administración SaaS y negocios

### RF-NEG-001 — Registro de negocios
Prioridad: P0 | Estado: Pendiente

El superadministrador podrá registrar negocios de distintos sectores.

Criterios de aceptación:
- Registrar datos comerciales y contacto.
- Asignar administrador responsable.
- Configurar el negocio de forma independiente.
- Impedir acceso a información de otros negocios.

### RF-NEG-002 — Activación de negocios
Prioridad: P0 | Estado: Pendiente

La activación será manual y estará condicionada al pago confirmado y a la habilitación del servicio.

### RF-NEG-003 — Configuración del negocio
Prioridad: P0 | Estado: Pendiente

Cada negocio tendrá configuración propia de horarios, profesionales, servicios, políticas de reserva y comunicaciones.

### RF-NEG-004 — Límite de profesionales
Prioridad: P0 | Estado: Pendiente

Cada negocio podrá registrar hasta cinco profesionales activos dentro de su membresía.

## 2. Membresías

### RF-MEM-001 — Plan mensual único
Prioridad: P0 | Estado: Pendiente

Existirá inicialmente una membresía mensual con todas las funcionalidades contratadas.

### RF-MEM-002 — Pagos de membresía
Prioridad: P0 | Estado: Pendiente

Permitir pagos manuales verificados y pagos electrónicos confirmados mediante pasarela.

### RF-MEM-003 — Renovaciones
Prioridad: P0 | Estado: Pendiente

Permitir renovación manual o cobro recurrente previamente autorizado.

### RF-MEM-004 — Gracia y suspensión
Prioridad: P0 | Estado: Pendiente

Conceder cinco días de gracia al vencer la membresía y suspender automáticamente las nuevas reservas al finalizar el plazo.

### RF-MEM-005 — Notificación de vencimiento
Prioridad: P1 | Estado: Pendiente

Enviar aviso por WhatsApp cinco días antes del vencimiento, excepto cuando la renovación ya esté confirmada.

### RF-MEM-006 — Cancelación de membresía
Prioridad: P0 | Estado: Pendiente

Permitir cancelación sin permanencia mínima, conservando el servicio hasta finalizar el período pagado.

## 3. Usuarios y profesionales

### RF-USU-001 — Roles y permisos
Prioridad: P0 | Estado: Pendiente

Implementar superadministrador, administrador del negocio y profesional, con permisos diferenciados y aislamiento multiempresa.

### RF-PRO-001 — Gestión de profesionales
Prioridad: P0 | Estado: Parcial

Permitir registrar, editar, activar y desactivar profesionales asociados a un negocio.

### RF-PRO-002 — Agenda individual
Prioridad: P0 | Estado: Parcial

Cada profesional podrá administrar sus citas, bloqueos, vacaciones, ausencias y disponibilidad excepcional.

## 4. Servicios y disponibilidad

### RF-SER-001 — Catálogo de servicios
Prioridad: P0 | Estado: Parcial

El administrador gestionará servicios, duraciones, precios y asignación a profesionales.

### RF-AGE-001 — Horarios configurables
Prioridad: P0 | Estado: Pendiente

Configurar jornadas, descansos, días laborales, festivos y excepciones para cada profesional.

### RF-AGE-002 — Disponibilidad
Prioridad: P0 | Estado: Parcial

Mostrar exclusivamente horarios compatibles con la duración del servicio, la jornada configurada y las reservas existentes.

### RF-AGE-003 — Bloqueo de solapamientos
Prioridad: P0 | Estado: Parcial

Impedir reservas simultáneas que se superpongan para una misma profesional.

## 5. Reservas

### RF-RES-001 — Reserva por WhatsApp
Prioridad: P0 | Estado: Parcial

Permitir seleccionar profesional, servicio, fecha y hora, registrar los datos del cliente y solicitar la reserva.

### RF-RES-002 — Confirmación configurable
Prioridad: P0 | Estado: Pendiente

Cada negocio elegirá confirmación automática o aprobación manual.

### RF-RES-003 — Caducidad de solicitudes
Prioridad: P0 | Estado: Pendiente

Las solicitudes pendientes de aprobación vencerán a las dos horas, o antes si comienza el horario solicitado.

### RF-RES-004 — Cancelaciones
Prioridad: P0 | Estado: Pendiente

Aplicar la política de cancelación configurable por cada negocio.

### RF-RES-005 — Reprogramaciones
Prioridad: P1 | Estado: Pendiente

Solo el administrador o la profesional autorizada podrán reprogramar desde Laravel.

### RF-RES-006 — Anticipación de reservas
Prioridad: P0 | Estado: Pendiente

Cada negocio configurará anticipación mínima y máxima para reservar.

### RF-RES-007 — Ausencias
Prioridad: P1 | Estado: Pendiente

Registrar ausencias y mostrar alertas sobre antecedentes sin bloquear automáticamente al cliente.

## 6. Clientes

### RF-CLI-001 — Historial de clientes
Prioridad: P1 | Estado: Pendiente

Registrar nombre, teléfono, citas, servicios realizados, cancelaciones y ausencias, separados por negocio.

### RF-CLI-002 — Aviso de privacidad
Prioridad: P0 | Estado: Pendiente

Presentar el aviso durante la primera reserva y conservar evidencia de la autorización correspondiente.

## 7. Comunicaciones

### RF-WA-001 — Número de WhatsApp por negocio
Prioridad: P0 | Estado: Condicionado

Cada negocio utilizará un número propio, configurado inicialmente por AgendaNails.

### RF-NOT-001 — Recordatorio de cita
Prioridad: P1 | Estado: Pendiente

Enviar recordatorio automático 24 horas antes con opciones para confirmar asistencia o cancelar.

### RF-NOT-002 — Avisos a profesionales
Prioridad: P1 | Estado: Pendiente

Notificar nuevas reservas en Laravel y al WhatsApp personal de la profesional.

### RF-NOT-003 — Notificaciones push
Prioridad: P1 | Estado: Pendiente

Notificar nuevas citas y modificaciones en dispositivos compatibles con la PWA.

### RF-WA-002 — Control de consumo
Prioridad: P0 | Estado: Condicionado

Registrar el consumo facturable, controlar el volumen incluido y exigir autorización antes de cobrar excedentes.

### RF-MKT-001 — Campañas promocionales
Prioridad: P2 | Estado: Pendiente

Permitir campañas futuras únicamente a clientes con autorización comercial.

## 8. Pagos de servicios

### RF-PAG-001 — Pago completo opcional
Prioridad: P1 | Estado: Condicionado

Permitir pago completo mediante PSE o llaves sin exigir anticipos para reservar.

### RF-PAG-002 — Recaudo directo
Prioridad: P1 | Estado: Condicionado

Los fondos llegarán directamente a la cuenta del negocio correspondiente.

### RF-PAG-003 — Confirmación y comprobantes
Prioridad: P1 | Estado: Pendiente

Registrar pagos confirmados, generar comprobantes digitales y enviar aviso por WhatsApp.

La facturación electrónica no forma parte del alcance inicial.

## 9. Reportes

### RF-INF-001 — Indicadores operativos
Prioridad: P1 | Estado: Pendiente

Mostrar citas, cancelaciones, ausencias, ocupación por profesional, servicios más solicitados y clientes recurrentes.

### RF-INF-002 — Exportación
Prioridad: P1 | Estado: Pendiente

Permitir exportar reportes autorizados a Excel y PDF.

## 10. Integraciones

### RF-CAL-001 — Google Calendar opcional
Prioridad: P1 | Estado: Parcial

Permitir sincronización unidireccional desde AgendaNails hacia Google Calendar, sin depender de Google para confirmar reservas.

## 11. Alcance específico de Viglam

- Primera profesional: Stefania.
- Horario habitual: lunes a sábado.
- Jornada: 08:00–12:00 y 14:00–18:00.
- Domingos y lunes festivos colombianos bloqueados por defecto.
- Servicios habituales de 120 minutos.
- Servicio combinado de manos y pies de 180 minutos.
- Disponibilidad excepcional configurable.

## 12. Pendientes de definición

- Precio comercial definitivo.
- Volumen de mensajería incluido y reglas de excedentes.
- Proveedor y esquema de facturación de WhatsApp.
- Pasarelas de pago compatibles con el modelo de recaudo directo.
- Reglas completas de estados y transiciones de reservas.
- Criterios de aceptación detallados y casos de prueba por historia.
- Dependencias entre módulos y planificación de sprints.

## 13. Trazabilidad

Documento base: 01-decisiones-aprobadas.md

Los estados de implementación son preliminares.
Cada funcionalidad deberá auditarse contra el código y sus pruebas
antes de declararse terminada para producción.
