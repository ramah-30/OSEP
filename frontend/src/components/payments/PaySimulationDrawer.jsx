import { useEffect, useState } from 'react'
import Drawer from '../ui/Drawer'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import Alert from '../ui/Alert'
import { Field, SelectField } from '../ui/Field'
import { formatCurrency } from '../../lib/format'
import { MOBILE_NETWORKS } from '../../lib/paymentConstants'

/**
 * Shared staged mobile-money simulator used by both the client's "pay an
 * invoice" flow and the planner's "pay a vendor" flow — same UX, different
 * payee/endpoint. `onSubmit` does the actual API call and must resolve to
 * `{ status: 'completed' | 'failed', receiptNumber, message }`.
 */
export default function PaySimulationDrawer({ open, onClose, payee, balance, currency = 'TZS', onSubmit, onSettled }) {
  const [step, setStep] = useState('amount')
  const [amount, setAmount] = useState('')
  const [payerPhone, setPayerPhone] = useState('')
  const [network, setNetwork] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState(null)
  const [result, setResult] = useState(null)

  useEffect(() => {
    if (open) {
      setStep('amount')
      setAmount(balance ? String(balance) : '')
      setPayerPhone('')
      setNetwork('')
      setError(null)
      setResult(null)
    }
  }, [open, balance])

  const continueToGateway = () => {
    const value = Number(amount)
    if (!value || value <= 0) return setError('Enter an amount to pay.')
    if (value > balance) return setError(`Amount can't exceed the balance of ${formatCurrency(balance, currency)}.`)
    setError(null)
    setStep('gateway')
  }

  const pay = async () => {
    if (!payerPhone.trim()) return setError('Enter the number you’re paying from.')
    if (!network) return setError('Choose a mobile network.')

    setError(null)
    setSubmitting(true)
    try {
      const outcome = await onSubmit({ amount: Number(amount), payer_phone: payerPhone.trim(), network })
      setResult(outcome)
      setStep('result')
      onSettled?.(outcome)
    } catch (err) {
      setError(err?.response?.data?.message ?? 'Something went wrong. Please try again.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Drawer
      open={open}
      onClose={onClose}
      title="Pay"
      description={payee?.name ? `Paying ${payee.name}` : undefined}
    >
      {error && <Alert tone="error" className="mb-4">{error}</Alert>}

      {step === 'amount' && (
        <div className="space-y-5">
          <p className="text-sm text-muted">
            Balance due: <span className="font-bold text-ink">{formatCurrency(balance, currency)}</span>
          </p>

          <div className="flex gap-2">
            <Button size="sm" variant="secondary" onClick={() => setAmount(String(balance / 2))}>Pay half</Button>
            <Button size="sm" variant="secondary" onClick={() => setAmount(String(balance))}>Pay in full</Button>
          </div>

          <Field
            label="Amount"
            type="number"
            min="0.01"
            max={balance}
            step="0.01"
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
          />

          <Button fullWidth onClick={continueToGateway}>
            Continue <Icon name="ArrowRight" className="size-4" />
          </Button>
        </div>
      )}

      {step === 'gateway' && (
        <div className="space-y-5">
          <div className="rounded-btn bg-canvas p-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-muted">You're paying</p>
            <p className="mt-1 font-bold text-ink">{payee?.name ?? 'Unknown'}</p>
            {payee?.phone && <p className="text-sm text-muted">{payee.phone}</p>}
            <p className="mt-2 text-sm font-semibold text-navy-700">{formatCurrency(amount, currency)}</p>
          </div>

          <Field
            label="Your mobile number"
            type="tel"
            placeholder="e.g. 0712345678"
            value={payerPhone}
            onChange={(e) => setPayerPhone(e.target.value)}
          />

          <SelectField
            label="Mobile network"
            value={network}
            onChange={(e) => setNetwork(e.target.value)}
          >
            <option value="">Select</option>
            {MOBILE_NETWORKS.map((n) => (
              <option key={n.value} value={n.value}>{n.label}</option>
            ))}
          </SelectField>

          <div className="flex gap-3">
            <Button variant="ghost" onClick={() => setStep('amount')}>Back</Button>
            <Button fullWidth loading={submitting} onClick={pay}>
              {submitting ? 'Processing…' : `Pay ${formatCurrency(amount, currency)}`}
            </Button>
          </div>
        </div>
      )}

      {step === 'result' && result && (
        <div className="space-y-5 text-center">
          {result.status === 'completed' ? (
            <>
              <span className="mx-auto grid size-16 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                <Icon name="CheckCircle2" className="size-9" />
              </span>
              <div>
                <p className="font-bold text-ink">Payment successful</p>
                <p className="mt-1 text-sm text-muted">{formatCurrency(amount, currency)} paid to {payee?.name}.</p>
                {result.receiptNumber && (
                  <p className="mt-1 text-xs text-muted">Receipt {result.receiptNumber}</p>
                )}
              </div>
              <Button fullWidth onClick={onClose}>Done</Button>
            </>
          ) : (
            <>
              <span className="mx-auto grid size-16 place-items-center rounded-full bg-danger-soft text-danger">
                <Icon name="TriangleAlert" className="size-9" />
              </span>
              <div>
                <p className="font-bold text-ink">Payment declined</p>
                <p className="mt-1 text-sm text-muted">{result.message ?? 'The mobile network declined this payment.'}</p>
              </div>
              <div className="flex gap-3">
                <Button variant="ghost" fullWidth onClick={onClose}>Cancel</Button>
                <Button fullWidth onClick={() => setStep('gateway')}>Try again</Button>
              </div>
            </>
          )}
        </div>
      )}
    </Drawer>
  )
}
