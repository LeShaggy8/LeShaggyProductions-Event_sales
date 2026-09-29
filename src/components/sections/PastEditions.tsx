import { media } from '../../content/past-editions'
import Section from '../ui/Section'

export default function PastEditions() {
  return (
    <Section id="ediciones" title="Ediciones anteriores" intro="Así se ha vivido SpookyPeda.">
      <div className="grid grid-cols-2 gap-3 md:grid-cols-3">
        {media.map((m) => (
          <div
            key={m.id}
            className="relative aspect-[4/5] overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-blue/20 via-void to-violet/20"
          >
            {m.src && m.kind === 'photo' && (
              <img src={m.src} alt={m.alt} loading="lazy" className="h-full w-full object-cover" />
            )}
            {m.src && m.kind === 'video' && (
              <video src={m.src} muted loop playsInline autoPlay preload="none" aria-label={m.alt} className="h-full w-full object-cover" />
            )}
            {!m.src && (
              <p className="micro absolute inset-0 grid place-items-center">
                {m.kind === 'video' ? 'VIDEO PENDIENTE' : 'FOTO PENDIENTE'}
              </p>
            )}
          </div>
        ))}
      </div>
    </Section>
  )
}
