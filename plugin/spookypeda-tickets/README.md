# SpookyPeda Tickets (Fase 1)

Al pasar una orden de WooCommerce a **Completada**:
1. Busca los `etn-attendee` ligados a la orden (`eventin_order_id`).
2. Registra cada `etn_unique_ticket_id` en la tabla `{prefijo}spp_tickets` (estado `unused`).
3. Envía al comprador el correo "Tus boletos" con un PNG por boleto. El QR contiene `SP5:<TicketID>`.

Si Eventin aún no creó los asistentes, reintenta (hasta 6 veces). Si algo falla, deja una nota en la orden.

**Reenviar / órdenes anteriores:** en la orden, menú "Acciones del pedido" → *Enviar/reenviar boletos con QR*.

Requisitos: WooCommerce, Eventin, PHP 7.4+ con GD. Librería QR incluida: kazuhikoarase/qrcode-generator (MIT).
No incluye el escáner ni la validación de entrada (Fase 2).
