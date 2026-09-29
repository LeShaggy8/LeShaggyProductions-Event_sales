import { faq } from '../../content/faq'
import Section from '../ui/Section'

export default function Faq() {
  return (
    <Section id="faq" title="Preguntas frecuentes">
      <div className="divide-y divide-white/10 border-y border-white/10">
        {faq.map((f) => (
          <details key={f.q} className="py-4">
            <summary className="cursor-pointer list-none font-display text-lg text-ink focus-visible:outline-2 focus-visible:outline-cyan [&::-webkit-details-marker]:hidden">
              {f.q}
            </summary>
            <p className="mt-2 max-w-prose text-ink/80">{f.a ?? 'Información por confirmar.'}</p>
          </details>
        ))}
      </div>
    </Section>
  )
}
