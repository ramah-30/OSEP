import { forwardRef, useState } from 'react'
import { cn } from '../../lib/cn'
import { passwordStrength } from '../../lib/validation'
import Icon from './Icon'
import Input from './Input'

const LEVELS = [
  { label: 'Too weak', bar: 'bg-danger', text: 'text-danger' },
  { label: 'Weak', bar: 'bg-danger', text: 'text-danger' },
  { label: 'Fair', bar: 'bg-warning', text: 'text-warning' },
  { label: 'Good', bar: 'bg-emerald-400', text: 'text-emerald-600' },
  { label: 'Strong', bar: 'bg-emerald-500', text: 'text-emerald-600' },
]

const PasswordInput = forwardRef(function PasswordInput(
  { label = 'Password', showStrength = false, value = '', error, ...props },
  ref,
) {
  const [visible, setVisible] = useState(false)
  const score = passwordStrength(value)
  const level = LEVELS[score]

  return (
    <div className="w-full">
      <div className="relative">
        <Input
          ref={ref}
          label={label}
          icon="Lock"
          type={visible ? 'text' : 'password'}
          error={error}
          value={value}
          inputClassName="pr-14"
          {...props}
        />

        {/* The field is 56px tall and the button 36px, so 10px centres it. */}
        <button
          type="button"
          onClick={() => setVisible((current) => !current)}
          className={cn(
            'absolute right-3 top-[10px] z-10 grid size-9 place-items-center rounded-lg text-muted',
            'transition-colors duration-200 hover:bg-canvas hover:text-navy-700',
          )}
          aria-label={visible ? 'Hide password' : 'Show password'}
        >
          <Icon name={visible ? 'EyeOff' : 'Eye'} className="size-[18px]" />
        </button>
      </div>

      {showStrength && value.length > 0 && (
        <div className="mt-2.5">
          <div className="flex gap-1.5" aria-hidden="true">
            {[0, 1, 2, 3].map((index) => (
              <span
                key={index}
                className={cn(
                  'h-1 flex-1 rounded-full transition-colors duration-300',
                  index < score ? level.bar : 'bg-line',
                )}
              />
            ))}
          </div>
          <p className={cn('mt-1.5 text-sm font-medium', level.text)} aria-live="polite">
            {level.label}
            <span className="ml-1 font-normal text-muted">
              — 8+ characters with upper, lower, a number and a symbol.
            </span>
          </p>
        </div>
      )}
    </div>
  )
})

export default PasswordInput
