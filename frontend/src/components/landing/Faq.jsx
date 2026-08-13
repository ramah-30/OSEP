import { FAQS } from '../../lib/content'
import Accordion from '../ui/Accordion'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import Reveal from '../ui/Reveal'
import Section, { SectionHeading } from '../ui/Section'

export default function Faq() {
  return (
    <Section id="faq" tone="surface">
      <SectionHeading
        eyebrow="FAQ"
        title="Questions, answered"
        description="If something is still unclear, the contact form below reaches a real person."
      />

      <Reveal delay={0.06} className="mx-auto mt-12 max-w-3xl">
        <Accordion items={FAQS} />
      </Reveal>

      <Reveal delay={0.12} className="mx-auto mt-10 flex max-w-3xl flex-col items-center gap-4 rounded-card bg-canvas p-8 text-center">
        <span className="grid size-12 place-items-center rounded-xl bg-navy-50 text-navy-800">
          <Icon name="Mail" className="size-6" />
        </span>
        <p className="text-[1.0625rem] font-semibold text-ink">Still deciding?</p>
        <p className="max-w-md text-muted">
          Tell us about the event you are planning and we will tell you honestly whether OSEP is
          the right fit.
        </p>
        <Button href="#contact" variant="secondary">
          Talk to us
          <Icon name="ArrowRight" className="size-4" />
        </Button>
      </Reveal>
    </Section>
  )
}
