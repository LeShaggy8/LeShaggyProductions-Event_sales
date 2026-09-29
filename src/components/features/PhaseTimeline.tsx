import { phases } from '../../content/pricing'

export default function PhaseTimeline({ current }: { current: string }) {
  return (
    <ol className="mt-8 grid gap-4 md:grid-cols-3">
      {phases.map((p) => {
        const on = p.id === current
        return (
          <li
            key={p.id}
            className={`rounded-2xl border p-6 ${on ? 'glow-neon border-neon bg-neon/5' : 'border-white/10 opacity-60'}`}
          >
            <p className="text-sm text-ink/70">{p.label}</p>
            <p className={`font-display text-5xl ${on ? 'text-neon' : 'text-ink'}`}>${p.price}</p>
            <p className="mt-1 text-sm text-ink/80">{p.range}</p>
          </li>
        )
      })}
    </ol>
  )
}
