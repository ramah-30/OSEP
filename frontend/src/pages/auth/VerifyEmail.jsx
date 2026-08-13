import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import AuthLayout from '../../components/layout/AuthLayout'
import Alert from '../../components/ui/Alert'
import Button from '../../components/ui/Button'
import Icon from '../../components/ui/Icon'
import { useAuth } from '../../context/AuthContext'
import { api, parseApiError } from '../../lib/api'

const CHECKLIST = [
  'Open the email from OSEP titled “Confirm your OSEP account”.',
  'Tap the confirm button — the link is valid for 60 minutes.',
  'You will land back here with your account active.',
]

export default function VerifyEmail() {
  const [searchParams] = useSearchParams()
  const { user, logout } = useAuth()
  const [status, setStatus] = useState(null)
  const [sending, setSending] = useState(false)

  const email = searchParams.get('email') ?? user?.email ?? ''

  const resend = async () => {
    setSending(true)
    setStatus(null)

    try {
      const { data } = await api.post('/auth/resend-verification', { email })
      setStatus({ tone: 'success', message: data.message })
    } catch (error) {
      setStatus({ tone: 'error', message: parseApiError(error).message })
    } finally {
      setSending(false)
    }
  }

  return (
    <AuthLayout
      title="Confirm your email"
      subtitle={
        email
          ? `We sent a confirmation link to ${email}. One click and your account is active.`
          : 'We sent you a confirmation link. One click and your account is active.'
      }
    >
      <div className="space-y-6">
        <div className="flex justify-center">
          <span className="grid size-16 place-items-center rounded-2xl bg-navy-50 text-navy-800">
            <Icon name="Mail" className="size-8" />
          </span>
        </div>

        {status && <Alert tone={status.tone}>{status.message}</Alert>}

        <ol className="space-y-3 rounded-card border border-line bg-canvas p-6">
          {CHECKLIST.map((item, index) => (
            <li key={item} className="flex gap-3 text-[0.95rem] text-ink/80">
              <span className="grid size-6 shrink-0 place-items-center rounded-full bg-navy-800 text-xs font-bold text-white">
                {index + 1}
              </span>
              {item}
            </li>
          ))}
        </ol>

        <Button size="lg" fullWidth loading={sending} onClick={resend} disabled={!email}>
          {sending ? 'Sending…' : 'Resend confirmation email'}
        </Button>

        <p className="text-center text-sm text-muted">
          Wrong address, or want to start over?{' '}
          <button
            type="button"
            onClick={logout}
            className="font-semibold text-navy-800 underline underline-offset-4"
          >
            Sign out
          </button>{' '}
          or{' '}
          <Link to="/register" className="font-semibold text-navy-800 underline underline-offset-4">
            register again
          </Link>
          .
        </p>
      </div>
    </AuthLayout>
  )
}
