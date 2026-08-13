import { motion } from 'framer-motion'
import { useReveal } from '../../lib/useReveal'
import { STEPS } from '../../lib/content'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import Reveal from '../ui/Reveal'
import Section, { SectionHeading } from '../ui/Section'

export default function HowItWorks() {
  const [trackRef, trackInView] = useReveal({ threshold: 0.3 })

  return (
    <Section id="how-it-works" tone="surface">
      <SectionHeading
        eyebrow="How it works"
        eyebrowTone="emerald"
        title="Three steps from idea to event"
        description="No onboarding marathon. Create an account and you are planning the same day."
      />

      <div ref={trackRef} className="relative mt-16">
        {/* Connector draws itself in once the row is on screen. */}
        <motion.span
          aria-hidden="true"
          initial={{ scaleX: 0 }}
          animate={trackInView ? { scaleX: 1 } : { scaleX: 0 }}
          transition={{ duration: 0.8, ease: [0.16, 1, 0.3, 1], delay: 0.2 }}
          className="absolute left-[16.6%] right-[16.6%] top-8 hidden h-px origin-left bg-gradient-to-r from-navy-200 via-emerald-300 to-purple-300 lg:block"
        />

        <ol className="relative grid gap-12 lg:grid-cols-3 lg:gap-8">
          {STEPS.map((step, index) => (
            <Reveal key={step.title} delay={index * 0.12} as="li" className="text-center">
              <span className="relative z-10 mx-auto grid size-16 place-items-center rounded-2xl bg-navy-800 text-white shadow-[0_16px_32px_-18px_rgba(30,58,138,0.9)]">
                <Icon name={step.icon} className="size-7" />
                <span className="absolute -right-2 -top-2 grid size-7 place-items-center rounded-full bg-emerald-500 text-xs font-bold text-white ring-4 ring-surface">
                  {index + 1}
                </span>
              </span>

              <h3 className="mt-6 text-[1.1875rem] font-bold text-ink">{step.title}</h3>
              <p className="mx-auto mt-2.5 max-w-xs leading-relaxed text-muted">{step.description}</p>
            </Reveal>
          ))}
        </ol>
      </div>

      <Reveal delay={0.2} className="mt-14 flex justify-center">
        <Button to="/register" size="lg">
          Create your free account
          <Icon name="ArrowRight" className="size-[18px]" />
        </Button>
      </Reveal>
    </Section>
  )
}
