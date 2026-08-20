import { Link } from 'react-router-dom'
import { cn } from '../../lib/cn'
import mark from '../../assets/osep-mark.png'

/**
 * The supplied artwork is a square lockup with a lot of surrounding whitespace,
 * so `osep-mark.png` is a trimmed crop of the circular mark alone (see also
 * `osep-lockup.png`). The mark is navy, so it sits on a white tile to stay
 * legible over the hero photography as well as on light surfaces.
 */
export default function Logo({ variant = 'dark', className, to = '/', showTagline = false }) {
  const content = (
    <span className={cn('flex items-center gap-2.5', className)}>
      <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-white p-1.5 ring-1 ring-line/60">
        <img src={mark} alt="" className="size-full object-contain" />
      </span>

      <span className="flex flex-col leading-none">
        <span
          className={cn(
            'text-[1.35rem] font-extrabold tracking-tight',
            variant === 'light' ? 'text-white' : 'text-navy-800',
          )}
        >
          OSEP
        </span>
        {showTagline && (
          <span
            className={cn(
              'mt-1.5 text-[0.7rem] font-medium tracking-wide',
              variant === 'light' ? 'text-white/70' : 'text-muted',
            )}
          >
            Event Planning Platform
          </span>
        )}
      </span>
    </span>
  )

  if (!to) return content

  return (
    <Link to={to} aria-label="OSEP home" className="rounded-xl">
      {content}
    </Link>
  )
}
