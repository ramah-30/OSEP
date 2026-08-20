import { useState } from 'react'
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import AuthLayout from '../../components/layout/AuthLayout'
import Alert from '../../components/ui/Alert'
import Button from '../../components/ui/Button'
import Checkbox from '../../components/ui/Checkbox'
import Icon from '../../components/ui/Icon'
import Input from '../../components/ui/Input'
import PasswordInput from '../../components/ui/PasswordInput'
import { useAuth } from '../../context/AuthContext'
import { applyServerErrors, parseApiError } from '../../lib/api'
import { loginSchema } from '../../lib/validation'

const NOTICES = {
  'password-reset': { tone: 'success', text: 'Password updated. Sign in with your new password.' },
  verified: { tone: 'success', text: 'Email confirmed. Your account is active — sign in to continue.' },
  'session-expired': { tone: 'warning', text: 'Your session expired. Please sign in again.' },
}

export default function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [searchParams] = useSearchParams()
  const [formError, setFormError] = useState(null)

  const notice = NOTICES[searchParams.get('notice')]

  const {
    register,
    handleSubmit,
    setError,
    watch,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(loginSchema),
    mode: 'onBlur',
    defaultValues: { email: '', password: '', remember: false },
  })

  const onSubmit = async (values) => {
    setFormError(null)

    try {
      const user = await login(values)

      // Unconfirmed accounts get the reminder screen rather than a dashboard
      // they cannot use yet.
      if (!user.email_verified) {
        navigate(`/verify-email?email=${encodeURIComponent(user.email)}`, { replace: true })
        return
      }

      const destination = location.state?.from ?? user.dashboard_path
      navigate(destination, { replace: true })
    } catch (error) {
      const parsed = parseApiError(error)
      applyServerErrors(parsed.errors, setError)

      if (!Object.keys(parsed.errors ?? {}).length) {
        setFormError(parsed.message)
      }
    }
  }

  return (
    <AuthLayout
      title="Welcome back"
      subtitle="Sign in to pick up where your events left off."
      footer={
        <>
          New to OSEP?{' '}
          <Link to="/register" className="font-semibold text-navy-800 underline underline-offset-4">
            Create an account
          </Link>
        </>
      }
    >
      <div className="space-y-6">
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          {notice && <Alert tone={notice.tone}>{notice.text}</Alert>}
          {formError && <Alert tone="error">{formError}</Alert>}

          <Input
            label="Email address"
            icon="Mail"
            type="email"
            autoComplete="email"
            error={errors.email?.message}
            {...register('email')}
          />

          <PasswordInput
            label="Password"
            autoComplete="current-password"
            value={watch('password')}
            error={errors.password?.message}
            {...register('password')}
          />

          <div className="flex items-center justify-between gap-4">
            <Checkbox label="Remember me for 30 days" {...register('remember')} />
            <Link
              to="/forgot-password"
              className="shrink-0 text-sm font-semibold text-navy-800 underline-offset-4 hover:underline"
            >
              Forgot password?
            </Link>
          </div>

          <Button type="submit" size="lg" fullWidth loading={isSubmitting}>
            {isSubmitting ? 'Signing in…' : 'Sign in'}
            {!isSubmitting && <Icon name="ArrowRight" className="size-[18px]" />}
          </Button>
        </form>
      </div>
    </AuthLayout>
  )
}
