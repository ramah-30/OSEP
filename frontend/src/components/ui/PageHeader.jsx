/** Consistent title block at the top of every dashboard page. */
export default function PageHeader({ title, description, actions }) {
  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 className="text-h3 font-extrabold tracking-tight text-ink">{title}</h1>
        {description && <p className="mt-1.5 max-w-2xl text-muted">{description}</p>}
      </div>
      {actions && <div className="flex flex-wrap items-center gap-3">{actions}</div>}
    </div>
  )
}
