import { phases } from './pricing'

// Sin `a` se muestra "Información por confirmar". Editar aquí las respuestas.
export type Faq = { q: string; a?: string }

export const faq: Faq[] = [
  { q: '¿Cuándo y dónde es?', a: '30 de octubre de 2026, en El Patio Salón de Eventos, Zacatecas.' },
  {
    q: '¿Cuánto cuestan los boletos?',
    a: phases.map((p) => `${p.label}: $${p.price} (${p.range.toLowerCase()})`).join('. ') + '.',
  },
  { q: '¿Cómo funciona el boleto virtual?', a: 'El boleto es virtual y usa código QR. Puedes comprar varios boletos en una sola compra.' },
  { q: '¿Habrá concurso de disfraces?', a: 'Sí. Hay premios para los mejores y los peores disfraces.' },
  { q: '¿A qué hora empieza?' },
  { q: '¿Hay edad mínima?' },
  { q: '¿Puedo pagar con tarjeta el día del evento?' },
  { q: '¿Puedo ingresar mi propia bebida?', a: 'Sí. No hay descorche: puedes traer tu propio alcohol.' },
]
