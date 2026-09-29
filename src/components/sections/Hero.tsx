import { event } from '../../content/event'
import { getCurrentPhase } from '../../lib/pricing'
import HeroVisual from '../features/HeroVisual'
import BuyButton from '../ui/BuyButton'

export default function Hero() {
  const phase = getCurrentPhase()
  const title =
    'block font-display leading-[0.9] tracking-tight text-[clamp(2.4rem,11.5vw,6rem)] md:text-[clamp(2.4rem,5.6vw,4.5rem)]'

  return (
    <section id="hero" className="relative overflow-hidden px-5 pb-16 pt-24 md:pb-24 md:pt-32">
      <div className="mx-auto grid max-w-6xl items-center gap-8 md:grid-cols-2">
        {/* En móvil el alien va arriba; en escritorio, a la derecha. Nunca bajo el texto. */}
        <HeroVisual className="order-first md:order-last" />
        <div className="relative z-10">
          <p className="micro">SIGNAL DETECTED // VOL. V</p>
          <h1 className="mt-4">
            <span className={`${title} text-ink`}>{event.name.toUpperCase()}</span>
            <span className={`${title} text-neon text-glow`}>{event.edition.toUpperCase()}</span>
          </h1>
          <p className="mt-6 text-lg tracking-wide text-ink">{event.dateLabel}</p>
          <p className="text-ink/80">
            {event.venue} · {event.city}
          </p>
          <p className="glow-neon mt-6 inline-block rounded-full border border-neon/60 px-4 py-1.5 font-display text-neon">
            {phase.label} — ${phase.price} pesos
          </p>
          <p className="mt-2 text-sm text-ink/70">{event.ticketNote}</p>
          <div className="mt-8">
            <BuyButton />
          </div>
          <p className="micro mt-10">30.10.2026 // ZACATECAS</p>
        </div>
      </div>
    </section>
  )
}
