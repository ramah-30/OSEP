import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import Badge from '../../../components/ui/Badge'
import Alert from '../../../components/ui/Alert'
import EmptyState from '../../../components/ui/EmptyState'
import Drawer from '../../../components/ui/Drawer'
import LoadState from '../../../components/dashboard/LoadState'
import RatingStars from '../../../components/marketplace/RatingStars'
import PlannerBadge from '../../../components/marketplace/PlannerBadge'
import { Field } from '../../../components/ui/Field'
import Textarea from '../../../components/ui/Textarea'
import { useResource } from '../../../lib/useResource'
import { api, parseApiError } from '../../../lib/api'

export default function FindPlanner() {
  const navigate = useNavigate()
  const [q, setQ] = useState('')
  const [debouncedQ, setDebouncedQ] = useState('')
  const [selectedPlanner, setSelectedPlanner] = useState(null)
  const [bookError, setBookError] = useState(null)
  const [booked, setBooked] = useState(false)

  useEffect(() => {
    const id = setTimeout(() => setDebouncedQ(q), 350)
    return () => clearTimeout(id)
  }, [q])

  const path = useMemo(() => {
    const params = new URLSearchParams()
    if (debouncedQ) params.set('q', debouncedQ)
    return `/planners?${params.toString()}`
  }, [debouncedQ])

  const { data, loading, error, reload } = useResource(path)

  const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm({
    // Wedding-only platform for now: the type is fixed, carried through on submit.
    defaultValues: { event_type: 'wedding' },
  })

  function openBooking(planner) {
    setSelectedPlanner(planner)
    setBookError(null)
    setBooked(false)
    reset()
  }

  function closeDrawer() {
    setSelectedPlanner(null)
    setBookError(null)
    setBooked(false)
  }

  const onSubmit = async (values) => {
    setBookError(null)
    try {
      await api.post('/booking-requests', {
        ...values,
        planner_id: selectedPlanner.id,
        expected_guests: values.expected_guests ? Number(values.expected_guests) : undefined,
      })
      setBooked(true)
    } catch (err) {
      setBookError(parseApiError(err).message)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Find a Planner"
        description="Browse available event planners and send a booking request."
      />

      <div className="relative">
        <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="Search by name, company, specialization or location"
          className="h-11 w-full rounded-btn border border-line bg-surface pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600 focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]"
        />
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          data.planners.length ? (
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {data.planners.map((planner) => (
                <Card key={planner.id} className="flex flex-col p-5">
                  <div className="flex items-start gap-4">
                    {planner.avatar_url ? (
                      <img
                        src={planner.avatar_url}
                        alt=""
                        className="size-12 shrink-0 rounded-full object-cover"
                      />
                    ) : (
                      <div className="grid size-12 shrink-0 place-items-center rounded-full bg-navy-100 text-lg font-bold text-navy-700 dark:bg-navy-900 dark:text-navy-200">
                        {(planner.company_name || planner.full_name).charAt(0)}
                      </div>
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-bold text-ink">
                        {planner.company_name || planner.full_name}
                      </p>
                      {planner.company_name && (
                        <p className="text-xs text-muted">{planner.full_name}</p>
                      )}
                      {planner.specialization && (
                        <Badge tone="info" className="mt-1">{planner.specialization}</Badge>
                      )}
                    </div>
                  </div>

                  <div className="mt-3 flex flex-wrap items-center gap-2">
                    {planner.reviews_count > 0 && (
                      <RatingStars rating={planner.rating} count={planner.reviews_count} size="size-3.5" />
                    )}
                    <PlannerBadge badge={planner.badge} />
                  </div>

                  <div className="mt-3 space-y-1 text-sm text-muted">
                    {planner.location && (
                      <p className="flex items-center gap-1.5">
                        <Icon name="MapPin" className="size-3.5 shrink-0" />
                        <span className="truncate">{planner.location}</span>
                      </p>
                    )}
                    {planner.experience_years > 0 && (
                      <p className="flex items-center gap-1.5">
                        <Icon name="Briefcase" className="size-3.5 shrink-0" />
                        {planner.experience_years} {planner.experience_years === 1 ? 'year' : 'years'} experience
                      </p>
                    )}
                  </div>

                  {planner.bio && (
                    <p className="mt-3 text-sm leading-relaxed text-muted line-clamp-3">
                      {planner.bio}
                    </p>
                  )}

                  <div className="mt-4 flex-1" />

                  <Button
                    size="sm"
                    className="mt-4 w-full"
                    onClick={() => openBooking(planner)}
                  >
                    <Icon name="Send" className="size-4" /> Book planner
                  </Button>
                </Card>
              ))}
            </div>
          ) : (
            <EmptyState
              icon="Users"
              title="No planners found"
              description={debouncedQ ? 'Try a different search term.' : 'No planners are available right now.'}
            />
          )
        )}
      </LoadState>

      {/* Booking drawer */}
      <Drawer
        open={!!selectedPlanner}
        onClose={closeDrawer}
        title={`Book ${selectedPlanner?.company_name || selectedPlanner?.full_name || ''}`}
        description="Tell us about your event and we'll send the planner your details."
      >
        {booked ? (
          <div className="flex flex-col items-center gap-4 py-8 text-center">
            <div className="grid size-14 place-items-center rounded-full bg-green-100 dark:bg-green-950">
              <Icon name="Check" className="size-7 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p className="text-lg font-bold text-ink">Request sent!</p>
              <p className="mt-1 text-sm text-muted">
                {selectedPlanner?.full_name} will review your request and get back to you.
              </p>
            </div>
            <div className="flex gap-3">
              <Button
                variant="secondary"
                onClick={() => navigate('/dashboard/client/booking-requests')}
              >
                View my requests
              </Button>
              <Button onClick={closeDrawer}>Browse more planners</Button>
            </div>
          </div>
        ) : (
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            {bookError && <Alert tone="error">{bookError}</Alert>}

            <input type="hidden" {...register('event_type')} />
            <Field label="Event type" value="Wedding" disabled />

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
              <Field
                label="Proposed budget"
                type="number"
                min="0"
                step="1000"
                placeholder="Optional — a starting point for the planner's quote"
                error={errors.proposed_budget?.message}
                {...register('proposed_budget')}
              />
            </div>

            <Textarea
              label="Message to planner"
              rows={4}
              placeholder="Tell the planner about your vision, special requirements, or anything else they should know…"
              error={errors.message?.message}
              {...register('message')}
            />

            <div className="flex justify-end gap-3">
              <Button type="button" variant="secondary" onClick={closeDrawer}>
                Cancel
              </Button>
              <Button type="submit" loading={isSubmitting}>
                <Icon name="Send" className="size-4" /> Send request
              </Button>
            </div>
          </form>
        )}
      </Drawer>
    </div>
  )
}
