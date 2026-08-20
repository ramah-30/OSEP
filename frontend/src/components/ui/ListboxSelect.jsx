import { forwardRef, useCallback, useEffect, useId, useLayoutEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { cn } from '../../lib/cn'
import Icon from './Icon'
import { normaliseOptions } from './selectOptions'

/**
 * A fully custom dropdown that renders its own option list instead of the
 * browser's native (OS-styled) popup. A visually-hidden native <select> is kept
 * in sync underneath so it still works with react-hook-form's register() and
 * with controlled value/onChange — the styled listbox just drives it.
 *
 * The popup renders in a body portal with fixed positioning so it is never
 * clipped by a modal or a scroll container.
 */

// React tracks a native <select> via its own value setter; to make a
// programmatic change trigger onChange we set through the prototype setter and
// dispatch a bubbling change event.
function setNativeValue(el, value) {
  const setter = Object.getOwnPropertyDescriptor(window.HTMLSelectElement.prototype, 'value')?.set
  setter?.call(el, value)
  el.dispatchEvent(new Event('change', { bubbles: true }))
}

const ListboxSelect = forwardRef(function ListboxSelect(
  {
    options,
    children,
    placeholder = 'Select…',
    error,
    icon,
    id,
    className,
    heightClass = 'h-12',
    innerLabel,
    disabled,
    value: controlledValue,
    defaultValue,
    onChange,
    ...props
  },
  ref,
) {
  const generatedId = useId()
  const selectId = id ?? generatedId
  const isControlled = controlledValue !== undefined
  const items = normaliseOptions(options, children)

  const selectRef = useRef(null)
  const triggerRef = useRef(null)
  const popupRef = useRef(null)

  const [uncontrolledValue, setUncontrolledValue] = useState(defaultValue ?? '')
  const currentValue = isControlled ? controlledValue : uncontrolledValue

  const [open, setOpen] = useState(false)
  const [activeIndex, setActiveIndex] = useState(-1)
  const [rect, setRect] = useState(null)

  const setRef = useCallback(
    (node) => {
      selectRef.current = node
      if (typeof ref === 'function') ref(node)
      else if (ref) ref.current = node
    },
    [ref],
  )

  // react-hook-form (and reset()) set the native select's value straight through
  // the ref without firing a change event, so mirror the DOM value each render to
  // keep the visible trigger in sync. Guarded by equality to avoid a render loop.
  useLayoutEffect(() => {
    if (isControlled || !selectRef.current) return
    const domValue = selectRef.current.value
    setUncontrolledValue((prev) => (prev === domValue ? prev : domValue))
  })

  const selected = items.find((o) => !o.group && String(o.value) === String(currentValue ?? ''))

  const updatePosition = useCallback(() => {
    if (triggerRef.current) setRect(triggerRef.current.getBoundingClientRect())
  }, [])

  useLayoutEffect(() => {
    if (!open) return
    updatePosition()
    const handler = () => updatePosition()
    window.addEventListener('scroll', handler, true)
    window.addEventListener('resize', handler)
    return () => {
      window.removeEventListener('scroll', handler, true)
      window.removeEventListener('resize', handler)
    }
  }, [open, updatePosition])

  useEffect(() => {
    if (!open) return
    const onDocClick = (e) => {
      if (
        !triggerRef.current?.contains(e.target) &&
        !popupRef.current?.contains(e.target)
      ) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', onDocClick)
    return () => document.removeEventListener('mousedown', onDocClick)
  }, [open])

  const openMenu = () => {
    if (disabled) return
    const idx = items.findIndex((o) => !o.group && String(o.value) === String(currentValue ?? ''))
    setActiveIndex(idx >= 0 ? idx : items.findIndex((o) => !o.group && !o.disabled))
    setOpen(true)
  }

  const commit = (option) => {
    if (!option || option.disabled || option.group) return
    if (selectRef.current) setNativeValue(selectRef.current, option.value)
    if (!isControlled) setUncontrolledValue(option.value)
    setOpen(false)
    triggerRef.current?.focus()
  }

  const moveActive = (dir) => {
    setActiveIndex((prev) => {
      let next = prev
      for (let i = 0; i < items.length; i++) {
        next += dir
        if (next < 0) next = items.length - 1
        if (next >= items.length) next = 0
        if (!items[next].group && !items[next].disabled) return next
      }
      return prev
    })
  }

  const onTriggerKeyDown = (e) => {
    if (disabled) return
    if (!open) {
      if (['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(e.key)) {
        e.preventDefault()
        openMenu()
      }
      return
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault()
      moveActive(1)
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      moveActive(-1)
    } else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault()
      commit(items[activeIndex])
    } else if (e.key === 'Escape') {
      e.preventDefault()
      setOpen(false)
    } else if (e.key === 'Tab') {
      setOpen(false)
    }
  }

  return (
    <div className={cn('w-full', className)}>
      {/* Source of truth: hidden but focusable-free native select. */}
      <select
        ref={setRef}
        id={selectId}
        tabIndex={-1}
        aria-hidden="true"
        disabled={disabled}
        className="sr-only"
        {...(isControlled ? { value: controlledValue } : { defaultValue: defaultValue ?? '' })}
        onChange={(e) => {
          if (!isControlled) setUncontrolledValue(e.target.value)
          onChange?.(e)
        }}
        {...props}
      >
        {!items.some((o) => !o.group && String(o.value) === '') && (
          <option value="" disabled>
            {placeholder}
          </option>
        )}
        {items.map((o, i) =>
          o.group ? (
            <optgroup key={`g-${i}`} label={o.group} />
          ) : (
            <option key={`${o.value}-${i}`} value={o.value} disabled={o.disabled}>
              {o.label}
            </option>
          ),
        )}
      </select>

      <button
        type="button"
        ref={triggerRef}
        disabled={disabled}
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-invalid={error ? 'true' : undefined}
        onClick={() => (open ? setOpen(false) : openMenu())}
        onKeyDown={onTriggerKeyDown}
        className={cn(
          'relative flex w-full items-center rounded-btn border bg-surface text-left text-[0.95rem] text-ink',
          'transition-[border-color,box-shadow] duration-200 outline-none',
          heightClass,
          icon ? 'pl-11 pr-11' : 'pl-4 pr-11',
          innerLabel ? 'pb-1.5 pt-5' : '',
          disabled && 'cursor-not-allowed opacity-60',
          error
            ? 'border-danger focus:border-danger focus:shadow-[0_0_0_3px_rgba(239,68,68,0.14)]'
            : 'border-line hover:border-navy-200 focus:border-navy-600 focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]',
          open && !error && 'border-navy-600 shadow-[0_0_0_3px_rgba(41,71,200,0.12)]',
        )}
      >
        {icon && (
          <Icon
            name={icon}
            className={cn(
              'pointer-events-none absolute left-4 top-1/2 size-[18px] -translate-y-1/2',
              error ? 'text-danger' : 'text-muted',
            )}
          />
        )}
        {innerLabel && (
          <span
            className={cn(
              'pointer-events-none absolute top-1.5 text-xs font-medium',
              icon ? 'left-11' : 'left-4',
              error ? 'text-danger' : 'text-muted',
            )}
          >
            {innerLabel}
          </span>
        )}
        <span className={cn('block truncate', !selected && 'text-muted')}>
          {selected ? selected.label : placeholder}
        </span>
        <Icon
          name="ChevronDown"
          className={cn(
            'pointer-events-none absolute right-4 top-1/2 size-[18px] -translate-y-1/2 text-muted transition-transform duration-200',
            open && 'rotate-180',
          )}
        />
      </button>

      {open &&
        rect &&
        createPortal(
          <ul
            ref={popupRef}
            role="listbox"
            className="fixed z-[1000] max-h-64 overflow-auto rounded-card border border-line bg-surface p-1.5 shadow-xl ring-1 ring-black/5 focus:outline-none"
            style={{
              top: rect.bottom + 6,
              left: rect.left,
              width: rect.width,
            }}
          >
            {items.map((o, i) => {
              if (o.group) {
                return (
                  <li
                    key={`g-${i}`}
                    className="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wide text-muted"
                  >
                    {o.group}
                  </li>
                )
              }
              const isSelected = String(o.value) === String(currentValue ?? '')
              const isActive = i === activeIndex
              return (
                <li
                  key={`${o.value}-${i}`}
                  role="option"
                  aria-selected={isSelected}
                  aria-disabled={o.disabled || undefined}
                  onMouseEnter={() => !o.disabled && setActiveIndex(i)}
                  onMouseDown={(e) => e.preventDefault()}
                  onClick={() => commit(o)}
                  className={cn(
                    'flex cursor-pointer items-center justify-between gap-2 rounded-btn px-3 py-2 text-[0.9rem]',
                    o.disabled && 'cursor-not-allowed text-muted opacity-60',
                    !o.disabled && isActive && 'bg-navy-50 text-navy-700',
                    !o.disabled && !isActive && 'text-ink',
                    isSelected && 'font-semibold',
                  )}
                >
                  <span className="truncate">{o.label}</span>
                  {isSelected && <Icon name="Check" className="size-4 shrink-0 text-navy-600" />}
                </li>
              )
            })}
          </ul>,
          document.body,
        )}
    </div>
  )
})

export default ListboxSelect
