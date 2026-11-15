# AgendaGo — Plan de sprints

Estado: planificación inicial.
Producto: SaaS multisectorial de reservas.
Primer cliente: Viglam.

## Objetivo

Convertir el MVP existente en una plataforma SaaS comercial,
segura, multiempresa y preparada para incorporar nuevos negocios.

Cada sprint deberá terminar con funcionalidades verificadas,
pruebas aprobadas y documentación actualizada.

## Sprint 0 — Auditoría y línea base

Objetivo: conocer el estado real del código antes de modificarlo.

Entregables:
- Inventario de módulos, rutas, modelos y migraciones.
- Identificación de funcionalidades existentes y pendientes.
- Diagnóstico de seguridad y dependencias.
- Revisión de pruebas automatizadas.
- Matriz de trazabilidad entre requisitos y código.
- Registro de deuda técnica y riesgos.
- Confirmación de una línea base funcional.

Criterio de cierre:
- Diagnóstico documentado.
- Pruebas existentes ejecutadas.
- Riesgos críticos identificados.
- Sin cambios funcionales innecesarios.

## Sprint 1 — Arquitectura multiempresa

Objetivo: aislar los datos y operaciones de cada negocio.

Entregables:
- Entidad Negocio.
- Asociación de datos existentes con Viglam.
- Contexto de negocio autenticado.
- Roles y permisos por negocio.
- Políticas y validaciones de acceso.
- Pruebas de aislamiento entre negocios.
- Migraciones seguras.

Criterio de cierre:
- Ningún usuario podrá acceder a recursos de otro negocio.
- Las reservas existentes de Viglam se conservarán.

## Sprint 2 — Catálogo, profesionales y horarios

Objetivo: eliminar restricciones específicas del MVP.

Entregables:
- Profesionales por negocio.
- Límite de cinco profesionales activos.
- Catálogo de servicios por negocio.
- Duraciones y precios configurables.
- Servicios asignados a profesionales.
- Jornadas divididas y descansos.
- Vacaciones, bloqueos y excepciones.
- Festivos configurables.

Criterio de cierre:
- Viglam podrá configurar sus horarios reales.
- Otro negocio podrá configurar servicios distintos.

## Sprint 3 — Motor de reservas robusto

Objetivo: garantizar disponibilidad correcta.

Entregables:
- Cálculo centralizado de disponibilidad.
- Prevención de conflictos y solapamientos.
- Protección ante solicitudes simultáneas.
- Anticipación mínima y máxima.
- Confirmación automática o manual.
- Vencimiento de solicitudes pendientes.
- Cancelación y reprogramación.
- Estados y transiciones validados.

Criterio de cierre:
- No se permitirán reservas incompatibles.
- Las pruebas concurrentes deberán pasar.

## Sprint 4 — WhatsApp multiempresa

Objetivo: atender reservas desde números independientes.

Entregables:
- Configuración de WhatsApp por negocio.
- Identificación del negocio mediante Phone Number ID.
- Webhooks autenticados e idempotentes.
- Flujo de reserva desacoplado del canal.
- Notificaciones de confirmación y cancelación.
- Tratamiento de errores y reintentos.
- Registro de mensajes y consumo.
- Pruebas con múltiples negocios.

Dependencia externa:
- Habilitación comercial y técnica de Meta.
- Validación del mecanismo de facturación.

Criterio de cierre:
- Los mensajes se atribuirán al negocio correcto.
- No habrá filtración de información entre negocios.

## Sprint 5 — Membresías y administración SaaS

Objetivo: comercializar y administrar la plataforma.

Entregables:
- Panel del superadministrador.
- Alta y activación manual de negocios.
- Membresía mensual única.
- Registro y verificación de pagos.
- Renovación manual.
- Renovación automática, si el proveedor lo permite.
- Notificación previa al vencimiento.
- Cinco días de gracia.
- Suspensión de nuevas reservas.
- Conservación de información histórica.

Criterio de cierre:
- Se podrá activar, renovar y suspender un negocio.
- Las reglas de membresía estarán automatizadas.

## Sprint 6 — Clientes y comunicaciones

Objetivo: completar el seguimiento de citas.

Entregables:
- Ficha e historial de clientes.
- Cancelaciones y ausencias.
- Alertas por antecedentes de ausencia.
- Aviso y autorización de tratamiento de datos.
- Recordatorios de 24 horas.
- Confirmación de asistencia.
- Avisos a profesionales.
- PWA y notificaciones push compatibles.

Criterio de cierre:
- Se podrá gestionar el ciclo completo de una cita.
- Las comunicaciones tendrán trazabilidad.

## Sprint 7 — Pagos de servicios y reportes

Objetivo: completar las herramientas operativas.

Entregables:
- Integración de pago completo opcional.
- Recaudo directo para cada negocio.
- Verificación de transacciones.
- Comprobantes digitales.
- Indicadores operativos.
- Reportes filtrables.
- Exportación Excel y PDF.

Dependencia externa:
- Proveedor compatible con pagos a cada negocio.

Criterio de cierre:
- Los pagos no se confundirán con las membresías.
- Los reportes respetarán permisos y negocio.

## Sprint 8 — Seguridad y preparación productiva

Objetivo: preparar la operación comercial.

Entregables:
- 2FA para administradores.
- Auditoría de operaciones relevantes.
- Revisión de permisos y credenciales.
- Configuración segura de producción.
- Copias de seguridad externas.
- Pruebas de restauración.
- Colas y tareas programadas.
- Monitoreo y manejo de errores.
- Pruebas integrales de aceptación.
- Documentación de despliegue y soporte.

Criterio de cierre:
- Sin defectos críticos conocidos.
- Copias restaurables.
- Integraciones verificadas.
- Aprobación de pruebas con Viglam.

## Sprint 9 — Piloto comercial Viglam

Objetivo: validar el sistema en operación real.

Entregables:
- Configuración productiva de Viglam.
- Número de WhatsApp comercial habilitado.
- Servicios, profesionales y horarios reales.
- Capacitación inicial.
- Validación de reservas reales.
- Seguimiento de incidentes.
- Corrección de defectos detectados.
- Acta de aceptación del piloto.

Criterio de cierre:
- Viglam podrá gestionar sus reservas de manera autónoma.
- Se habrán validado las operaciones críticas.
- El servicio contará con soporte y recuperación definidos.

## Fase 2 — Evolución

- Campañas promocionales con consentimiento.
- Mejoras de analítica.
- Ampliación de capacidad comercial.
- Nuevas integraciones solicitadas por los negocios.
- Ajustes sectoriales que no comprometan el núcleo común.

## Reglas de ejecución

- No iniciar un sprint sin revisar sus dependencias.
- No declarar una funcionalidad terminada sin pruebas.
- Mantener documentación y código sincronizados.
- Utilizar commits pequeños y descriptivos.
- Evitar cambios ajenos al objetivo del sprint.
- Utilizar datos sintéticos para desarrollo y pruebas.
- Conservar la trazabilidad con los requisitos aprobados.
- Registrar decisiones técnicas y cambios de alcance.
- Ejecutar validaciones antes de publicar cambios.

## Prioridades y dependencias

La secuencia es orientativa y podrá ajustarse tras el Sprint 0.

Los trabajos de seguridad, privacidad y prevención de
conflictos son obligatorios antes del lanzamiento comercial.

Los pagos de servicios y las mejoras no esenciales podrán
posponerse si no bloquean la operación contratada por Viglam.

La integración comercial de WhatsApp debe verificarse
tempranamente para evitar descubrir bloqueos al final.

## Referencias

- 01-decisiones-aprobadas.md
- 02-requisitos-funcionales.md
- 03-requisitos-no-funcionales.md
- 04-reglas-negocio.md
- 05-arquitectura.md
