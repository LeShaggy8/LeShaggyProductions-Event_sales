import type { ReactNode } from 'react'

type Props = { id?: string; title?: string; intro?: string; children: ReactNode }

export default function Section({ id, title, intro, children }: Props) {
  return (
    <section id={id} className="px-5 py-20">
      <div className="mx-auto max-w-6xl">
        {title && <h2 className="font-display text-4xl text-ink md:text-5xl">{title}</h2>}
        {intro && <p className="mt-3 max-w-prose text-ink/80">{intro}</p>}
        <div className={title ? 'mt-8' : ''}>{children}</div>
      </div>
    </section>
  )
}
