import { activities } from '../../content/activities'
import Section from '../ui/Section'

export default function Edition() {
  const shown = activities.filter((a) => a.confirmed)
  return (
    <Section
      id="vol-5"
      title="Vol. V: llegan los alienígenas"
      intro="La temática de esta edición es alienígena: diseño, decoración y ambiente."
    >
      <div className="rounded-2xl border border-cyan/40 bg-cyan/5 p-6 md:max-w-xl">
        <h3 className="font-display text-xl text-cyan">Boleto virtual con código QR</h3>
        <p className="mt-2 text-ink/80">Esta edición estrena el sistema de boletos virtuales.</p>
      </div>
      {/* Solo aparecen las actividades con confirmed: true en content/activities.ts */}
      {shown.length > 0 && (
        <ul className="mt-6 grid gap-4 sm:grid-cols-2 md:grid-cols-3">
          {shown.map((a) => (
            <li key={a.id} className="rounded-2xl border border-white/10 p-5">
              <h3 className="font-display text-lg text-ink">{a.title}</h3>
              {a.text && <p className="mt-1 text-sm text-ink/80">{a.text}</p>}
            </li>
          ))}
        </ul>
      )}
    </Section>
  )
}
