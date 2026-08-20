import { cn } from '../../lib/cn'
import Reveal from './Reveal'

/** Small navy/emerald/purple pill that opens most sections. */
export function Eyebrow({ tone = 'navy', children, className }) {
  const tones = {
    navy: 'bg-navy-50 text-navy-800 ring-navy-100',
    emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    purple: 'bg-purple-50 text-purple-700 ring-purple-100',
    light: 'bg-white/10 text-white ring-white/20',
  }

  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 ring-inset',
        tones[tone],
        className,
      )}
    >
      {children}
    </span>
  )
}

export function SectionHeading({ eyebrow, eyebrowTone = 'navy', title, description, align = 'center', className }) {
  return (
    <Reveal
      className={cn(
        'flex flex-col gap-4',
        align === 'center' ? 'items-center text-center' : 'items-start text-left',
        className,
      )}
    >
      {eyebrow && <Eyebrow tone={eyebrowTone}>{eyebrow}</Eyebrow>}
      <h2 className="max-w-3xl text-h2 font-extrabold text-ink text-balance">{title}</h2>
      {description && (
        <p className="max-w-2xl text-lead text-muted text-pretty">{description}</p>
      )}
    </Reveal>
  )
}

export default function Section({ id, className, tone = 'canvas', children }) {
  const tones = {
    canvas: 'bg-canvas',
    surface: 'bg-surface',
    navy: 'bg-navy-950 text-white',
  }

  return (
    <section id={id} className={cn('py-20 md:py-28', tones[tone], className)}>
      <div className="container-page">{children}</div>
    </section>
  )
}
