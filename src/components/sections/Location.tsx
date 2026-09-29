import { MAPS_EMBED_URL, MAPS_URL } from '../../config/site'
import { event } from '../../content/event'
import Section from '../ui/Section'

export default function Location() {
  return (
    <Section id="ubicacion" title="Ubicación" intro={`${event.venueFull}, ${event.city}.`}>
      {MAPS_EMBED_URL ? (
        <iframe
          src={MAPS_EMBED_URL}
          title={`Mapa de ${event.venue}`}
          loading="lazy"
          className="h-72 w-full rounded-2xl border border-white/10 md:h-96"
        />
      ) : (
        <div className="grid h-72 place-items-center rounded-2xl border border-dashed border-cyan/40 md:h-96">
          <p className="micro">MAPA PENDIENTE</p>
        </div>
      )}
      {MAPS_URL && (
        <p className="mt-5">
          <a
            href={MAPS_URL}
            target="_blank"
            rel="noopener noreferrer"
            className="font-display text-cyan underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan"
          >
            Abrir en Google Maps
          </a>
        </p>
      )}
    </Section>
  )
}
