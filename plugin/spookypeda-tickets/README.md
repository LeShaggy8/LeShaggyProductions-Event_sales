# SpookyPeda Tickets

## Fase 1 — emisión (sin cambios)
Al pasar una orden de WooCommerce a **Completada**: busca los `etn-attendee` de la orden (`eventin_order_id`), registra cada `etn_unique_ticket_id` en `{prefijo}spp_tickets` y envía el correo "Tus boletos" con un PNG por boleto. El QR contiene `SP5:<TicketID>`. Reenvío manual: acción de pedido *Enviar/reenviar boletos con QR*.

## Fase 2 — escáner y validación (v0.2.0)
- Página: `https://<sitio>/escaner` (requiere sesión; si no hay, redirige al login y vuelve).
- Cámara del celular (librería jsQR incluida localmente, sin CDN) + captura manual del Ticket ID.
- Resultados: **VÁLIDO** (verde, lo marca `used`), **YA USADO** (ámbar, con hora), **NO VÁLIDO** / **ANULADO** (rojo).
- **Atómico:** el cambio a `used` es un solo `UPDATE ... WHERE ticket_id=? AND status='unused'`. Si dos celulares escanean a la vez, solo uno recibe "1 fila afectada" y entra; el otro ve "YA USADO".
- **Anulado:** si el pedido ya no está en *Completado* (reembolso/cancelación) el boleto se rechaza sin consumirse.
- **Bitácora:** cada intento (también los inválidos) se guarda en `{prefijo}spp_scans`: fecha/hora (UTC), usuario, origen (cámara/manual), código, Ticket ID y resultado.

### Usuarios de la puerta
Usuarios → Añadir nuevo → rol **Escáner SpookyPeda** (un usuario por celular). Los administradores también pueden escanear. Elimina esos usuarios al terminar el evento.

### Cambios en la base de datos (v2) — solo se agregan cosas
- Nueva tabla `{prefijo}spp_scans`. `spp_tickets` no cambia.
- Nuevo rol `spp_scanner` y permiso `spp_scan` (también para administradores).
- Se aplican solos una vez, en la primera carga tras actualizar el plugin (opción `spp_db_version` = 2).
- No se borra ni modifica ningún dato existente.

### Pruebas (SQL, en phpMyAdmin)
Devolver un boleto de PRUEBA a "sin usar":
```sql
UPDATE wp77_spp_tickets SET status='unused', used_at=NULL, used_by=NULL WHERE ticket_id='tm39lapsuj';
```
Ver los últimos escaneos:
```sql
SELECT scanned_at, source, ticket_id, result FROM wp77_spp_scans ORDER BY id DESC LIMIT 20;
```

Requisitos: WooCommerce, Eventin, PHP 7.4+ con GD, HTTPS (la cámara lo exige).
Librerías incluidas: kazuhikoarase/qrcode-generator (MIT) y jsQR 1.4.0 (Apache-2.0).
