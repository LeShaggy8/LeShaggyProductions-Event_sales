import { phases, type PricePhase } from '../content/pricing'

const TZ = 'America/Mexico_City'

/** Fase vigente según la fecha (hora de México). Después del evento devuelve la última. */
export function getCurrentPhase(now: Date = new Date()): PricePhase {
  const today = new Intl.DateTimeFormat('en-CA', { timeZone: TZ }).format(now) // YYYY-MM-DD
  return phases.find((p) => today <= p.endsOn) ?? phases[phases.length - 1]
}
