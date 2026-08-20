import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import AuthLayout from '../../components/layout/AuthLayout'
import Alert from '../../components/ui/Alert'
import Button from '../../components/ui/Button'
import Icon from '../../components/ui/Icon'
import PasswordInput from '../../components/ui/PasswordInput'
import { api, applyServerErrors, parseApiError } from '../../lib/api'
import { resetPasswordSchema } from '../../lib/validation'

export default function ResetPassword() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const [formError, setFormError] = useState(null)

  const token = searchParams.get('token')
  const email = searchParams.get('email')

  const {
    register,
    handleSubmit,
    setError,
    watch,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(resetPasswordSchema),
    mode: 'onBlur',
    defaultValues: { password: '', password_confirmation: '' },
  })

  // A link without both halves cannot be honoured, so say so rather than
  // letting the user type a password that will be rejected.
  if (!token || !email) {
    return (
      <AuthLayout title="This link is incomplete" subtitle="The reset link is missing information.">
        <div className="space-y-6">
          <Alert tone="error" title="Cannot reset from this link">
            Open the most recent link from your email, or request a fresh one.
          </Alert>
          <Button to="/forgot-password" size="lg" fullWidth>
            Request a new link
          </Button>
        </div>
      </AuthLayout>
    )
  }

  const onSubmit = async (values) => {
    setFormError(null)

    try {
      await api.post('/auth/reset-password', { ...values, token, email })
      navigate('/login?notice=password-reset', { replace: true })
    } catch (error) {
      const parsed = parseApiError(error)
      applyServerErrors(parsed.errors, setError)
      setFormError(parsed.message)
    }
  }

  return (
    <AuthLayout
      title="Choose a new password"
      subtitle={`Setting a new password for ${email}.`}
      footer={
        <>
          Changed your mind?{' '}
          <Link to="/login" className="font-semibold text-navy-800 underline underline-offset-4">
            Back to sign in
          </Link>
        </>
      }
    >
      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
        {formError && <Alert tone="error">{formError}</Alert>}

        <PasswordInput
          label="New password"
          autoComplete="new-password"
          showStrength
          value={watch('password')}
          error={errors.password?.message}
          {...register('password')}
        />

        <PasswordInput
          label="Confirm new password"
          autoComplete="new-password"
          value={watch('password_confirmation')}
          error={errors.password_confirmation?.message}
          {...register('password_confirmation')}
        />

        <Alert tone="info">
          Setting a new password signs you out everywhere else — anyone using the old one loses
          access immediately.
        </Alert>

        <Button type="submit" size="lg" fullWidth loading={isSubmitting}>
          {isSubmitting ? 'Updating…' : 'Update password'}
          {!isSubmitting && <Icon name="ArrowRight" className="size-[18px]" />}
        </Button>
      </form>
    </AuthLayout>
  )
}
