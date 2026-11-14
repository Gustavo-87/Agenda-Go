# AgendaNails — Decisiones aprobadas

Estado: perfilamiento funcional y comercial.
Primer negocio: Viglam.
Producto: AgendaNails, plataforma SaaS multiempresa.

## 1. Modelo comercial
- Membresía mensual única con todas las funcionalidades.
- Hasta 5 profesionales por negocio.
- Configuración inicial incluida, sin cobro adicional.
- Sin permanencia mínima.
- Pagos de membresía manuales y mediante pasarela.
- Renovación automática autorizada o pago manual.
- Primera vigencia inicia con pago confirmado y servicio habilitado.
- Período de gracia de 5 días al vencimiento.
- Después del plazo, suspensión automática de nuevas reservas.
- Aviso de vencimiento por WhatsApp 5 días antes.
- Precio definido mediante análisis de costos y mercado.
- Mensajería incluida hasta un volumen por definir.
- Excedentes sujetos a autorización previa del negocio.
- Vigencia y pagos administrados desde Laravel.

## 2. Modelo multiempresa
- Cada negocio tendrá su propio número de WhatsApp.
- El superadministrador registrará y activará los negocios.
- AgendaNails configurará inicialmente WhatsApp y medios de pago.
- Datos y operaciones aislados por negocio.
- Acceso desde un dominio único con autenticación y permisos.

## 3. Usuarios y permisos
- Superadministrador: gestión global del SaaS.
- Administrador: gestión integral de su negocio.
- Profesional: gestión exclusivamente de su agenda.
- Autenticación de dos factores obligatoria para administradores.
- Profesionales con acceso mediante correo y contraseña.

## 4. Agenda y reservas
- Agenda interna como fuente principal de disponibilidad.
- Horarios y descansos configurables por profesional.
- Domingos y festivos configurables, con excepciones.
- Servicios con precios y duraciones configurables.
- Catálogo administrado por el administrador del negocio.
- Servicios asignados a profesionales.
- Anticipación mínima y máxima configurable por negocio.
- Confirmación automática o aprobación manual configurable.
- Solicitudes pendientes vencen después de 2 horas.
- Prevención de reservas solapadas.
- Gestión de citas, vacaciones, bloqueos y horarios excepcionales.
- Reprogramación únicamente desde Laravel por usuarios autorizados.
- Políticas de cancelación configurables por negocio.
- Registro de ausencias y alertas sobre antecedentes.
- Historial de clientas, servicios, cancelaciones y ausencias.

## 5. WhatsApp y notificaciones
- Reservas mediante WhatsApp del negocio.
- Recordatorio automático 24 horas antes de la cita.
- Botones para confirmar asistencia o cancelar.
- Avisos de nuevas reservas en Laravel y WhatsApp personal de la profesional.
- Notificaciones push en dispositivos compatibles.
- Campañas promocionales en una segunda fase, con autorización.

## 6. Pagos de servicios
- No se exigirán anticipos para reservar.
- Pago completo opcional mediante PSE o llaves.
- Recaudo directamente a la cuenta del negocio.
- AgendaNails configurará inicialmente los medios de pago.
- Comprobante digital y confirmación automática por WhatsApp.
- No se implementará facturación electrónica inicialmente.
- La facturación tributaria será responsabilidad del negocio.

## 7. Reportes
- Citas, cancelaciones y ausencias.
- Ocupación por profesional.
- Servicios más solicitados.
- Clientas recurrentes.
- Filtros, visualizaciones y exportación a Excel y PDF.

## 8. Infraestructura y seguridad
- Laravel alojado en infraestructura en la nube.
- Aplicación web progresiva (PWA) adaptable a dispositivos.
- Auditoría de accesos, reservas, precios, horarios, permisos y pagos.
- Copias de seguridad automáticas cada 24 horas.
- Conservación de respaldos durante 30 días.
- Datos de negocios cancelados conservados durante 90 días,
  sujetos a obligaciones legales de conservación.
- Exportación de datos por solicitud al superadministrador.
- Aviso de privacidad y autorización durante la primera reserva.
- Registro verificable de la autorización otorgada.

## 9. Google Calendar
- Integración opcional por profesional.
- Sincronización desde AgendaNails hacia Google Calendar.
- Cambios realizados directamente en Google Calendar no modifican AgendaNails.

## 10. Soporte
- Lunes a sábado, de 8:00 a. m. a 6:00 p. m., hora de Colombia.
- Atención prioritaria de incidentes críticos fuera del horario.
- Reservas disponibles las 24 horas.

## 11. Configuración inicial de Viglam
- Profesional: Stefania.
- Lunes a sábado: 8:00 a 12:00 y 14:00 a 18:00.
- Domingos: atención excepcional.
- Lunes festivos colombianos: bloqueados por defecto.
- Servicios estándar: 2 horas.
- Servicio de manos y pies: 3 horas.
- Excepciones configurables desde la agenda.

## 12. Pendientes de definición y validación
- Precio definitivo de la membresía.
- Cantidad y unidad de mensajería incluida.
- Tarifa y mecanismo de cobro de excedentes.
- Proveedor y facturación centralizada de WhatsApp.
- Pasarela para membresías y pagos a cada negocio.
- Confirmación automática de transferencias por llaves.
- Dominio y proveedor de infraestructura.
- Política detallada de cancelaciones y plazos por defecto.
- Tiempos de respuesta a incidentes críticos.
- Activación comercial de WhatsApp Flows y requisitos de Meta.

## 13. Estado de implementación
Este documento registra decisiones aprobadas de producto.
No implica que todas estén implementadas.

El MVP de demostración cuenta con reservas por WhatsApp,
agenda, disponibilidad y prototipo de WhatsApp Flows.
La publicación comercial de Flows y varias funciones SaaS
continúan pendientes.

La siguiente etapa será elaborar requisitos trazables,
criterios de aceptación, arquitectura y plan de sprints.
