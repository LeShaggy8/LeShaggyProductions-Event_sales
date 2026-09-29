import BuyButton from '../ui/BuyButton'

export default function Header() {
  return (
    <header className="fixed inset-x-0 top-0 z-30 border-b border-white/10 bg-void/70 backdrop-blur">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-5 py-3">
        <a href="#hero" className="font-display text-ink">
          SPOOKYPEDA <span className="text-neon">V</span>
        </a>
        <BuyButton small>Boletos</BuyButton>
      </div>
    </header>
  )
}
