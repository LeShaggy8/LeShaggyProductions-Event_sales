import { event } from '../../content/event'
import { getCurrentPhase } from '../../lib/pricing'
import BuyButton from '../ui/BuyButton'

export default function FinalCta() {
  const phase = getCurrentPhase()
  return (
    <section className="px-5 py-24 text-center">
      <div className="mx-auto max-w-2xl">
        <p className="micro">TRANSMISSION 05</p>
        <h2 className="mt-4 font-display text-4xl text-ink md:text-6xl">Nos vemos el 30 de octubre</h2>
        <p className="mt-4 text-ink/80">
          {event.venue} · {event.city} · {phase.label}, ${phase.price} pesos
        </p>
        <div className="mt-8">
          <BuyButton />
        </div>
      </div>
    </section>
  )
}
