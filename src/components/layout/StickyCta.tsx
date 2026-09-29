import { useEffect, useState } from 'react'
import { getCurrentPhase } from '../../lib/pricing'
import BuyButton from '../ui/BuyButton'

// Barra fija de compra solo en móvil; aparece al salir del Hero.
export default function StickyCta() {
  const [show, setShow] = useState(false)
  const phase = getCurrentPhase()

  useEffect(() => {
    const hero = document.getElementById('hero')
    if (!hero) return
    const io = new IntersectionObserver(([e]) => setShow(!e.isIntersecting))
    io.observe(hero)
    return () => io.disconnect()
  }, [])

  return (
    <div
      className={`fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-3 border-t border-neon/30 bg-void/90 px-4 py-3 backdrop-blur transition-transform md:hidden ${
        show ? 'translate-y-0' : 'translate-y-full'
      }`}
    >
      <span className="font-display text-neon">${phase.price} pesos</span>
      <BuyButton small />
    </div>
  )
}
