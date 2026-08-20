import Icon from './Icon'

/** Friendly placeholder for lists and panels that have nothing to show yet. */
export default function EmptyState({ icon = 'Sparkles', title, description, action, className }) {
  return (
    <div className={`grid place-items-center rounded-card border border-dashed border-line px-6 py-12 text-center ${className ?? ''}`}>
      <span className="grid size-12 place-items-center rounded-2xl bg-canvas text-navy-700">
        <Icon name={icon} className="size-6" />
      </span>
      <p className="mt-4 font-bold text-ink">{title}</p>
      {description && <p className="mt-1 max-w-sm text-sm text-muted">{description}</p>}
      {action && <div className="mt-5">{action}</div>}
    </div>
  )
}
