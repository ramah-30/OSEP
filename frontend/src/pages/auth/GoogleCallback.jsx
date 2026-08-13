import { useEffect, useRef, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import AuthLayout from '../../components/layout/AuthLayout'
import Alert from '../../components/ui/Alert'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'
import { useAuth } from '../../context/AuthContext'
import { parseApiError } from '../../lib/api'

const ERRORS = {
  not_configured:
    'Google sign-in is not configured on this server yet. Sign in with your email and password instead.',
  google_failed: 'Google could not complete the sign-in. Please try again.',
  no_email: 'That Google account has no email address attached, so we cannot create an account from it.',
  account_unavailable: 'This account is not available. Please contact support.',
}

/**
 * Trades the one-time code the API put in the URL for a bearer token. The code
 * is single-use and expires in two minutes, so it is safe in browser history.
 */
export default function GoogleCallback() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { loginWithGoogleCode } = useAuth()
  const [error, setError] = useState(ERRORS[searchParams.get('error')] ?? null)
  const exchanged = useRef(false)

  const code = searchParams.get('code')

  useEffect(() => {
    if (!code || exchanged.current) {
      if (!code && !searchParams.get('error')) {
        setError('This sign-in link is incomplete. Please try again.')
      }
      return
    }

    exchanged.current = true

    loginWithGoogleCode(code)
      .then((user) => navigate(user.dashboard_path, { replace: true }))
      .catch((requestError) => setError(parseApiError(requestError).message))
  }, [code, searchParams, loginWithGoogleCode, navigate])

  if (error) {
    return (
      <AuthLayout title="Google sign-in failed" subtitle="We could not finish signing you in.">
        <div className="space-y-6">
          <Alert tone="error">{error}</Alert>
          <Button to="/login" size="lg" fullWidth>
            Back to sign in
          </Button>
        </div>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout title="Signing you in…" subtitle="One moment while we finish with Google.">
      <div className="flex justify-center py-8 text-navy-800">
        <Spinner className="size-8" />
      </div>
    </AuthLayout>
  )
}
