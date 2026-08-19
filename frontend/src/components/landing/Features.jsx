import { FEATURES } from '../../lib/content'
import Card from '../ui/Card'
import Icon from '../ui/Icon'
import Reveal from '../ui/Reveal'
import Section, { SectionHeading } from '../ui/Section'

const ACCENTS = {
  navy: 'bg-navy-50 text-navy-800 group-hover:bg-navy-800 group-hover:text-white',
  emerald: 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-500 group-hover:text-white',
  purple: 'bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white',
}

export default function Features() {
  return (
    <Section id="features" tone="canvas">
      <SectionHeading
        eyebrow="Platform"
        title="Everything an event needs, in one place"
        description="Six tools that replace the spreadsheet sprawl, built to work together from the first brief to the final invoice."
      />

      <div className="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {FEATURES.map((feature, index) => (
          <Reveal key={feature.title} delay={index * 0.05}>
            <Card hover className="group h-full p-7">
              <span
                className={`grid size-12 place-items-center rounded-xl transition-colors duration-300 ${ACCENTS[feature.accent]}`}
              >
                <Icon name={feature.icon} className="size-6" />
              </span>

              <h3 className="mt-5 text-[1.1875rem] font-bold text-ink">{feature.title}</h3>
              <p className="mt-2.5 leading-relaxed text-muted">{feature.description}</p>
            </Card>
          </Reveal>
        ))}
      </div>
    </Section>
  )
}
