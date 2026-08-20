import { Link } from 'react-router-dom'
import { cn } from '../../lib/cn'
import Spinner from './Spinner'

const VARIANTS = {
  primary:
    'bg-navy-800 text-white shadow-[0_10px_24px_-14px_rgba(30,58,138,0.9)] hover:bg-navy-900 active:bg-navy-950',
  secondary:
    'bg-surface text-navy-800 border border-navy-800/25 hover:border-navy-800/60 hover:bg-navy-50',
  ghost: 'bg-transparent text-ink hover:bg-ink/5',
  light: 'bg-white/12 text-white border border-white/25 backdrop-blur-sm hover:bg-white/20',
  emerald: 'bg-emerald-500 text-white hover:bg-emerald-600',
  danger: 'bg-danger-soft text-danger border border-danger/25 hover:bg-danger hover:text-white',
}

const SIZES = {
  sm: 'h-10 px-4 text-sm gap-1.5',
  md: 'h-12 px-6 text-[0.95rem] gap-2',
  lg: 'h-14 px-8 text-base gap-2.5',
}

/**
 * One button for the whole product: 12px corners, generous padding, a subtle
 * lift on hover and a 200ms ease. Renders as <button>, <Link> or <a> depending
 * on what it is asked to do.
 */
export default function Button({
  variant = 'primary',
  size = 'md',
  to,
  href,
  loading = false,
  disabled = false,
  fullWidth = false,
  className,
  children,
  ...props
}) {
  const isDisabled = disabled || loading

  const classes = cn(
    'inline-flex items-center justify-center rounded-btn font-semibold',
    'transition-[transform,background-color,border-color,box-shadow,color] duration-200 ease-out',
    'hover:-translate-y-0.5 active:translate-y-0',
    'disabled:pointer-events-none disabled:opacity-55',
    VARIANTS[variant],
    SIZES[size],
    fullWidth && 'w-full',
    className,
  )

  const content = (
    <>
      {loading && <Spinner className="size-4" />}
      {children}
    </>
  )

  if (to && !isDisabled) {
    return (
      <Link to={to} className={classes} {...props}>
        {content}
      </Link>
    )
  }

  if (href && !isDisabled) {
    return (
      <a href={href} className={classes} {...props}>
        {content}
      </a>
    )
  }

  return (
    <button type="button" className={classes} disabled={isDisabled} {...props}>
      {content}
    </button>
  )
}
