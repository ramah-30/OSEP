import { IMAGES } from '../../lib/content'
import Icon from '../ui/Icon'
import Reveal from '../ui/Reveal'
import Section, { Eyebrow } from '../ui/Section'

const PILLARS = [
  {
    icon: 'Sparkles',
    title: 'Intelligence that assists',
    body: 'AI drafts the parts that are tedious. Every decision stays yours.',
  },
  {
    icon: 'ShieldCheck',
    title: 'Trust by default',
    body: 'Verified vendors, role-based access and a full audit trail from day one.',
  },
  {
    icon: 'Globe2',
    title: 'Built to travel',
    body: 'One platform for a 40-guest wedding and a 4,000-delegate conference alike.',
  },
]

export default function About() {
  return (
    <Section id="about" tone="surface">
      <div className="grid items-center gap-14 lg:grid-cols-2 lg:gap-20">
        <Reveal>
          <Eyebrow>About OSEP</Eyebrow>

          <h2 className="mt-5 text-h2 font-extrabold text-ink text-balance">
            Great events are built on hundreds of small decisions
          </h2>

          <div className="mt-6 space-y-4 text-lead text-muted text-pretty">
            <p>
              Most of them get made in inboxes, group chats and spreadsheets that nobody can find
              two weeks later. That is where budgets slip and details quietly go missing.
            </p>
            <p>
              OSEP exists to put those decisions in one place — visible to the planner running the
              event, the vendors delivering it and the client paying for it. The technology should
              be doing the remembering, so the people can do the creating.
            </p>
          </div>

          <div className="mt-10 space-y-6">
            {PILLARS.map((pillar) => (
              <div key={pillar.title} className="flex gap-4">
                <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-navy-50 text-navy-800">
                  <Icon name={pillar.icon} className="size-5" />
                </span>
                <div>
                  <p className="font-bold text-ink">{pillar.title}</p>
                  <p className="mt-1 leading-relaxed text-muted">{pillar.body}</p>
                </div>
              </div>
            ))}
          </div>
        </Reveal>

        <Reveal delay={0.1} className="relative">
          <div className="overflow-hidden rounded-card shadow-lift">
            <img
              src={IMAGES.aboutTeam}
              alt="A planning team reviewing an event brief together"
              className="aspect-4/5 w-full object-cover md:aspect-3/2 lg:aspect-4/5"
              loading="lazy"
            />
          </div>

          <div className="absolute -bottom-6 -left-4 hidden w-64 rounded-card border border-line bg-surface p-5 shadow-lift sm:block">
            <p className="text-sm font-semibold text-muted">Average planning time saved</p>
            <p className="mt-1 text-h3 font-extrabold text-emerald-600">31 hours</p>
            <p className="mt-1 text-sm text-muted">per event, across 12,500 events</p>
          </div>
        </Reveal>
      </div>
    </Section>
  )
}
