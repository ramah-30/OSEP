import { forwardRef, useId } from 'react'
import { cn } from '../../lib/cn'
import Icon from './Icon'

/** Multi-line field matching Input's styling, with a static top label. */
const Textarea = forwardRef(function Textarea(
  { label, error, hint, rows = 4, className, id, ...props },
  ref,
) {
  const generatedId = useId()
  const fieldId = id ?? generatedId

  return (
    <div className={cn('w-full', className)}>
      {label && (
        <label htmlFor={fieldId} className="mb-1.5 block text-sm font-semibold text-ink">
          {label}
        </label>
      )}
      <textarea
        ref={ref}
        id={fieldId}
        rows={rows}
        aria-invalid={error ? 'true' : undefined}
        className={cn(
          'w-full rounded-btn border bg-surface px-4 py-3 text-[0.95rem] text-ink',
          'transition-[border-color,box-shadow] duration-200 outline-none resize-y',
          error
            ? 'border-danger focus:border-danger focus:shadow-[0_0_0_3px_rgba(239,68,68,0.14)]'
            : 'border-line hover:border-navy-200 focus:border-navy-600 focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]',
        )}
        {...props}
      />
      {error ? (
        <p className="mt-1.5 flex items-center gap-1.5 text-sm text-danger">
          <Icon name="TriangleAlert" className="size-3.5 shrink-0" />
          {error}
        </p>
      ) : hint ? (
        <p className="mt-1.5 text-sm text-muted">{hint}</p>
      ) : null}
    </div>
  )
})

export default Textarea
