import { forwardRef, useId } from 'react'
import { cn } from '../../lib/cn'
import Icon from './Icon'
import ListboxSelect from './ListboxSelect'
import { normaliseOptions } from './selectOptions'

/**
 * Labelled select with the label tucked inside the control. Renders a fully
 * custom dropdown list (see ListboxSelect) rather than the browser's default.
 */
const Select = forwardRef(function Select(
  { label, icon, options = [], error, hint, placeholder = 'Select…', className, id, children, ...props },
  ref,
) {
  const generatedId = useId()
  const selectId = id ?? generatedId
  const items = normaliseOptions(options, children)

  return (
    <div className={cn('w-full', className)}>
      <ListboxSelect
        ref={ref}
        id={selectId}
        options={items}
        placeholder={placeholder}
        error={error}
        icon={icon}
        innerLabel={label}
        heightClass="h-14"
        {...props}
      />

      {error ? (
        <p id={`${selectId}-error`} className="mt-1.5 flex items-center gap-1.5 text-sm text-danger">
          <Icon name="TriangleAlert" className="size-3.5 shrink-0" />
          {error}
        </p>
      ) : hint ? (
        <p id={`${selectId}-hint`} className="mt-1.5 text-sm text-muted">
          {hint}
        </p>
      ) : null}
    </div>
  )
})

export default Select
