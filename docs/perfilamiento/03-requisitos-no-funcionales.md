# AgendaNails — Requisitos no funcionales

Estado: perfilamiento aprobado.
Alcance: plataforma SaaS multisectorial.
Primer cliente: Viglam.

## 1. Seguridad

### RNF-SEG-001 — Autenticación
Prioridad: P0

- Administradores y superadministradores utilizarán correo,
  contraseña y segundo factor obligatorio (2FA).
- Profesionales utilizarán correo y contraseña.
- Las contraseñas se almacenarán mediante algoritmos seguros.
- Se limitarán los intentos fallidos de inicio de sesión.
- Existirá recuperación segura de contraseñas.

### RNF-SEG-002 — Aislamiento multiempresa
Prioridad: P0

- Cada negocio accederá exclusivamente a sus datos.
- Las consultas y operaciones validarán el contexto del negocio.
- Los permisos se aplicarán en servidor, no solo en la interfaz.
- Se probarán intentos de acceso entre negocios.

### RNF-SEG-003 — Auditoría
Prioridad: P0

- Registrar accesos y modificaciones relevantes.
- Auditar reservas, servicios, precios, horarios, permisos,
  membresías y pagos.
- Registrar usuario, negocio, operación y fecha.
- Proteger los registros contra modificaciones no autorizadas.
- No registrar contraseñas, tokens ni secretos.

### RNF-SEG-004 — Protección de credenciales
Prioridad: P0

- Almacenar credenciales e integraciones de forma cifrada.
- No publicar secretos en Git.
- Aplicar permisos mínimos a usuarios e integraciones.
- Permitir renovación o revocación de credenciales.
- Utilizar HTTPS en las comunicaciones externas.

## 2. Datos personales

### RNF-DAT-001 — Tratamiento de datos
Prioridad: P0

- Presentar aviso de privacidad durante la primera reserva.
- Registrar evidencia de autorización cuando corresponda.
- Mantener separadas las autorizaciones comerciales.
- Restringir el acceso a datos personales.
- Contemplar procedimientos para consultas, correcciones
  y solicitudes de eliminación conforme a la legislación aplicable.

### RNF-DAT-002 — Conservación de datos
Prioridad: P0

- Conservar datos de negocios cancelados durante 90 días,
  salvo obligaciones legales que requieran otros plazos.
- Permitir exportación mediante solicitud al superadministrador.
- Registrar las solicitudes y entregas de información.
- Definir procedimientos seguros de eliminación.

## 3. Infraestructura

### RNF-INF-001 — Alojamiento en la nube
Prioridad: P0

- Desplegar Laravel en un servidor de producción.
- No depender de computadores personales ni túneles temporales.
- Utilizar HTTPS y configuración segura.
- Contar con base de datos persistente.
- Ejecutar colas de trabajos y tareas programadas.
- Separar desarrollo y producción.

### RNF-INF-002 — Dominio único
Prioridad: P0

- Todos los negocios accederán desde un dominio compartido.
- El negocio se identificará mediante autenticación
  y autorización contextual.
- No depender de subdominios para garantizar aislamiento.

### RNF-INF-003 — Escalabilidad
Prioridad: P1

- Incorporar nuevos negocios sin duplicar la aplicación.
- Permitir crecimiento de usuarios, reservas y mensajes.
- Separar procesos de mensajería y tareas pesadas
  mediante colas cuando corresponda.

## 4. Disponibilidad y continuidad

### RNF-DIS-001 — Reservas permanentes
Prioridad: P0

- La plataforma permitirá recibir reservas las 24 horas,
  siempre que los servicios externos estén disponibles.
- El horario de soporte no limitará el acceso a las reservas.
- Los errores externos se registrarán para diagnóstico.

### RNF-DIS-002 — Copias de seguridad
Prioridad: P0

- Realizar respaldos automáticos cada 24 horas.
- Conservar las copias durante 30 días.
- Almacenar respaldos fuera del servidor principal.
- Verificar periódicamente su restauración.
- Documentar los procedimientos de recuperación.

Nota: esta frecuencia supone una posible pérdida de cambios
de hasta 24 horas entre respaldos, sujeto a la estrategia
definitiva de recuperación.

### RNF-DIS-003 — Manejo de fallas
Prioridad: P0

- Registrar fallas de integraciones y tareas automáticas.
- Evitar duplicaciones durante reintentos.
- Mantener las reservas válidas ante fallas de Google Calendar.
- No confirmar pagos sin validación del proveedor.

## 5. Rendimiento e integridad

### RNF-REN-001 — Integridad de las reservas
Prioridad: P0

- Prevenir reservas solapadas incluso con solicitudes simultáneas.
- Validar disponibilidad nuevamente antes de confirmar.
- Garantizar operaciones transaccionales.
- Evitar procesar repetidamente el mismo evento externo.

### RNF-REN-002 — Rendimiento
Prioridad: P1

- Optimizar consultas de disponibilidad.
- Paginar listados extensos.
- Utilizar índices y consultas eficientes.
- Ejecutar notificaciones y sincronizaciones mediante colas.
- Definir métricas y umbrales medibles antes de producción.

## 6. Experiencia de usuario

### RNF-UX-001 — Interfaz adaptable
Prioridad: P0

- Soportar computadores, tabletas y teléfonos.
- Priorizar operaciones sencillas para profesionales.
- Mantener formularios claros y mensajes comprensibles.
- Ofrecer una interfaz consistente entre los módulos.

### RNF-MOV-001 — Aplicación PWA
Prioridad: P1

- Permitir instalación desde navegadores compatibles.
- Mantener diseño adaptable.
- Proporcionar iconos y manifiesto de aplicación.
- Gestionar adecuadamente los cambios de versión.
- Informar cuando una operación requiera conexión a Internet.

### RNF-MOV-002 — Notificaciones push
Prioridad: P1

- Enviar alertas a dispositivos compatibles.
- Solicitar autorización antes de activar notificaciones.
- Permitir gestionar la suscripción del dispositivo.
- Evitar notificaciones repetidas.
- Mantener avisos internos si las push no están disponibles.

## 7. Integraciones

### RNF-INT-001 — WhatsApp
Prioridad: P0

- Aislar la configuración de cada negocio.
- Validar autenticidad de webhooks.
- Gestionar errores, límites y expiración de credenciales.
- Registrar identificadores y estados de los envíos.
- Evitar incluir datos sensibles en registros técnicos.
- Contemplar restricciones comerciales de Meta.

### RNF-INT-002 — Pasarelas de pago
Prioridad: P1

- Utilizar integraciones autorizadas.
- Validar autenticidad de notificaciones de pago.
- Impedir confirmaciones duplicadas.
- Diferenciar pagos de membresías y pagos de servicios.
- Mantener trazabilidad de transacciones.
- No almacenar información sensible de tarjetas.

### RNF-INT-003 — Google Calendar
Prioridad: P1

- La sincronización será opcional y unidireccional.
- AgendaNails será la fuente principal de las reservas.
- Los fallos de Google no impedirán registrar citas.
- Los reintentos no crearán eventos duplicados.

## 8. Soporte y mantenimiento

### RNF-SOP-001 — Horario de soporte
Prioridad: P1

- Atención de lunes a sábado.
- Horario: 08:00–18:00, hora de Colombia.

### RNF-SOP-002 — Incidentes críticos
Prioridad: P1

- Priorizar fallas críticas fuera del horario habitual.
- Clasificar incidentes según impacto.
- Registrar atención, diagnóstico y solución.
- Definir tiempos de respuesta antes de asumir compromisos
  contractuales.

## 9. Calidad del software

### RNF-CAL-001 — Pruebas
Prioridad: P0

- Pruebas de autenticación, permisos y aislamiento multiempresa.
- Pruebas de reservas simultáneas y prevención de conflictos.
- Pruebas del ciclo de membresías.
- Pruebas de pagos y notificaciones.
- Pruebas de integración con servicios externos usando simulaciones.
- Pruebas de aceptación con el primer negocio.

### RNF-CAL-002 — Versionado y trazabilidad
Prioridad: P0

- Utilizar Git y GitHub.
- Mantener commits descriptivos en español.
- Documentar requisitos y decisiones.
- Relacionar funcionalidades con pruebas y entregas.
- Organizar el desarrollo por sprints.
- Conservar datos sintéticos en las pruebas.

## 10. Aspectos pendientes

- Definir objetivos cuantitativos de rendimiento.
- Definir disponibilidad contractual y métricas de servicio.
- Seleccionar proveedor de alojamiento.
- Determinar estrategia de recuperación ante desastres.
- Confirmar capacidades y costos de mensajería.
- Seleccionar proveedores de pago.
- Validar requisitos legales de tratamiento de datos.
- Definir plazos de respuesta para incidentes críticos.

## 11. Referencias internas

- 01-decisiones-aprobadas.md
- 02-requisitos-funcionales.md

Los requisitos deberán verificarse mediante pruebas
antes de declararse implementados para producción.
