# AgendaGo — Reglas de negocio

Estado: decisiones aprobadas durante el perfilamiento.
Alcance: SaaS multisectorial.
Primer cliente: Viglam.

## 1. Membresías

- RN-001: Cada negocio tendrá una membresía mensual.
- RN-002: Cada membresía incluirá hasta 5 profesionales activos.
- RN-003: La configuración inicial no tendrá costo adicional.
- RN-004: La primera vigencia comenzará con el pago confirmado y el servicio habilitado.
- RN-005: El vencimiento permitirá 5 días de gracia.
- RN-006: Finalizado el período de gracia, se bloquearán nuevas reservas.
- RN-007: La suspensión no eliminará reservas ni información histórica.
- RN-008: Las membresías podrán renovarse mediante pago manual o cobro automático autorizado.
- RN-009: No existirá permanencia mínima.
- RN-010: Los negocios recibirán aviso de vencimiento por WhatsApp 5 días antes, salvo renovación confirmada.

## 2. Negocios y profesionales

- RN-011: Cada negocio tendrá datos independientes.
- RN-012: Cada negocio utilizará su propio número de WhatsApp.
- RN-013: El superadministrador registrará y activará los negocios.
- RN-014: El administrador gestionará servicios, precios y profesionales.
- RN-015: Cada profesional administrará exclusivamente su agenda.
- RN-016: Los horarios, descansos, vacaciones y excepciones serán configurables.
- RN-017: Los servicios tendrán una duración y precio definidos por el negocio.

## 3. Reservas

- RN-018: No se permitirán reservas en horarios ocupados.
- RN-019: La disponibilidad considerará duración del servicio, descansos, festivos y bloqueos.
- RN-020: Cada negocio definirá la anticipación mínima y máxima para reservar.
- RN-021: Cada negocio elegirá confirmación automática o manual.
- RN-022: Las solicitudes pendientes bloquearán temporalmente el horario.
- RN-023: Las solicitudes no aprobadas vencerán después de 2 horas o antes del inicio de la cita.
- RN-024: Las cancelaciones respetarán la política definida por el negocio.
- RN-025: Solo profesionales autorizados y administradores podrán reprogramar citas.
- RN-026: La reprogramación actualizará la disponibilidad y los recordatorios.
- RN-027: Las ausencias se registrarán sin bloqueos automáticos de clientes.
- RN-028: Las reservas no exigirán anticipos económicos.

## 4. Comunicaciones

- RN-029: Se enviará un recordatorio 24 horas antes de cada cita elegible.
- RN-030: El recordatorio permitirá confirmar asistencia o solicitar cancelación.
- RN-031: Las profesionales recibirán avisos por WhatsApp y en Laravel.
- RN-032: Los avisos se enviarán al WhatsApp personal registrado por cada profesional.
- RN-033: Los mensajes sujetos a cobro respetarán las tarifas y condiciones de Meta.
- RN-034: Los excedentes de mensajería requerirán autorización previa del negocio.
- RN-035: Las campañas promocionales quedarán para una segunda fase y requerirán consentimiento.

## 5. Pagos

- RN-036: Los pagos por servicios serán opcionales y por el valor completo.
- RN-037: Los pagos de servicios llegarán directamente al negocio correspondiente.
- RN-038: PSE y llaves estarán sujetos a disponibilidad e integración del proveedor.
- RN-039: Un pago solo se considerará confirmado mediante evidencia verificable.
- RN-040: AgendaGo generará comprobantes digitales y avisos de pago.
- RN-041: Cada negocio será responsable de sus obligaciones de facturación electrónica.
- RN-042: Los pagos de servicios y las membresías se registrarán separadamente.

## 6. Seguridad y datos

- RN-043: Los administradores utilizarán autenticación de dos factores.
- RN-044: Los usuarios solo podrán acceder a información autorizada de su negocio.
- RN-045: Las acciones relevantes generarán registros de auditoría.
- RN-046: Se registrará la autorización de tratamiento de datos de los clientes.
- RN-047: Los datos de negocios cancelados se conservarán durante 90 días, salvo obligaciones legales aplicables.
- RN-048: La exportación de datos se solicitará al superadministrador.

## 7. Reglas particulares de Viglam

- RV-001: Profesional inicial: Stefania.
- RV-002: Jornada habitual de lunes a sábado.
- RV-003: Atención de 08:00 a 12:00 y de 14:00 a 18:00.
- RV-004: Domingos bloqueados por defecto.
- RV-005: Lunes festivos colombianos bloqueados por defecto.
- RV-006: Servicios habituales de 120 minutos.
- RV-007: Servicio combinado de manos y pies de 180 minutos.
- RV-008: Se permitirá habilitar disponibilidad excepcional.

## 8. Pendientes de especificación

- Política predeterminada de cancelación.
- Tratamiento de solicitudes pendientes durante una suspensión.
- Detalles de autorización y cobro de mensajería excedente.
- Condiciones de reembolsos y pagos fallidos.
- Transiciones completas de estados de reservas.
- Retención legal de información financiera y personal.

Estas reglas deberán convertirse en criterios verificables
y pruebas automatizadas durante los sprints.
