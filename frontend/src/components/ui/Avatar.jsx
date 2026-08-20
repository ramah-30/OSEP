import { cn } from '../../lib/cn'

const SIZES = {
  sm: 'size-8 text-xs',
  md: 'size-11 text-sm',
  lg: 'size-16 text-lg',
  xl: 'size-24 text-2xl',
}

/** User/business avatar: shows the image when present, initials otherwise. */
export default function Avatar({ src, name = '', initials, size = 'md', className }) {
  const fallback =
    initials ??
    name
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase())
      .join('')

  return (
    <span
      className={cn(
        'grid shrink-0 place-items-center overflow-hidden rounded-full bg-navy-800 font-bold text-white',
        SIZES[size],
        className,
      )}
    >
      {src ? (
        <img src={src} alt={name} className="size-full object-cover" />
      ) : (
        <span>{fallback || '·'}</span>
      )}
    </span>
  )
}
