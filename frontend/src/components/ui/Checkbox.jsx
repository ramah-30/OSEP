import { forwardRef, useId } from 'react'
import { cn } from '../../lib/cn'
import Icon from './Icon'

const Checkbox = forwardRef(function Checkbox({ label, error, className, id, ...props }, ref) {
  const generatedId = useId()
  const checkboxId = id ?? generatedId

  return (
    <div className={cn('w-full', className)}>
      <div className="flex items-start gap-3">
        <span className="relative mt-0.5 grid size-5 shrink-0 place-items-center">
          <input
            ref={ref}
            id={checkboxId}
            type="checkbox"
            aria-invalid={error ? 'true' : undefined}
            className={cn(
              'peer size-5 cursor-pointer appearance-none rounded-md border bg-surface',
              'transition-colors duration-200',
              'checked:border-navy-800 checked:bg-navy-800',
              error ? 'border-danger' : 'border-line hover:border-navy-300',
            )}
            {...props}
          />
          <Icon
            name="Check"
            className="pointer-events-none absolute size-3.5 stroke-[3] text-white opacity-0 transition-opacity duration-200 peer-checked:opacity-100"
          />
        </span>

        <label htmlFor={checkboxId} className="cursor-pointer text-sm leading-relaxed text-muted">
          {label}
        </label>
      </div>

      {error && (
        <p className="mt-1.5 flex items-center gap-1.5 text-sm text-danger">
          <Icon name="TriangleAlert" className="size-3.5 shrink-0" />
          {error}
        </p>
      )}
    </div>
  )
})

export default Checkbox
