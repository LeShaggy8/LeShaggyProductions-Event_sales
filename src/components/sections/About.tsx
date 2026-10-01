import { history } from '../../content/history'
import Section from '../ui/Section'

export default function About() {
  return (
    <Section
      id="sobre"
      title="Qué es SpookyPeda"
      intro="La fiesta de Halloween de Zacatecas. Empezó con más de 100 personas y este año llega a su quinta edición."
    >
      <ul className="max-w-2xl space-y-4">
        {history.map((h) => (
          <li key={h.edition} className="grid grid-cols-[4.5rem_1fr_4.5rem] items-center gap-3 text-sm">
            <span className="text-ink/70">{h.edition}</span>
            <span
              className="h-3 rounded-full bg-gradient-to-r from-blue via-violet to-neon"
              style={{ width: `${Math.max((h.size / 800) * 100, 4)}%` }}
            />
            <span className="text-right font-display text-neon">{h.people}</span>
          </li>
        ))}
      </ul>
      <p className="mt-6 text-sm text-ink/60">Asistentes aproximados por edición.</p>
    </Section>
  )
}
