import Header from './components/layout/Header'
import StickyCta from './components/layout/StickyCta'
import Hero from './components/sections/Hero'
import Tickets from './components/sections/Tickets'

export default function App() {
  return (
    <>
      <Header />
      <main>
        <Hero />
        <Tickets />
      </main>
      <footer className="px-5 pb-24 pt-10 text-center md:pb-10">
        <p className="micro">SPOOKYPEDA VOL. V // 30.10.2026 // ZACATECAS</p>
      </footer>
      <StickyCta />
    </>
  )
}
