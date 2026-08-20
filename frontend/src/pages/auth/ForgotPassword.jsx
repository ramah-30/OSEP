import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import AuthLayout from '../../components/layout/AuthLayout'
import Alert from '../../components/ui/Alert'
import Button from '../../components/ui/Button'
import Icon from '../../components/ui/Icon'
import Input from '../../components/ui/Input'
import { api, applyServerErrors, parseApiError } from '../../lib/api'
import { forgotPasswordSchema } from '../../lib/validation'

export default function ForgotPassword() {
  const [sentTo, setSentTo] = useState(null)
  const [formError, setFormError] = useState(null)

  const {
    register,
    handleSubmit,
    setError,
    getValues,
    formState: { errors, isSubmitting },
  } = useForm({ resolver: zodResolver(forgotPasswordSchema), mode: 'onBlur' })

  const onSubmit = async (values) => {
    setFormError(null)

    try {
      await api.post('/auth/forgot-password', values)
      setSentTo(values.email)
    } catch (error) {
      const parsed = parseApiError(error)
      applyServerErrors(parsed.errors, setError)

      if (!Object.keys(parsed.errors ?? {}).length) {
        setFormError(parsed.message)
      }
    }
  }

  if (sentTo) {
    return (
      <AuthLayout
        title="Check your inbox"
        subtitle={`We sent a reset link to ${sentTo}.`}
      >
        <div className="space-y-6">
          <Alert tone="success" title="Link sent">
            The link is valid for 60 minutes. If it does not arrive, check your spam folder before
            requesting another.
          </Alert>

          <Button to="/login" variant="secondary" size="lg" fullWidth>
            Back to sign in
          </Button>

          <button
            type="button"
            onClick={() => onSubmit({ email: getValues('email') })}
            className="w-full text-center text-sm font-semibold text-navy-800 underline-offset-4 hover:underline"
          >
            Send the link again
          </button>
        </div>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout
      title="Forgot your password?"
      subtitle="Enter the address you registered with. We will check it against your account and send a secure reset link."
      footer={
        <>
          Remembered it?{' '}
          <Link to="/login" className="font-semibold text-navy-800 underline underline-offset-4">
            Back to sign in
          </Link>
        </>
      }
    >
      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
        {formError && <Alert tone="error">{formError}</Alert>}

        <Input
          label="Email address"
          icon="Mail"
          type="email"
          autoComplete="email"
          error={errors.email?.message}
          {...register('email')}
        />

        <Button type="submit" size="lg" fullWidth loading={isSubmitting}>
          {isSubmitting ? 'Sending…' : 'Send reset link'}
          {!isSubmitting && <Icon name="ArrowRight" className="size-[18px]" />}
        </Button>
      </form>
    </AuthLayout>
  )
}
