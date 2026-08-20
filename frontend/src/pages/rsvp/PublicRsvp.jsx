import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import Button from '../../components/ui/Button'
import Icon from '../../components/ui/Icon'
import Spinner from '../../components/ui/Spinner'
import { SelectField } from '../../components/ui/Field'
import Textarea from '../../components/ui/Textarea'
import Logo from '../../components/ui/Logo'
import { api } from '../../lib/api'
import { formatDate } from '../../lib/format'
import { cn } from '../../lib/cn'

const CHOICES = [
  { value: 'attending', label: "I'll be there", icon: 'CheckCircle2', tone: 'border-emerald-500 bg-emerald-50 text-emerald-700' },
  { value: 'maybe', label: 'Maybe', icon: 'Clock', tone: 'border-warning bg-warning-soft text-warning' },
  { value: 'not_attending', label: "Can't make it", icon: 'X', tone: 'border-danger bg-danger-soft text-danger' },
]

export default function PublicRsvp() {
  const { token } = useParams()
  const [state, setState] = useState({ loading: true, error: null, data: null })
  const [form, setForm] = useState({ response: '', additional_guests: 0, meal_choice: '', special_requirements: '', message: '' })
  const [submitting, setSubmitting] = useState(false)
  const [done, setDone] = useState(null)

  useEffect(() => {
    api.get(`/rsvp/${token}`)
      .then((r) => {
        const d = r.data.data
        setState({ loading: false, error: null, data: d })
        if (d.current_response) {
          setForm({
            response: d.current_response.response,
            additional_guests: d.current_response.additional_guests ?? 0,
            meal_choice: d.current_response.meal_choice ?? '',
            special_requirements: d.current_response.special_requirements ?? '',
            message: d.current_response.message ?? '',
          })
        }
      })
      .catch((e) => setState({ loading: false, error: e.response?.status === 404 ? 'notfound' : 'error', data: null }))
  }, [token])

  async function submit(e) {
    e.preventDefault()
    if (!form.response) return
    setSubmitting(true)
    try {
      const r = await api.post(`/rsvp/${token}`, form)
      setDone({ confirmed: r.data.data.confirmed, message: r.data.message })
    } catch {
      setState((s) => ({ ...s, error: 'submit' }))
    } finally {
      setSubmitting(false)
    }
  }

  if (state.loading) {
    return <Shell><div className="grid place-items-center py-16"><Spinner className="size-8" /></div></Shell>
  }

  if (state.error === 'notfound') {
    return <Shell><Message icon="Search" title="Invitation not found" text="This RSVP link isn't valid. Please check the link in your invitation." /></Shell>
  }

  if (done) {
    return (
      <Shell>
        <Message
          icon={done.confirmed ? 'PartyPopper' : 'CheckCircle2'}
          title="Thank you!"
          text={done.message}
        />
        {done.confirmed && (
          <p className="mt-2 text-center text-sm text-muted">
            You're on the list your digital ticket will be shared closer to the day.
          </p>
        )}
      </Shell>
    )
  }

  const { event, guest, meal_options: meals } = state.data

  return (
    <Shell>
      <div className="mb-6 text-center">
        <p className="text-sm font-semibold uppercase tracking-wide text-navy-600">You're invited</p>
        <h1 className="mt-1 text-2xl font-extrabold text-ink">{event.title}</h1>
        <div className="mt-3 space-y-1 text-sm text-muted">
          {event.date && <p className="flex items-center justify-center gap-1.5"><Icon name="Calendar" className="size-4" />{formatDate(event.date)}{event.start_time ? ` · ${event.start_time}` : ''}</p>}
          {event.venue && <p className="flex items-center justify-center gap-1.5"><Icon name="MapPin" className="size-4" />{event.venue}</p>}
        </div>
        {event.description && <p className="mt-3 text-sm text-muted">{event.description}</p>}
      </div>

      <form onSubmit={submit} className="space-y-5">
        <div>
          <p className="mb-2 text-center text-sm font-semibold text-ink">Hi {guest.first_name || guest.full_name}, will you join us?</p>
          <div className="grid grid-cols-3 gap-2">
            {CHOICES.map((c) => (
              <button
                key={c.value}
                type="button"
                onClick={() => setForm((f) => ({ ...f, response: c.value }))}
                className={cn(
                  'flex flex-col items-center gap-1.5 rounded-xl border-2 p-3 text-xs font-semibold transition-all',
                  form.response === c.value ? c.tone : 'border-line bg-surface text-muted hover:border-navy-200',
                )}
              >
                <Icon name={c.icon} className="size-5" />
                {c.label}
              </button>
            ))}
          </div>
        </div>

        {form.response === 'attending' && (
          <div className="space-y-4">
            {guest.plus_ones_allowed > 0 && (
              <SelectField label={`Bringing guests? (up to ${guest.plus_ones_allowed})`}
                value={form.additional_guests} onChange={(e) => setForm((f) => ({ ...f, additional_guests: Number(e.target.value) }))}>
                {Array.from({ length: guest.plus_ones_allowed + 1 }, (_, i) => <option key={i} value={i}>{i}</option>)}
              </SelectField>
            )}
            {meals.length > 0 && (
              <SelectField label="Meal preference" value={form.meal_choice} onChange={(e) => setForm((f) => ({ ...f, meal_choice: e.target.value }))}>
                <option value="">No preference</option>
                {meals.map((m) => <option key={m.name} value={m.name}>{m.name}</option>)}
              </SelectField>
            )}
            <Textarea label="Any special requirements?" rows={2} value={form.special_requirements}
              onChange={(e) => setForm((f) => ({ ...f, special_requirements: e.target.value }))} placeholder="Accessibility, allergies…" />
          </div>
        )}

        <Textarea label="Message to the host (optional)" rows={2} value={form.message}
          onChange={(e) => setForm((f) => ({ ...f, message: e.target.value }))} />

        {state.error === 'submit' && <p className="text-center text-sm text-danger">Something went wrong. Please try again.</p>}

        <Button type="submit" className="w-full" loading={submitting} disabled={!form.response}>Send my response</Button>
      </form>
    </Shell>
  )
}

function Shell({ children }) {
  return (
    <div className="min-h-screen bg-canvas px-4 py-10">
      <div className="mx-auto max-w-md">
        <div className="mb-6 flex justify-center"><Logo /></div>
        <div className="rounded-2xl border border-line bg-surface p-6 shadow-sm sm:p-8">{children}</div>
        <p className="mt-4 text-center text-xs text-muted">Powered by OSEP</p>
      </div>
    </div>
  )
}

function Message({ icon, title, text }) {
  return (
    <div className="py-6 text-center">
      <span className="mx-auto mb-4 grid size-14 place-items-center rounded-full bg-navy-50 text-navy-700">
        <Icon name={icon} className="size-7" />
      </span>
      <h1 className="text-xl font-extrabold text-ink">{title}</h1>
      <p className="mt-2 text-sm text-muted">{text}</p>
    </div>
  )
}
