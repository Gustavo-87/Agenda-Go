# AgendaNails — Arquitectura técnica

Estado: arquitectura propuesta, pendiente de validación.
Producto: SaaS multisectorial de gestión de reservas.
Primer cliente: Viglam.

## 1. Principios arquitectónicos

- Aplicación SaaS multiempresa.
- Monolito modular desarrollado en Laravel.
- Una sola aplicación para todos los negocios.
- Aislamiento estricto de datos entre negocios.
- Separación entre lógica comercial e integraciones externas.
- Arquitectura preparada para crecimiento progresivo.
- Reutilización del motor de reservas existente.
- Seguridad y trazabilidad desde el diseño.

## 2. Tecnologías

### Aplicación
- Laravel 13.
- PHP 8.5.
- Blade y Tailwind CSS.
- Autenticación y autorización contextual.
- PWA para dispositivos compatibles.

### Persistencia
- SQLite para desarrollo local.
- Base de datos relacional de producción por seleccionar.
- Migraciones, índices y restricciones de integridad.
- Transacciones para operaciones críticas.

### Procesamiento
- Laravel Queues para trabajos asíncronos.
- Laravel Scheduler para tareas programadas.
- Procesos independientes para notificaciones y sincronizaciones.

### Integraciones
- WhatsApp Cloud API.
- WhatsApp Flows, sujeto a habilitación por Meta.
- Google Calendar OAuth opcional.
- Pasarela de pagos por seleccionar.
- PSE y llaves según capacidades del proveedor.

## 3. Modelo multiempresa

Se utilizará inicialmente una base de datos compartida
con separación lógica por negocio.

Entidad principal: negocios.

Cada negocio tendrá:
- Administradores.
- Profesionales.
- Clientes.
- Servicios.
- Agendas.
- Reservas.
- Configuración.
- Membresía.
- Integración de WhatsApp.
- Configuración de pagos.
- Registros de mensajería.

Las entidades dependientes deberán tener una relación
verificable con el negocio correspondiente.

No será suficiente filtrar datos en las vistas.

Las políticas, consultas, servicios y operaciones deberán
validar el contexto del negocio en el servidor.

Los identificadores proporcionados por los usuarios no
podrán utilizarse para acceder a recursos de otros negocios.

## 4. Roles y permisos

### Superadministrador
- Administración global de la plataforma.
- Registro y activación de negocios.
- Gestión de membresías.
- Configuración inicial de integraciones.
- Gestión de pagos y soporte.

### Administrador del negocio
- Administración de su negocio.
- Catálogo de servicios y precios.
- Gestión de profesionales.
- Configuración de horarios y políticas.
- Supervisión de reservas y reportes.

### Profesional
- Acceso exclusivamente a su agenda autorizada.
- Gestión de citas.
- Bloqueos, vacaciones y disponibilidad excepcional.
- Consulta de alertas y notificaciones.

Las asignaciones y permisos se comprobarán en cada operación.

## 5. Módulos Laravel

### Administración SaaS
- Negocios.
- Membresías.
- Planes.
- Renovaciones.
- Suspensiones.
- Facturación de membresías.

### Gestión del negocio
- Configuración general.
- Usuarios y profesionales.
- Servicios y precios.
- Horarios y políticas.

### Reservas
- Motor de disponibilidad.
- Creación y confirmación.
- Aprobación manual.
- Cancelación y reprogramación.
- Historial y control de ausencias.

### Comunicaciones
- WhatsApp por negocio.
- Flujos conversacionales.
- WhatsApp Flows.
- Recordatorios.
- Notificaciones a profesionales.
- Consumo de mensajería.

### Pagos de servicios
- Integración de medios de pago.
- Confirmación de transacciones.
- Comprobantes.
- Recaudo directo por negocio.

### Reportes
- Indicadores operativos.
- Filtros.
- Exportación a Excel y PDF.

### Seguridad
- Roles y permisos.
- Autenticación y 2FA.
- Auditoría.
- Protección de datos.

## 6. Diseño del motor de reservas

La disponibilidad deberá calcularse considerando:

- Negocio.
- Profesional.
- Servicio.
- Duración del servicio.
- Jornadas y descansos.
- Festivos aplicables.
- Excepciones de horario.
- Bloqueos y vacaciones.
- Reservas confirmadas.
- Solicitudes pendientes vigentes.
- Anticipación mínima y máxima.

La confirmación deberá volver a verificar disponibilidad.

Las operaciones simultáneas deberán protegerse mediante
transacciones y mecanismos efectivos de concurrencia.

La validación previa por sí sola no garantiza evitar
dobles reservas en solicitudes concurrentes.

## 7. Estados de reservas

Estados propuestos, sujetos a especificación:

- Pendiente.
- Confirmada.
- Cancelada.
- Rechazada.
- Vencida.
- Completada.
- Ausente.

La confirmación de asistencia será un atributo o evento
diferenciado del estado de la reserva.

Las solicitudes pendientes vencerán después de dos horas,
o antes si llega el inicio de la cita.

## 8. WhatsApp multiempresa

Cada negocio tendrá su propio número de WhatsApp.

La plataforma identificará el negocio de origen utilizando
el identificador de número recibido desde Meta.

La configuración incluirá:
- Identificador del negocio.
- WABA y Phone Number ID.
- Credenciales protegidas.
- Estado de conexión.
- Configuración de mensajes.
- Plantillas autorizadas.

Los webhooks deberán:
- Verificar autenticidad.
- Identificar correctamente el negocio.
- Evitar procesamientos duplicados.
- Registrar errores de forma segura.
- Responder rápidamente.
- Delegar operaciones largas a trabajos en cola.

La publicación de WhatsApp Flows depende de los requisitos
comerciales y técnicos de Meta.

## 9. Mensajería y costos

- Registrar eventos de mensajería por negocio.
- Diferenciar mensajes enviados y mensajes facturables.
- Consultar tarifas y condiciones del proveedor.
- Controlar consumo incluido en la membresía.
- Solicitar autorización antes de cobrar excedentes.
- Mantener trazabilidad del consumo autorizado.

El mecanismo de facturación centralizada de WhatsApp
continúa pendiente de validación comercial.

## 10. Membresías

Una membresía activa por negocio dentro del modelo inicial.

Estados propuestos:
- Pendiente de activación.
- Activa.
- En gracia.
- Suspendida.
- Cancelada.

Reglas:
- Un único plan mensual.
- Hasta cinco profesionales activos.
- Sin cobro inicial de implementación.
- Primera vigencia después del pago y habilitación.
- Cinco días de gracia.
- Bloqueo de nuevas reservas tras la gracia.
- Conservación de información histórica.

Las tareas programadas revisarán los vencimientos.
La ejecución deberá ser repetible sin duplicar operaciones.

## 11. Pagos

Separar completamente:

A. Pagos de membresías a AgendaNails.
B. Pagos de servicios a los negocios.

Los pagos de servicios deberán liquidarse directamente
al negocio correspondiente mediante un proveedor compatible.

Nunca se confirmará una transacción por el retorno del
navegador sin validación verificable del proveedor.

Los webhooks de pago deberán ser autenticados e idempotentes.

No se almacenarán datos sensibles de tarjetas.

## 12. Google Calendar

- Integración opcional por profesional.
- Sincronización unidireccional.
- AgendaNails como fuente principal de disponibilidad.
- Operaciones mediante trabajos en cola.
- Reintentos controlados.
- Prevención de eventos duplicados.
- Fallas externas sin invalidar la reserva local.

## 13. PWA

- Interfaz adaptable a dispositivos.
- Instalación en navegadores compatibles.
- Manifiesto y recursos de aplicación.
- Notificaciones push autorizadas.
- Compatibilidad con iOS y Android según sus restricciones.
- Operaciones críticas disponibles con conexión a Internet.

## 14. Seguridad

- HTTPS obligatorio en producción.
- 2FA para administradores.
- Autorización contextual por negocio.
- Cifrado de credenciales.
- Protección de secretos fuera del repositorio.
- Validación de webhooks.
- Registro de auditoría.
- Protección contra acceso entre negocios.
- Autorizaciones de tratamiento de datos.

## 15. Infraestructura de producción

Arquitectura inicial propuesta:

Usuario
  |
HTTPS
  |
Aplicación Laravel
  |
  +-- Base de datos
  +-- Cola de trabajos
  +-- Tareas programadas
  +-- Almacenamiento privado
  |
  +-- Meta WhatsApp Cloud API
  +-- Google Calendar
  +-- Proveedor de pagos

El servidor deberá alojarse en la nube.

No se utilizará Cloudflare Quick Tunnel como infraestructura
comercial permanente.

Los respaldos serán diarios, conservados durante 30 días
y almacenados fuera del servidor principal.

## 16. Migración desde el MVP

El MVP actual no deberá considerarse automáticamente
seguro para operación multiempresa.

Antes del lanzamiento será necesario:

1. Introducir la entidad negocio.
2. Asociar correctamente los datos existentes.
3. Implementar aislamiento por negocio.
4. Extraer la lógica de reservas de los controladores
   hacia servicios reutilizables.
5. Corregir la concurrencia y prevenir dobles reservas.
6. Configurar horarios y descansos dinámicos.
7. Aislar credenciales de WhatsApp por negocio.
8. Añadir controles de membresía.
9. Implementar auditoría y pruebas multiempresa.
10. Validar despliegue, colas y recuperación.

Los datos actuales de Viglam se migrarán sin perder
las reservas existentes.

## 17. Decisiones pendientes

- Motor de base de datos de producción.
- Proveedor de hosting.
- Dominio definitivo.
- Pasarela de pagos.
- Operador de facturación de WhatsApp.
- Estrategia de despliegue.
- Niveles medibles de rendimiento y disponibilidad.
- Estrategia definitiva de concurrencia.

## 18. Referencias

- 01-decisiones-aprobadas.md
- 02-requisitos-funcionales.md
- 03-requisitos-no-funcionales.md
- 04-reglas-negocio.md

Este documento define la arquitectura objetivo.
No constituye evidencia de implementación.
