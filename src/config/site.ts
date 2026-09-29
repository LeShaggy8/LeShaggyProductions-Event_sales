// ÚNICO punto de salida a la compra. Cambiar el destino = editar esta línea
// (o definir VITE_PURCHASE_URL al compilar). Solo BuyButton la consume.
export const PURCHASE_URL: string = import.meta.env.VITE_PURCHASE_URL ?? '#boletos'
