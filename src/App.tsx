import Header from './components/layout/Header'
import StickyCta from './components/layout/StickyCta'
import About from './components/sections/About'
import Edition from './components/sections/Edition'
import Experience from './components/sections/Experience'
import Faq from './components/sections/Faq'
import FinalCta from './components/sections/FinalCta'
import Hero from './components/sections/Hero'
import Location from './components/sections/Location'
import PastEditions from './components/sections/PastEditions'
import Tickets from './components/sections/Tickets'

export default function App() {
  return (
    <>
      <Header />
      <main>
        <Hero />
        <About />
        <Experience />
        <Edition />
        <Tickets />
        <PastEditions />
        <Location />
        <Faq />
        <FinalCta />
      </main>
      <footer className="px-5 pb-24 pt-10 text-center md:pb-10">
        <p className="micro">SPOOKYPEDA VOL. V // 30.10.2026 // ZACATECAS</p>
      </footer>
      <StickyCta />
    </>
  )
}
