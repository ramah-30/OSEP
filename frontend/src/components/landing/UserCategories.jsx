import { USER_CATEGORIES } from '../../lib/content'
import Button from '../ui/Button'
import Card from '../ui/Card'
import Icon from '../ui/Icon'
import Reveal from '../ui/Reveal'
import Section, { SectionHeading } from '../ui/Section'

const ACCENTS = {
  navy: { chip: 'bg-navy-50 text-navy-800', tick: 'text-navy-700', rule: 'bg-navy-800' },
  emerald: { chip: 'bg-emerald-50 text-emerald-600', tick: 'text-emerald-600', rule: 'bg-emerald-500' },
  purple: { chip: 'bg-purple-50 text-purple-600', tick: 'text-purple-600', rule: 'bg-purple-600' },
}

export default function UserCategories() {
  return (
    <Section id="who-its-for" tone="canvas">
      <SectionHeading
        eyebrow="Built for three kinds of people"
        eyebrowTone="purple"
        title="Pick the account that matches how you work"
        description="Your workspace, your dashboard and the tools you see are shaped by the account type you choose at signup."
      />

      <div className="mt-14 grid gap-6 lg:grid-cols-3">
        {USER_CATEGORIES.map((category, index) => {
          const accent = ACCENTS[category.accent]

          return (
            <Reveal key={category.key} delay={index * 0.08}>
              <Card hover className="flex h-full flex-col p-8">
                <span className={`grid size-12 place-items-center rounded-xl ${accent.chip}`}>
                  <Icon name={category.icon} className="size-6" />
                </span>

                <h3 className="mt-5 text-h3 font-extrabold text-ink">{category.title}</h3>
                <p className="mt-1 text-sm font-semibold text-muted">{category.tagline}</p>

                <span className={`mt-5 block h-1 w-12 rounded-full ${accent.rule}`} />

                <p className="mt-5 leading-relaxed text-muted">{category.description}</p>

                <ul className="mt-6 flex-1 space-y-3">
                  {category.benefits.map((benefit) => (
                    <li key={benefit} className="flex gap-3 text-[0.95rem] text-ink/80">
                      <Icon name="Check" className={`mt-0.5 size-[18px] shrink-0 ${accent.tick}`} />
                      {benefit}
                    </li>
                  ))}
                </ul>

                <Button
                  to={`/register?type=${category.key}`}
                  variant="secondary"
                  fullWidth
                  className="mt-8"
                >
                  {category.cta}
                  <Icon name="ArrowRight" className="size-4" />
                </Button>
              </Card>
            </Reveal>
          )
        })}
      </div>
    </Section>
  )
}
