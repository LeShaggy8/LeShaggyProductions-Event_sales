// ÚNICO punto de salida a la compra. Cambiar el destino = editar esta línea
// (o definir VITE_PURCHASE_URL al compilar). Solo BuyButton la consume.
export const PURCHASE_URL: string = import.meta.env.VITE_PURCHASE_URL ?? '#boletos'

// Google Maps: pegar aquí el enlace real (o definir VITE_MAPS_URL / VITE_MAPS_EMBED_URL al compilar).
// Mientras estén vacíos, la sección Ubicación muestra un placeholder.
export const MAPS_URL: string = import.meta.env.VITE_MAPS_URL ?? 'https://maps.app.goo.gl/6GJdCPEMLUsL6vsAA'
export const MAPS_EMBED_URL: string =
  import.meta.env.VITE_MAPS_EMBED_URL ?? 'https://maps.google.com/maps?q=22.7693468,-102.5951324&z=16&hl=es&output=embed'
