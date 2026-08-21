import { useEffect, useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import { TESTIMONIALS } from '../../lib/content'
import { cn } from '../../lib/cn'
import Icon from '../ui/Icon'
import Section, { SectionHeading } from '../ui/Section'

/** Falls back to initials on a navy chip if the portrait fails to load. */
function Avatar({ person, className }) {
  const [failed, setFailed] = useState(false)
  const initials = person.name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)

  if (failed || !person.image) {
    return (
      <span
        className={cn(
          'grid shrink-0 place-items-center rounded-full bg-navy-800 font-bold text-white',
          className,
        )}
      >
        {initials}
      </span>
    )
  }

  return (
    <img
      src={person.image}
      alt={person.name}
      onError={() => setFailed(true)}
      loading="lazy"
      className={cn('shrink-0 rounded-full object-cover', className)}
    />
  )
}

function Stars() {
  return (
    <div className="flex gap-1" aria-label="Rated 5 out of 5">
      {[0, 1, 2, 3, 4].map((index) => (
        <svg key={index} viewBox="0 0 20 20" className="size-4 fill-warning" aria-hidden="true">
          <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.78L10 14.77l-5.2 2.73.99-5.78-4.21-4.1 5.82-.85L10 1.5Z" />
        </svg>
      ))}
    </div>
  )
}

export default function Testimonials() {
  const [index, setIndex] = useState(0)
  const [paused, setPaused] = useState(false)

  useEffect(() => {
    if (paused) return

    const timer = setInterval(() => setIndex((current) => (current + 1) % TESTIMONIALS.length), 7000)

    return () => clearInterval(timer)
  }, [paused])

  const active = TESTIMONIALS[index]

  return (
    <Section id="testimonials" tone="canvas">
      <SectionHeading
        eyebrow="Testimonials"
        title="The people who run events, on OSEP"
        description="Planners, vendors and clients describing what changed after they moved their events across."
      />

      <div
        className="mx-auto mt-14 max-w-3xl"
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
      >
        <div className="relative min-h-[19rem] sm:min-h-[16rem]">
          <AnimatePresence mode="wait">
            <motion.figure
              key={active.name}
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -16 }}
              transition={{ duration: 0.3, ease: [0.16, 1, 0.3, 1] }}
              className="rounded-card border border-line/80 bg-surface p-8 shadow-card md:p-10"
            >
              <Stars />

              <blockquote className="mt-5 text-[1.1875rem] leading-relaxed text-ink text-pretty">
                “{active.quote}”
              </blockquote>

              <figcaption className="mt-7 flex items-center gap-4">
                <Avatar person={active} className="size-12 text-sm" />
                <span>
                  <span className="block font-bold text-ink">{active.name}</span>
                  <span className="block text-sm text-muted">{active.role}</span>
                </span>
              </figcaption>
            </motion.figure>
          </AnimatePresence>
        </div>

        <div className="mt-8 flex items-center justify-center gap-3">
          {TESTIMONIALS.map((person, personIndex) => (
            <button
              key={person.name}
              type="button"
              onClick={() => setIndex(personIndex)}
              aria-label={`Read the review from ${person.name}`}
              aria-current={personIndex === index}
              className={cn(
                'rounded-full transition-all duration-300',
                personIndex === index
                  ? 'ring-2 ring-navy-800 ring-offset-2 ring-offset-canvas'
                  : 'opacity-45 hover:opacity-90',
              )}
            >
              <Avatar person={person} className="size-10 text-xs" />
            </button>
          ))}
        </div>

        <div className="mt-6 flex items-center justify-center gap-2 text-sm text-muted">
          <Icon name="CheckCircle2" className="size-4 text-emerald-500" />
          Every review comes from a booking made on OSEP
        </div>
      </div>
    </Section>
  )
}
