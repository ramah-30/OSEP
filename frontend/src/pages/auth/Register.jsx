import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { AnimatePresence, motion } from 'framer-motion'
import AuthLayout from '../../components/layout/AuthLayout'
import Divider from '../../components/auth/Divider'
import GoogleButton from '../../components/auth/GoogleButton'
import Alert from '../../components/ui/Alert'
import Button from '../../components/ui/Button'
import Checkbox from '../../components/ui/Checkbox'
import Icon from '../../components/ui/Icon'
import Input from '../../components/ui/Input'
import PasswordInput from '../../components/ui/PasswordInput'
import Select from '../../components/ui/Select'
import { useAuth } from '../../context/AuthContext'
import { applyServerErrors, parseApiError } from '../../lib/api'
import { cn } from '../../lib/cn'
import { COUNTRIES } from '../../lib/countries'
import { USER_CATEGORIES } from '../../lib/content'
import { ACCOUNT_TYPES, registerSchema } from '../../lib/validation'

const ACCENTS = {
  navy: 'text-navy-800 bg-navy-50',
  emerald: 'text-emerald-600 bg-emerald-50',
  purple: 'text-purple-600 bg-purple-50',
}

export default function Register() {
  const { register: registerUser } = useAuth()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()

  // Deep links from the landing page ("/register?type=vendor") skip the picker.
  const presetType = searchParams.get('type')
  const initialType = ACCOUNT_TYPES.includes(presetType) ? presetType : null

  const [accountType, setAccountType] = useState(initialType)
  const [step, setStep] = useState(initialType ? 2 : 1)
  const [formError, setFormError] = useState(null)

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    watch,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(registerSchema),
    mode: 'onBlur',
    defaultValues: {
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      password: '',
      password_confirmation: '',
      account_type: initialType ?? '',
      country: '',
      terms: false,
    },
  })

  const chooseType = (key) => {
    setAccountType(key)
    setValue('account_type', key, { shouldValidate: true })
    setStep(2)
  }

  const onSubmit = async (values) => {
    setFormError(null)

    try {
      const user = await registerUser(values)
      navigate(`/verify-email?email=${encodeURIComponent(user.email)}`, { replace: true })
    } catch (error) {
      const parsed = parseApiError(error)
      applyServerErrors(parsed.errors, setError)

      if (!Object.keys(parsed.errors ?? {}).length) {
        setFormError(parsed.message)
      }
    }
  }

  const selected = USER_CATEGORIES.find((category) => category.key === accountType)

  return (
    <AuthLayout
      title={step === 1 ? 'Create your account' : `Set up your ${selected?.title.toLowerCase()} account`}
      subtitle={
        step === 1
          ? 'First, tell us how you plan to use OSEP. This shapes your workspace.'
          : 'A minute to fill in, and your workspace is ready.'
      }
      footer={
        <>
          Already have an account?{' '}
          <Link to="/login" className="font-semibold text-navy-800 underline underline-offset-4">
            Sign in
          </Link>
        </>
      }
    >
      <AnimatePresence mode="wait">
        {step === 1 ? (
          <motion.div
            key="step-1"
            initial={{ opacity: 0, x: -16 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -16 }}
            transition={{ duration: 0.24, ease: [0.16, 1, 0.3, 1] }}
            className="space-y-3"
          >
            {USER_CATEGORIES.map((category) => (
              <button
                key={category.key}
                type="button"
                onClick={() => chooseType(category.key)}
                className="group flex w-full items-center gap-4 rounded-card border border-line bg-surface p-5 text-left transition-[transform,border-color,box-shadow] duration-200 hover:-translate-y-0.5 hover:border-navy-300 hover:shadow-card"
              >
                <span className={cn('grid size-12 shrink-0 place-items-center rounded-xl', ACCENTS[category.accent])}>
                  <Icon name={category.icon} className="size-6" />
                </span>

                <span className="flex-1">
                  <span className="block font-bold text-ink">{category.title}</span>
                  <span className="mt-0.5 block text-sm leading-relaxed text-muted">
                    {category.tagline}
                  </span>
                </span>

                <Icon
                  name="ArrowRight"
                  className="size-5 shrink-0 text-muted transition-transform duration-200 group-hover:translate-x-1 group-hover:text-navy-800"
                />
              </button>
            ))}

            <div className="pt-3">
              <Divider>or</Divider>
              <div className="pt-5">
                <GoogleButton label="Sign up with Google" />
                <p className="mt-3 text-center text-sm text-muted">
                  Google sign-ups start as a client account and can be changed later.
                </p>
              </div>
            </div>
          </motion.div>
        ) : (
          <motion.form
            key="step-2"
            initial={{ opacity: 0, x: 16 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: 16 }}
            transition={{ duration: 0.24, ease: [0.16, 1, 0.3, 1] }}
            onSubmit={handleSubmit(onSubmit)}
            noValidate
            className="space-y-5"
          >
            <div className="flex items-center justify-between rounded-btn bg-canvas p-3 pl-4">
              <span className="flex items-center gap-2.5 text-sm">
                <Icon name={selected?.icon} className="size-[18px] text-navy-800" />
                <span className="font-semibold text-ink">{selected?.title} account</span>
              </span>
              <button
                type="button"
                onClick={() => setStep(1)}
                className="rounded-lg px-3 py-1.5 text-sm font-semibold text-navy-800 transition-colors duration-200 hover:bg-navy-50"
              >
                Change
              </button>
            </div>

            {formError && <Alert tone="error">{formError}</Alert>}

            <input type="hidden" {...register('account_type')} />

            <div className="grid gap-5 sm:grid-cols-2">
              <Input
                label="First name"
                icon="User"
                autoComplete="given-name"
                error={errors.first_name?.message}
                {...register('first_name')}
              />
              <Input
                label="Last name"
                icon="User"
                autoComplete="family-name"
                error={errors.last_name?.message}
                {...register('last_name')}
              />
            </div>

            <Input
              label="Email address"
              icon="Mail"
              type="email"
              autoComplete="email"
              error={errors.email?.message}
              {...register('email')}
            />

            <div className="grid gap-5 sm:grid-cols-2">
              <Input
                label="Phone number"
                icon="Phone"
                type="tel"
                autoComplete="tel"
                error={errors.phone?.message}
                {...register('phone')}
              />
              <Select
                label="Country"
                icon="Globe2"
                placeholder="Select"
                options={COUNTRIES}
                error={errors.country?.message}
                {...register('country')}
              />
            </div>

            <PasswordInput
              label="Password"
              autoComplete="new-password"
              showStrength
              value={watch('password')}
              error={errors.password?.message}
              {...register('password')}
            />

            <PasswordInput
              label="Confirm password"
              autoComplete="new-password"
              value={watch('password_confirmation')}
              error={errors.password_confirmation?.message}
              {...register('password_confirmation')}
            />

            <Checkbox
              error={errors.terms?.message}
              label={
                <>
                  I agree to the{' '}
                  <Link to="/terms" className="font-semibold text-navy-800 underline underline-offset-4">
                    Terms of Service
                  </Link>{' '}
                  and{' '}
                  <Link to="/privacy" className="font-semibold text-navy-800 underline underline-offset-4">
                    Privacy Policy
                  </Link>
                  .
                </>
              }
              {...register('terms')}
            />

            <Button type="submit" size="lg" fullWidth loading={isSubmitting}>
              {isSubmitting ? 'Creating your account…' : 'Create account'}
              {!isSubmitting && <Icon name="ArrowRight" className="size-[18px]" />}
            </Button>
          </motion.form>
        )}
      </AnimatePresence>
    </AuthLayout>
  )
}
