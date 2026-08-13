import { forwardRef, useId } from 'react'
import { cn } from '../../lib/cn'
import Icon from './Icon'
import ListboxSelect from './ListboxSelect'
import { normaliseOptions } from './selectOptions'

/**
 * A plain top-labelled text field for the dashboard forms, where the floating
 * label of the auth <Input> is more than we need. Works with react-hook-form's
 * register() (ref + name/onChange/onBlur are spread through).
 */
export const Field = forwardRef(function Field(
  { label, error, hint, className, id, ...props },
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
      <input
        ref={ref}
        id={fieldId}
        aria-invalid={error ? 'true' : undefined}
        className={cn(
          'h-12 w-full rounded-btn border bg-surface px-4 text-[0.95rem] text-ink',
          'transition-[border-color,box-shadow] duration-200 outline-none',
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

/** Matching select for the dashboard forms — a fully custom styled dropdown. */
export const SelectField = forwardRef(function SelectField(
  { label, error, hint, options = [], placeholder = 'Select…', className, id, children, ...props },
  ref,
) {
  const generatedId = useId()
  const fieldId = id ?? generatedId
  const items = normaliseOptions(options, children)

  return (
    <div className={cn('w-full', className)}>
      {label && (
        <label htmlFor={fieldId} className="mb-1.5 block text-sm font-semibold text-ink">
          {label}
        </label>
      )}
      <ListboxSelect
        ref={ref}
        id={fieldId}
        options={items}
        placeholder={placeholder}
        error={error}
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
