import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import Button from '../../components/ui/Button'
import Card from '../../components/ui/Card'
import Icon from '../../components/ui/Icon'
import Alert from '../../components/ui/Alert'
import Spinner from '../../components/ui/Spinner'
import { Field, SelectField } from '../../components/ui/Field'
import Textarea from '../../components/ui/Textarea'
import RatingStars from '../../components/marketplace/RatingStars'
import PlannerBadge from '../../components/marketplace/PlannerBadge'
import { api, parseApiError } from '../../lib/api'
import { useAuth } from '../../context/AuthContext'
import { formatDate } from '../../lib/format'

const EVENT_TYPES = [
  'Wedding', 'Birthday', 'Corporate', 'Conference', 'Baby Shower',
  'Anniversary', 'Graduation', 'Concert', 'Exhibition', 'Other',
]

export default function PlannerBookingPage() {
  const { slug } = useParams()
  const { user } = useAuth()
  const navigate = useNavigate()

  const [planner, setPlanner] = useState(null)
  const [reviews, setReviews] = useState([])
  const [loading, setLoading] = useState(true)
  const [fetchError, setFetchError] = useState(null)
  const [submitted, setSubmitted] = useState(false)
  const [formError, setFormError] = useState(null)

  const { register, handleSubmit, formState: { errors, isSubmitting } } = useForm()

  useEffect(() => {
    api.get(`/planners/${slug}`)
      .then((r) => { setPlanner(r.data.data.planner); setReviews(r.data.data.reviews ?? []) })
      .catch(() => setFetchError('This planner page could not be found.'))
      .finally(() => setLoading(false))
  }, [slug])

  const onSubmit = async (values) => {
    if (!user) {
      navigate(`/login?redirect=/book/${slug}`)
      return
    }
    if (user.account_type !== 'client') {
      setFormError('Only client accounts can send booking requests.')
      return
    }
    setFormError(null)
    try {
      await api.post('/booking-requests', {
        ...values,
        planner_id: planner.id,
        expected_guests: values.expected_guests ? Number(values.expected_guests) : undefined,
      })
      setSubmitted(true)
    } catch (err) {
      setFormError(parseApiError(err).message)
    }
  }

  if (loading) {
    return (
      <div className="grid min-h-screen place-items-center bg-neutral-50 dark:bg-neutral-950">
        <Spinner className="size-10" />
      </div>
    )
  }

  if (fetchError) {
    return (
      <div className="grid min-h-screen place-items-center bg-neutral-50 px-4 dark:bg-neutral-950">
        <div className="text-center">
          <Icon name="CalendarX2" className="mx-auto mb-4 size-12 text-muted" />
          <h1 className="text-h2 font-bold text-ink">Planner not found</h1>
          <p className="mt-2 text-muted">{fetchError}</p>
          <Link to="/" className="mt-6 inline-block text-sm font-semibold text-navy-700 hover:underline">
            Back to OSEP
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-neutral-50 py-12 dark:bg-neutral-950">
      <div className="mx-auto max-w-2xl px-4">
        {/* Planner card */}
        <Card className="mb-6 flex items-start gap-5 p-6">
          {planner.avatar_url ? (
            <img src={planner.avatar_url} alt="" className="size-16 rounded-full object-cover" />
          ) : (
            <div className="grid size-16 place-items-center rounded-full bg-navy-100 text-xl font-bold text-navy-700 dark:bg-navy-900 dark:text-navy-200">
              {planner.full_name.charAt(0)}
            </div>
          )}
          <div className="min-w-0">
            <p className="text-xs font-semibold uppercase tracking-wide text-muted">Event Planner</p>
            <h1 className="text-h3 font-extrabold text-ink">{planner.company_name || planner.full_name}</h1>
            {planner.specialization && (
              <p className="mt-0.5 text-sm text-muted">{planner.specialization}</p>
            )}
            <div className="mt-2 flex flex-wrap items-center gap-2">
              {planner.reviews_count > 0 && (
                <RatingStars rating={planner.rating} count={planner.reviews_count} size="size-3.5" />
              )}
              <PlannerBadge badge={planner.badge} />
            </div>
            {planner.location && (
              <p className="mt-1 flex items-center gap-1.5 text-sm text-muted">
                <Icon name="MapPin" className="size-3.5" />{planner.location}
              </p>
            )}
            {planner.bio && (
              <p className="mt-3 text-sm leading-relaxed text-muted line-clamp-3">{planner.bio}</p>
            )}
          </div>
        </Card>

        {/* Client reviews */}
        {reviews.length > 0 && (
          <Card className="mb-6 p-6">
            <p className="mb-4 flex items-center gap-2 font-bold text-ink">
              <Icon name="Star" className="size-4 fill-warning text-warning" /> What clients say
            </p>
            <div className="space-y-4">
              {reviews.map((r) => (
                <div key={r.id} className="border-b border-line pb-4 last:border-0 last:pb-0">
                  <div className="flex items-center justify-between gap-2">
                    <span className="text-sm font-semibold text-ink">{r.reviewer?.full_name ?? 'Client'}</span>
                    <RatingStars rating={r.rating} showValue={false} size="size-3.5" />
                  </div>
                  {r.comment && <p className="mt-1.5 text-sm leading-relaxed text-muted">{r.comment}</p>}
                  <p className="mt-1 text-xs text-muted">{formatDate(r.created_at)}</p>
                </div>
              ))}
            </div>
          </Card>
        )}

        {submitted ? (
          <Card className="p-8 text-center">
            <div className="mx-auto mb-4 grid size-14 place-items-center rounded-full bg-green-100 dark:bg-green-950">
              <Icon name="Check" className="size-7 text-green-600 dark:text-green-400" />
            </div>
            <h2 className="text-h3 font-bold text-ink">Request sent!</h2>
            <p className="mt-2 text-muted">
              {planner.full_name} will review your request and get back to you.
            </p>
            {user ? (
              <Button className="mt-6" onClick={() => navigate('/dashboard/client/booking-requests')}>
                View my requests
              </Button>
            ) : (
              <Link to="/login" className="mt-6 inline-block">
                <Button>Sign in to track your request</Button>
              </Link>
            )}
          </Card>
        ) : (
          <Card className="p-6 md:p-8">
            <h2 className="mb-1 text-lg font-bold text-ink">Book {planner.company_name || planner.full_name}</h2>
            <p className="mb-6 text-sm text-muted">
              Tell us about your event and we&apos;ll send the planner your details.
            </p>

            {!user && (
              <Alert tone="info" className="mb-6">
                <Link to={`/login?redirect=/book/${slug}`} className="font-semibold underline">Sign in</Link>
                {' '}to submit your request, or{' '}
                <Link to={`/register?redirect=/book/${slug}`} className="font-semibold underline">create an account</Link>
                {' '}— it only takes a minute.
              </Alert>
            )}

            {formError && <Alert tone="error" className="mb-6">{formError}</Alert>}

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
              <SelectField label="Event type" error={errors.event_type?.message} {...register('event_type')}>
                <option value="">Select a type</option>
                {EVENT_TYPES.map((t) => <option key={t} value={t.toLowerCase()}>{t}</option>)}
              </SelectField>

              <div className="grid gap-5 sm:grid-cols-2">
                <Field
                  label="Event date"
                  type="date"
                  error={errors.event_date?.message}
                  {...register('event_date')}
                />
                <Field
                  label="Expected guests"
                  type="number"
                  min="1"
                  placeholder="e.g. 150"
                  error={errors.expected_guests?.message}
                  {...register('expected_guests')}
                />
                <Field
                  label="Venue name"
                  placeholder="Optional"
                  error={errors.venue?.message}
                  {...register('venue')}
                />
                <Field
                  label="Location / city"
                  placeholder="Optional"
                  error={errors.location?.message}
                  {...register('location')}
                />
              </div>

              <Textarea
                label="Message to planner"
                rows={4}
                placeholder="Tell the planner about your vision, special requirements, or anything else they should know…"
                error={errors.message?.message}
                {...register('message')}
              />

              <div className="flex justify-end">
                <Button type="submit" loading={isSubmitting} disabled={!user}>
                  <Icon name="Send" className="size-4" />
                  Send booking request
                </Button>
              </div>
            </form>
          </Card>
        )}
      </div>
    </div>
  )
}
