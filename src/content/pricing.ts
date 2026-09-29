export type PricePhase = {
  id: string
  label: string
  price: number
  range: string
  /** Último día (YYYY-MM-DD, hora de México) en que aplica esta fase */
  endsOn: string
}

// Solo se conocen las fechas de cierre. Mantener en sincronía con WooCommerce.
export const phases: PricePhase[] = [
  { id: 'f1', label: 'Fase 1', price: 80, range: 'Hasta el 15 de octubre', endsOn: '2026-10-15' },
  { id: 'f2', label: 'Fase 2', price: 100, range: 'Del 16 al 29 de octubre', endsOn: '2026-10-29' },
  { id: 'door', label: 'Día del evento', price: 120, range: '30 de octubre', endsOn: '2026-10-30' },
]
