import { cn } from '../../lib/cn'

/**
 * 20px corners, a soft shadow and a minimal border. `hover` adds the lift used
 * across the feature and category grids.
 */
export default function Card({ as: Tag = 'div', hover = false, className, children, ...props }) {
  return (
    <Tag
      className={cn(
        'rounded-card border border-line/80 bg-surface shadow-card',
        hover &&
          'transition-[transform,box-shadow,border-color] duration-300 ease-out hover:-translate-y-1 hover:border-navy-100 hover:shadow-lift',
        className,
      )}
      {...props}
    >
      {children}
    </Tag>
  )
}
