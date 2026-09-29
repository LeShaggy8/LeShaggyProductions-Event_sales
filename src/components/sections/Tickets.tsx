import { getCurrentPhase } from '../../lib/pricing'
import PhaseTimeline from '../features/PhaseTimeline'
import BuyButton from '../ui/BuyButton'

export default function Tickets() {
  const current = getCurrentPhase()
  return (
    <section id="boletos" className="px-5 py-20">
      <div className="mx-auto max-w-6xl">
        <h2 className="font-display text-4xl text-ink md:text-5xl">Boletos</h2>
        <p className="mt-3 max-w-prose text-ink/80">
          Boleto virtual con código QR. Puedes comprar varios en una sola compra.
        </p>
        <PhaseTimeline current={current.id} />
        <div className="mt-10">
          <BuyButton>Comprar boleto — ${current.price}</BuyButton>
        </div>
      </div>
    </section>
  )
}
