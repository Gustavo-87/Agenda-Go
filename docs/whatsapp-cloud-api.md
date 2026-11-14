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
