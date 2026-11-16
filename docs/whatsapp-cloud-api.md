# WhatsApp Cloud API

## Prueba extremo a extremo

Se confirmó el flujo de prueba completo con un destinatario autorizado:

`WhatsApp → Meta → Cloudflare Tunnel → webhook Laravel → Meta → respuesta automática`

La respuesta automática devolvió HTTP 200 al usar un token temporal emitido por Meta. No se versionan tokens, identificadores de acceso ni respuestas con datos sensibles.

## Estado de credenciales

- El token temporal funciona con el número de prueba y permite responder automáticamente.
- El token permanente de tipo `SYSTEM_USER` devuelve `(#10) Viewer cannot access the provided Paid Message Account` al responder.
- La revisión de Meta confirma para AgendaNails el número de prueba registrado, webhook configurado y la suscripción de la cuenta de prueba a la aplicación.
- El MCP no permite inspeccionar la vinculación efectiva de un token ya emitido con sus activos. Por ello, el error #10 se trata como una asignación o emisión pendiente de confirmar, no como una falla del webhook o de Laravel.

## Cloudflare Tunnel

La URL pública de Cloudflare Tunnel usada para el webhook es temporal. Cada vez que cambie, debe actualizarse manualmente como callback de WhatsApp en la configuración de la aplicación Meta y verificarse antes de probar el flujo.

## Siguiente acción para el token permanente

En Meta Business Settings, comprobar que el usuario del sistema tenga **Full control** sobre la aplicación AgendaNails y sobre la cuenta de WhatsApp de prueba exacta; después generar un nuevo token de ese mismo usuario y aplicación con `business_management`, `whatsapp_business_management` y `whatsapp_business_messaging`. No usar ni modificar los activos de Velas Art y Lux.

## Preparación para integración real (2026-11-16)

Estado del código (verificado con pruebas **simuladas**):
- Cada número se asocia a su negocio por `businesses.whatsapp_phone_number_id`; las reservas validan el negocio del número (sin canal global). En producción no existe negocio por defecto: los números desconocidos se registran como `ignored` y no responden.
- Firma del webhook (`X-Hub-Signature-256`), idempotencia (`wa_message_id`) y aislamiento multiempresa probados; conversación completa (menú → disponibilidad → reserva → confirmación → cancelación) ejecutada por webhook con Barbería Demo.

Verificado con Meta (real): únicamente la prueba histórica con token temporal registrada arriba. NO se han publicado Flows ni enviado mensajes reales en esta fase.

Configuración requerida para la puesta en marcha (acción externa):
1. Número comercial habilitado por negocio y `whatsapp_phone_number_id` registrado en cada fila de `businesses`.
2. Token permanente `SYSTEM_USER` con `whatsapp_business_management` y `whatsapp_business_messaging` (pendiente por el error #10 de la Messaging Account).
3. Webhook apuntando a la URL pública estable con `WHATSAPP_VERIFY_TOKEN` registrado en Meta.
4. Plantillas UTILITY aprobadas para avisos fuera de la ventana de 24 horas.
