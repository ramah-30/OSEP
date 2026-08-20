import { useEffect, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import AuthLayout from '../../components/layout/AuthLayout'
import Alert from '../../components/ui/Alert'
import Button from '../../components/ui/Button'
import Icon from '../../components/ui/Icon'
import { useAuth } from '../../context/AuthContext'

const OUTCOMES = {
  verified: {
    tone: 'success',
    icon: 'CheckCircle2',
    title: 'Email confirmed',
    body: 'Your account is active. Everything is ready for you.',
  },
  'already-verified': {
    tone: 'info',
    icon: 'Info',
    title: 'Already confirmed',
    body: 'This address was confirmed earlier — you can sign in as normal.',
  },
  invalid: {
    tone: 'error',
    icon: 'TriangleAlert',
    title: 'This link did not work',
    body: 'The link may have expired or been used already. Request a fresh one and try again.',
  },
}

/**
 * Where the API sends the browser after processing the signed link. The token
 * work already happened server-side; this screen only reports the outcome.
 */
export default function VerifyEmailCallback() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { isAuthenticated, user, refreshUser } = useAuth()

  const status = searchParams.get('status') ?? 'invalid'
  const outcome = OUTCOMES[status] ?? OUTCOMES.invalid
  const [refreshed, setRefreshed] = useState(false)

  // A signed-in user's cached record still says "pending" — pull the fresh one.
  useEffect(() => {
    if (status === 'verified' && isAuthenticated && !refreshed) {
      setRefreshed(true)
      refreshUser()
    }
  }, [status, isAuthenticated, refreshed, refreshUser])

  const goOn = () => {
    if (isAuthenticated && user?.email_verified) {
      navigate(user.dashboard_path, { replace: true })
    } else {
      navigate('/login?notice=verified', { replace: true })
    }
  }

  return (
    <AuthLayout title={outcome.title} subtitle={outcome.body}>
      <div className="space-y-6">
        <div className="flex justify-center">
          <span
            className={`grid size-16 place-items-center rounded-2xl ${
              outcome.tone === 'success'
                ? 'bg-emerald-50 text-emerald-600'
                : outcome.tone === 'error'
                  ? 'bg-danger-soft text-danger'
                  : 'bg-navy-50 text-navy-800'
            }`}
          >
            <Icon name={outcome.icon} className="size-8" />
          </span>
        </div>

        {status === 'invalid' ? (
          <>
            <Alert tone="error" title="Confirmation failed">
              Verification links expire after 60 minutes and can only be used once.
            </Alert>
            <Button to="/verify-email" size="lg" fullWidth>
              Send a new link
            </Button>
            <Button to="/login" variant="secondary" size="lg" fullWidth>
              Back to sign in
            </Button>
          </>
        ) : (
          <Button size="lg" fullWidth onClick={goOn}>
            Continue
            <Icon name="ArrowRight" className="size-[18px]" />
          </Button>
        )}
      </div>
    </AuthLayout>
  )
}
