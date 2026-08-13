import { forwardRef, useId } from 'react'
import { cn } from '../../lib/cn'
import Icon from './Icon'

/**
 * Rounded field with the icon inside, a label that floats out of the way once
 * there is content, and inline validation state. The floating label is done
 * with `peer` + `placeholder-shown` so it needs no JS and stays correct when
 * the browser autofills.
 */
const Input = forwardRef(function Input(
  { label, icon, type = 'text', error, hint, valid = false, className, inputClassName, id, ...props },
  ref,
) {
  const generatedId = useId()
  const inputId = id ?? generatedId
  const describedBy = error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined

  return (
    <div className={cn('w-full', className)}>
      <div className="relative">
        {icon && (
          <Icon
            name={icon}
            className={cn(
              'pointer-events-none absolute left-4 top-1/2 z-10 size-[18px] -translate-y-1/2 transition-colors duration-200',
              error ? 'text-danger' : 'text-muted',
            )}
          />
        )}

        <input
          ref={ref}
          id={inputId}
          type={type}
          placeholder=" "
          aria-invalid={error ? 'true' : undefined}
          aria-describedby={describedBy}
          className={cn(
            'peer h-14 w-full rounded-btn border bg-surface pb-2 pt-6 text-[0.95rem] text-ink',
            'transition-[border-color,box-shadow] duration-200 outline-none',
            'placeholder:text-transparent',
            // Chrome paints autofilled fields blue and ignores background-color;
            // an inset shadow is the only way to keep the surface white.
            'autofill:shadow-[inset_0_0_0_1000px_var(--color-surface)] autofill:[-webkit-text-fill-color:var(--color-ink)]',
            icon ? 'pl-11 pr-11' : 'px-4',
            error
              ? 'border-danger focus:border-danger focus:shadow-[0_0_0_3px_rgba(239,68,68,0.14)]'
              : valid
                ? 'border-emerald-400 focus:border-emerald-500 focus:shadow-[0_0_0_3px_rgba(16,185,129,0.14)]'
                : 'border-line hover:border-navy-200 focus:border-navy-600 focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]',
            inputClassName,
          )}
          {...props}
        />

        <label
          htmlFor={inputId}
          className={cn(
            'pointer-events-none absolute top-2 text-xs font-medium transition-all duration-200',
            icon ? 'left-11' : 'left-4',
            'peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-[0.95rem] peer-placeholder-shown:font-normal',
            // Chrome's autofill *preview* keeps :placeholder-shown true while
            // already painting a value, so the label must lift for it too.
            'peer-autofill:top-2 peer-autofill:translate-y-0 peer-autofill:text-xs peer-autofill:font-medium',
            'peer-focus:top-2 peer-focus:translate-y-0 peer-focus:text-xs peer-focus:font-medium',
            error
              ? 'text-danger'
              : 'text-muted peer-focus:text-navy-700',
          )}
        >
          {label}
        </label>

        {valid && !error && (
          <Icon
            name="CheckCircle2"
            className="pointer-events-none absolute right-4 top-1/2 size-[18px] -translate-y-1/2 text-emerald-500"
          />
        )}
      </div>

      {error ? (
        <p id={`${inputId}-error`} className="mt-1.5 flex items-center gap-1.5 text-sm text-danger">
          <Icon name="TriangleAlert" className="size-3.5 shrink-0" />
          {error}
        </p>
      ) : hint ? (
        <p id={`${inputId}-hint`} className="mt-1.5 text-sm text-muted">
          {hint}
        </p>
      ) : null}
    </div>
  )
})

export default Input
