import { Link, Outlet, useLocation, useNavigate, useParams } from 'react-router-dom'
import Badge from '../../../components/ui/Badge'
import Icon from '../../../components/ui/Icon'
import ProgressBar from '../../../components/ui/ProgressBar'
import Dropdown, { DropdownItem } from '../../../components/ui/Dropdown'
import LoadState from '../../../components/dashboard/LoadState'
import { AskAiButton } from '../../../components/ai/InlineAiButtons'
import { useResource } from '../../../lib/useResource'
import { useAuth } from '../../../context/AuthContext'
import { api } from '../../../lib/api'
import { formatDate } from '../../../lib/format'
import { EVENT_PIPELINE, EVENT_STATUS_TONE } from '../../../lib/eventConstants'

const TABS = [
  { value: '', label: 'Overview' },
  { value: 'timeline', label: 'Timeline' },
  { value: 'tasks', label: 'Tasks' },
  { value: 'budget', label: 'Budget' },
  { value: 'guests', label: 'Guests' },
  { value: 'vendors', label: 'Vendors' },
  { value: 'venue', label: 'Venue' },
  { value: 'venue-designer', label: 'Venue Designer' },
  { value: 'settings', label: 'Settings' },
]

const PIPE = EVENT_PIPELINE.map((s) => s.value)

/** Statuses a planner may move to from the current one (mirrors the backend). */
function allowedTargets(current) {
  if (current === 'cancelled') return ['planning']
  const i = PIPE.indexOf(current)
  const out = []
  if (i > 0) out.push(PIPE[i - 1])
  if (i >= 0 && i < PIPE.length - 1) out.push(PIPE[i + 1])
  out.push('cancelled')
  return out
}

const LABELS = { ...Object.fromEntries(EVENT_PIPELINE.map((s) => [s.value, s.label])), cancelled: 'Cancelled' }

export default function EventWorkspace() {
  const { eventId } = useParams()
  const { user } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const { data, loading, error, reload } = useResource(`/events/${eventId}`)

  const base = `${user.dashboard_path}/events/${eventId}`
  const rest = location.pathname.replace(base, '').replace(/^\//, '')
  const activeTab = rest.split('/')[0]

  async function changeStatus(status) {
    try {
      await api.put(`/events/${eventId}/status`, { status })
      reload()
    } catch {
      /* backend rejects invalid transitions; nothing to do here */
    }
  }

  return (
    <div className="space-y-6">
      <Link to={`${user.dashboard_path}/events`} className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <Icon name="ArrowLeft" className="size-4" /> All events
      </Link>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data?.event && (
          <>
            <div className="rounded-card border border-line/80 bg-surface p-6 shadow-card">
              <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0">
                  <p className="text-xs font-semibold uppercase tracking-wide text-muted">{data.event.event_code}</p>
                  <h1 className="mt-1 text-h3 font-extrabold tracking-tight text-ink">{data.event.title}</h1>
                  <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-muted">
                    <span className="flex items-center gap-1.5"><Icon name="Calendar" className="size-4" />{formatDate(data.event.event_date)}</span>
                    {data.event.venue && <span className="flex items-center gap-1.5"><Icon name="MapPin" className="size-4" />{data.event.venue}</span>}
                    {data.event.client && <span className="flex items-center gap-1.5"><Icon name="User" className="size-4" />{data.event.client.full_name}</span>}
                    {data.event.expected_guests != null && <span className="flex items-center gap-1.5"><Icon name="Users" className="size-4" />{data.event.expected_guests} guests</span>}
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                  <Badge tone={EVENT_STATUS_TONE[data.event.status] ?? 'muted'} dot>{data.event.status_label}</Badge>
                  <AskAiButton
                    eventId={data.event.id}
                    prompt={`Summarize where ${data.event.title} stands and what I should focus on next.`}
                  />
                  <Dropdown
                    trigger={() => (
                      <span className="inline-flex h-10 items-center gap-1.5 rounded-btn border border-line bg-surface px-3 text-sm font-semibold text-ink hover:border-navy-200">
                        Change status <Icon name="ChevronDown" className="size-4" />
                      </span>
                    )}
                  >
                    {({ close }) =>
                      allowedTargets(data.event.status).map((s) => (
                        <DropdownItem key={s} onClick={() => { changeStatus(s); close() }}>
                          {LABELS[s]}
                        </DropdownItem>
                      ))
                    }
                  </Dropdown>
                </div>
              </div>

              <div className="mt-5 flex items-center gap-4">
                <ProgressBar value={data.event.progress} className="max-w-md flex-1" />
                <span className="text-sm font-semibold text-ink">{data.event.progress}% complete</span>
              </div>
            </div>

            <div className="flex gap-1 overflow-x-auto border-b border-line">
              {TABS.map((tab) => {
                const active = tab.value === activeTab
                return (
                  <button
                    key={tab.value}
                    type="button"
                    onClick={() => navigate(tab.value ? `${base}/${tab.value}` : base)}
                    className={`relative whitespace-nowrap px-4 py-3 text-sm font-semibold transition-colors ${active ? 'text-navy-800' : 'text-muted hover:text-ink'}`}
                  >
                    {tab.label}
                    {active && <span className="absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-navy-800" />}
                  </button>
                )
              })}
            </div>

            <Outlet context={{ event: data.event, reload }} />
          </>
        )}
      </LoadState>
    </div>
  )
}
