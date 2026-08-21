import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import PageHeader from '../../../components/ui/PageHeader'
import Button from '../../../components/ui/Button'
import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Icon from '../../../components/ui/Icon'
import ProgressBar from '../../../components/ui/ProgressBar'
import EmptyState from '../../../components/ui/EmptyState'
import Drawer from '../../../components/ui/Drawer'
import ConfirmDialog from '../../../components/ui/ConfirmDialog'
import { SelectField } from '../../../components/ui/Field'
import LoadState from '../../../components/dashboard/LoadState'
import EventForm from '../../../components/dashboard/EventForm'
import { useResource } from '../../../lib/useResource'
import { useAuth } from '../../../context/AuthContext'
import { api } from '../../../lib/api'
import { formatCurrency, formatDate } from '../../../lib/format'
import { EVENT_PIPELINE, EVENT_STATUS_TONE, PRIORITY_OPTIONS, PRIORITY_TONE } from '../../../lib/eventConstants'

export default function Events() {
  const { user } = useAuth()
  const navigate = useNavigate()
  const { t } = useTranslation()
  const [filters, setFilters] = useState({ q: '', status: '', priority: '' })
  const [debouncedQ, setDebouncedQ] = useState('')
  const [creating, setCreating] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [deletingId, setDeletingId] = useState(null)
  const [deleting, setDeleting] = useState(false)

  useEffect(() => {
    const id = setTimeout(() => setDebouncedQ(filters.q), 350)
    return () => clearTimeout(id)
  }, [filters.q])

  const path = useMemo(() => {
    const params = new URLSearchParams()
    if (debouncedQ) params.set('q', debouncedQ)
    if (filters.status) params.set('status', filters.status)
    if (filters.priority) params.set('priority', filters.priority)
    params.set('sort', 'event_date')
    return `/events?${params.toString()}`
  }, [debouncedQ, filters.status, filters.priority])

  const { data, loading, error, reload } = useResource(path)

  async function handleCreate(values) {
    setSubmitting(true)
    try {
      const r = await api.post('/events', values)
      navigate(`${user.dashboard_path}/events/${r.data.data.event.id}`)
    } finally {
      setSubmitting(false)
    }
  }

  async function handleDelete() {
    setDeleting(true)
    try {
      await api.delete(`/events/${deletingId}`)
      setDeletingId(null)
      reload()
    } finally {
      setDeleting(false)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t('nav.events')}
        description={t('events.eventsDescription')}
        actions={
          <Button size="sm" onClick={() => setCreating(true)}>
            <Icon name="Plus" className="size-4" /> {t('events.createEvent')}
          </Button>
        }
      />

      <div className="flex flex-col gap-3 sm:flex-row">
        <div className="relative flex-1">
          <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
          <input
            value={filters.q}
            onChange={(e) => setFilters((f) => ({ ...f, q: e.target.value }))}
            placeholder={t('forms.searchEvents')}
            className="h-11 w-full rounded-btn border border-line bg-surface pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600 focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]"
          />
        </div>
        <SelectField className="sm:w-44" value={filters.status}
          onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value }))}>
          <option value="">{t('filters.allStatuses')}</option>
          {EVENT_PIPELINE.concat([{ value: 'cancelled', label: 'Cancelled' }]).map((s) => (
            <option key={s.value} value={s.value}>{s.label}</option>
          ))}
        </SelectField>
        <SelectField className="sm:w-40" value={filters.priority}
          onChange={(e) => setFilters((f) => ({ ...f, priority: e.target.value }))}>
          <option value="">{t('filters.allPriorities')}</option>
          {PRIORITY_OPTIONS.map((p) => <option key={p.value} value={p.value}>{p.label}</option>)}
        </SelectField>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          data.events.length ? (
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {data.events.map((event) => (
                <Card key={event.id} as={Link} to={`${user.dashboard_path}/events/${event.id}`} hover className="group block p-5">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <p className="text-xs font-semibold uppercase tracking-wide text-muted">{event.event_code}</p>
                      <p className="mt-1 truncate font-bold text-ink">{event.title}</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <Badge tone={EVENT_STATUS_TONE[event.status] ?? 'muted'} dot>{event.status_label}</Badge>
                      <button
                        type="button"
                        onClick={(e) => { e.preventDefault(); e.stopPropagation(); setDeletingId(event.id) }}
                        className="rounded p-1 text-muted opacity-0 transition-opacity hover:bg-red-50 hover:text-red-600 group-hover:opacity-100 dark:hover:bg-red-950"
                        aria-label="Delete event"
                      >
                        <Icon name="Trash2" className="size-4" />
                      </button>
                    </div>
                  </div>

                  <div className="mt-3 space-y-1.5 text-sm text-muted">
                    <p className="flex items-center gap-2"><Icon name="Calendar" className="size-4" />{formatDate(event.event_date)}</p>
                    {event.venue && <p className="flex items-center gap-2"><Icon name="MapPin" className="size-4" /><span className="truncate">{event.venue}</span></p>}
                  </div>

                  <div className="mt-4 flex items-center gap-3">
                    <ProgressBar value={event.progress} className="flex-1" />
                    <span className="text-sm font-semibold text-ink">{event.progress}%</span>
                  </div>

                  <div className="mt-4 flex items-center justify-between border-t border-line pt-3 text-xs text-muted">
                    <span className="flex items-center gap-3">
                      <span className="flex items-center gap-1"><Icon name="ListChecks" className="size-3.5" />{event.counts?.tasks ?? 0}</span>
                      <span className="flex items-center gap-1"><Icon name="Users" className="size-3.5" />{event.counts?.guests ?? 0}</span>
                    </span>
                    {event.priority && <Badge tone={PRIORITY_TONE[event.priority] ?? 'muted'}>{event.priority_label}</Badge>}
                  </div>

                  {event.budget?.total > 0 && (
                    <p className="mt-3 text-sm font-semibold text-ink">{formatCurrency(event.budget.total)}</p>
                  )}
                </Card>
              ))}
            </div>
          ) : (
            <EmptyState
              icon="CalendarClock"
              title={t('events.noEventsMatch')}
              description={t('events.noEventsMatchDesc')}
              action={<Button size="sm" onClick={() => setCreating(true)}><Icon name="Plus" className="size-4" /> {t('events.createEvent')}</Button>}
            />
          )
        )}
      </LoadState>

      <Drawer open={creating} onClose={() => setCreating(false)} title={t('events.createEvent')}
        description={t('events.createEventDesc')}>
        <EventForm onSubmit={handleCreate} submitting={submitting} showVenueLocation={false} />
      </Drawer>

      <ConfirmDialog
        open={!!deletingId}
        onClose={() => setDeletingId(null)}
        onConfirm={handleDelete}
        title={t('events.deleteEvent') + '?'}
        description={t('events.deleteEventDesc')}
        confirmLabel={t('common.delete')}
        loading={deleting}
      />
    </div>
  )
}
