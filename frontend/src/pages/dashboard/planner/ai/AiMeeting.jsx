import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import Spinner from '../../../../components/ui/Spinner'
import EmptyState from '../../../../components/ui/EmptyState'
import Markdown from '../../../../components/ai/Markdown'
import { api, parseApiError } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'
import { formatRelative } from '../../../../lib/format'

const BASE = '/dashboard/planner/ai-assistant'
const TYPE_LABEL = { client: 'Client meeting', vendor: 'Vendor meeting', internal: 'Internal / team', other: 'Other' }
const TYPE_TONE = { client: 'navy', vendor: 'purple', internal: 'emerald', other: 'muted' }

export default function AiMeeting() {
  const { id } = useParams()
  const [meeting, setMeeting] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [processing, setProcessing] = useState(false)
  const [busyItem, setBusyItem] = useState(null)   // action-item id in flight
  const [showNotes, setShowNotes] = useState(false)

  const load = useCallback(async () => {
    setLoading(true); setError(null)
    try {
      const r = await api.get(`/ai/meetings/${id}`)
      setMeeting(r.data.data.meeting)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [id])

  useEffect(() => { load() }, [load])

  const process = async () => {
    setProcessing(true)
    try {
      const r = await api.post(`/ai/meetings/${id}/process`)
      setMeeting(r.data.data.meeting)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setProcessing(false)
    }
  }

  const patchItem = async (item, body) => {
    setBusyItem(item.id)
    try {
      const r = await api.put(`/ai/meetings/${id}/items/${item.id}`, body)
      setMeeting(r.data.data.meeting)
    } catch { /* ignore */ } finally { setBusyItem(null) }
  }

  const convert = async (item) => {
    setBusyItem(item.id)
    try {
      const r = await api.post(`/ai/meetings/${id}/items/${item.id}/task`)
      setMeeting(r.data.data.meeting)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally { setBusyItem(null) }
  }

  const toggleDone = (item) => patchItem(item, { status: item.status === 'done' ? 'open' : 'done' })

  if (loading) return <div className="grid place-items-center py-20"><Spinner /></div>
  if (error && !meeting) {
    return (
      <EmptyState icon="NotebookPen" title="Meeting unavailable" description={error}
        action={<Button size="sm" to={`${BASE}/meetings`}>Back to meetings</Button>} />
    )
  }

  const items = meeting.action_items ?? []
  const canConvert = meeting.scope === 'event'

  return (
    <div className="space-y-5">
      <div>
        <Link to={`${BASE}/meetings`} className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink">
          <Icon name="ArrowLeft" className="size-4" /> Meetings
        </Link>
      </div>

      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <h2 className="text-xl font-bold text-ink">{meeting.title}</h2>
          <div className="mt-1.5 flex flex-wrap items-center gap-1.5">
            <Badge tone={TYPE_TONE[meeting.meeting_type] ?? 'muted'}>{TYPE_LABEL[meeting.meeting_type] ?? meeting.meeting_type}</Badge>
            <Badge tone="navy">{meeting.scope === 'event' ? (meeting.event_title ?? 'Event') : 'General'}</Badge>
            {meeting.meeting_date && <span className="text-xs text-muted">· {meeting.meeting_date}</span>}
            {meeting.attendees?.length > 0 && <span className="text-xs text-muted">· {meeting.attendees.join(', ')}</span>}
          </div>
        </div>
        <Button size="sm" onClick={process} loading={processing}>
          <Icon name="Sparkles" className="size-4" /> {meeting.status === 'processed' ? 'Re-process' : 'Process with AI'}
        </Button>
      </div>

      {error && <p className="rounded-xl border border-danger/30 bg-danger-soft px-3.5 py-2.5 text-sm text-danger">{error}</p>}

      {/* Summary */}
      {meeting.status === 'processed' ? (
        <Card className="p-5">
          <div className="mb-3 flex items-center gap-2">
            <span className="grid size-7 place-items-center rounded-lg bg-navy-50 text-navy-700"><Icon name="Sparkles" className="size-4" /></span>
            <div className="flex flex-wrap items-center gap-2">
              <span className="text-sm font-bold text-ink">AI summary</span>
              {meeting.grounded && <Badge tone="emerald">Grounded in event</Badge>}
              <span className="text-xs text-muted">· {meeting.model}</span>
              {meeting.processed_at && <span className="text-xs text-muted">· {formatRelative(meeting.processed_at)}</span>}
            </div>
          </div>
          <Markdown content={meeting.summary} />
        </Card>
      ) : (
        <Card className="p-5">
          <p className="text-sm text-muted">This meeting hasn’t been processed yet. Run it through the copilot to get a summary and extract action items.</p>
        </Card>
      )}

      {/* Action items */}
      {items.length > 0 && (
        <div>
          <p className="mb-2 flex items-center gap-2 text-sm font-bold text-ink"><Icon name="ListPlus" className="size-4 text-navy-600" /> Action items</p>
          <Card className="divide-y divide-line p-0">
            {items.map((item) => {
              const done = item.status === 'done'
              return (
                <div key={item.id} className={cn('flex items-start gap-3 p-3.5', item.status === 'dismissed' && 'opacity-50')}>
                  <button
                    type="button"
                    title={done ? 'Mark open' : 'Mark done'}
                    disabled={busyItem === item.id}
                    onClick={() => toggleDone(item)}
                    className={cn('mt-0.5 shrink-0 text-muted transition-colors', done ? 'text-emerald-600' : 'hover:text-navy-700')}
                  >
                    <Icon name={done ? 'CheckCircle2' : 'Circle'} className="size-5" />
                  </button>
                  <div className="min-w-0 flex-1">
                    <p className={cn('text-sm text-ink', done && 'line-through text-muted')}>{item.description}</p>
                    <div className="mt-1 flex flex-wrap items-center gap-1.5">
                      {item.owner && <Badge tone="navy"><Icon name="User" className="mr-1 inline size-3" />{item.owner}</Badge>}
                      {item.due_date && <span className="text-xs text-muted">Due {item.due_date}</span>}
                      {item.converted && (
                        <Link to={`/dashboard/planner/events/${meeting.event_id}/tasks`} className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:underline">
                          <Icon name="Check" className="size-3" /> On task board
                        </Link>
                      )}
                    </div>
                  </div>
                  <div className="flex shrink-0 items-center gap-0.5">
                    {!item.converted && canConvert && (
                      <button
                        type="button"
                        title="Add to task board"
                        disabled={busyItem === item.id}
                        onClick={() => convert(item)}
                        className="grid size-7 place-items-center rounded-lg text-muted hover:bg-canvas hover:text-navy-700 disabled:opacity-40"
                      >
                        <Icon name={busyItem === item.id ? 'Loader2' : 'ListPlus'} className={cn('size-4', busyItem === item.id && 'animate-spin')} />
                      </button>
                    )}
                    {item.status !== 'dismissed' ? (
                      <button type="button" title="Dismiss" disabled={busyItem === item.id} onClick={() => patchItem(item, { status: 'dismissed' })}
                        className="grid size-7 place-items-center rounded-lg text-muted hover:bg-canvas hover:text-danger disabled:opacity-40">
                        <Icon name="X" className="size-4" />
                      </button>
                    ) : (
                      <button type="button" title="Restore" disabled={busyItem === item.id} onClick={() => patchItem(item, { status: 'open' })}
                        className="grid size-7 place-items-center rounded-lg text-muted hover:bg-canvas hover:text-navy-700 disabled:opacity-40">
                        <Icon name="RotateCcw" className="size-4" />
                      </button>
                    )}
                  </div>
                </div>
              )
            })}
          </Card>
          {!canConvert && items.some((i) => !i.converted) && (
            <p className="mt-2 text-xs text-muted">Link this meeting to an event to push action items onto a task board.</p>
          )}
        </div>
      )}

      {/* Raw notes */}
      <div>
        <button type="button" onClick={() => setShowNotes((s) => !s)} className="flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
          <Icon name={showNotes ? 'ChevronDown' : 'ChevronRight'} className="size-4" /> Original notes
        </button>
        {showNotes && (
          <Card className="mt-2 p-4">
            <pre className="whitespace-pre-wrap text-sm leading-relaxed text-ink">{meeting.notes}</pre>
          </Card>
        )}
      </div>
    </div>
  )
}
