import { experience } from '../../content/experience'
import Section from '../ui/Section'

export default function Experience() {
  return (
    <Section id="experiencia" title="La experiencia">
      <ul className="divide-y divide-white/10 border-y border-white/10">
        {experience.map((i) => (
          <li key={i.title} className="flex flex-col gap-1 py-5 md:flex-row md:items-baseline md:gap-8">
            <h3 className="font-display text-xl text-ink md:w-1/3">{i.title}</h3>
            <p className="text-ink/80">{i.text}</p>
          </li>
        ))}
      </ul>
    </Section>
  )
}
