import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import Modal from '../../../../components/ui/Modal'
import EmptyState from '../../../../components/ui/EmptyState'
import LoadState from '../../../../components/dashboard/LoadState'
import ListboxSelect from '../../../../components/ui/ListboxSelect'
import ActionCard from '../../../../components/ai/ActionCard'
import { api, parseApiError } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'
import { formatRelative } from '../../../../lib/format'

const BASE = '/dashboard/planner/ai-assistant'
const ACTION_META = {
  recommend: { icon: 'Sparkles', label: 'Raise a recommendation', tone: 'purple' },
  draft_document: { icon: 'FileText', label: 'Draft a document', tone: 'navy' },
  send_rsvp_reminders: { icon: 'BellRing', label: 'Send RSVP reminders', tone: 'navy' },
  send_invitations: { icon: 'Send', label: 'Send invitations', tone: 'navy' },
  create_tasks: { icon: 'ListChecks', label: 'Add the planning checklist', tone: 'emerald' },
  flag: { icon: 'Bell', label: 'Flag it', tone: 'muted' },
}

const blankForm = (triggers) => ({
  name: '',
  event_id: '',
  trigger_type: triggers[0]?.value ?? 'budget_over',
  threshold: triggers[0]?.default ?? 0,
  action_type: 'recommend',
})

export default function AiAutomation() {
  const [data, setData] = useState(null)
  const [events, setEvents] = useState([])
  const [queue, setQueue] = useState({ pending: [], recent: [] })
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [running, setRunning] = useState(false)
  const [banner, setBanner] = useState(null)

  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(null)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)

  const load = useCallback(async () => {
    setLoading(true); setError(null)
    try {
      const [a, meta, q] = await Promise.all([api.get('/ai/automation'), api.get('/ai/meta'), api.get('/ai/actions')])
      setData(a.data.data)
      setEvents(meta.data.data.events ?? [])
      setQueue(q.data.data)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [])

  const loadQueue = useCallback(async () => {
    try {
      const q = await api.get('/ai/actions')
      setQueue(q.data.data)
    } catch { /* keep queue */ }
  }, [])

  useEffect(() => { load() }, [load])

  const triggers = data?.options?.triggers ?? []
  const actions = data?.options?.actions ?? []
  const rules = data?.rules ?? []
  const runs = data?.runs ?? []

  const runNow = async () => {
    setRunning(true); setBanner(null)
    try {
      const r = await api.post('/ai/automation/run')
      setBanner({ tone: r.data.data.fired.length ? 'emerald' : 'muted', text: r.data.message })
      setData((d) => ({ ...d, runs: r.data.data.runs }))
      load()
    } catch (err) {
      setBanner({ tone: 'danger', text: parseApiError(err).message })
    } finally {
      setRunning(false)
    }
  }

  const openNew = () => { setEditing(null); setForm(blankForm(triggers)); setFormError(null); setOpen(true) }
  const openEdit = (rule) => {
    setEditing(rule)
    setForm({ name: rule.name, event_id: rule.event_id ? String(rule.event_id) : '', trigger_type: rule.trigger_type, threshold: rule.threshold ?? '', action_type: rule.action_type })
    setFormError(null); setOpen(true)
  }

  const save = async () => {
    setSaving(true); setFormError(null)
    const payload = {
      name: form.name.trim(),
      event_id: form.event_id ? Number(form.event_id) : null,
      trigger_type: form.trigger_type,
      threshold: form.threshold === '' ? null : Number(form.threshold),
      action_type: form.action_type,
    }
    try {
      if (editing) await api.put(`/ai/automation/${editing.id}`, payload)
      else await api.post('/ai/automation', payload)
      setOpen(false)
      load()
    } catch (err) {
      setFormError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  const toggle = async (rule) => {
    try {
      await api.put(`/ai/automation/${rule.id}`, { enabled: !rule.enabled })
      setData((d) => ({ ...d, rules: d.rules.map((r) => r.id === rule.id ? { ...r, enabled: !r.enabled } : r) }))
    } catch { /* ignore */ }
  }

  const remove = async (rule) => {
    try {
      await api.delete(`/ai/automation/${rule.id}`)
      setData((d) => ({ ...d, rules: d.rules.filter((r) => r.id !== rule.id) }))
    } catch { /* ignore */ }
  }

  const currentTrigger = useMemo(() => triggers.find((t) => t.value === form?.trigger_type), [triggers, form])
  const eventOptions = useMemo(() => [{ value: '', label: 'All active events' }, ...events.map((e) => ({ value: String(e.id), label: e.title }))], [events])

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold text-ink">Automation</h2>
          <p className="text-sm text-muted">When a condition is met, the copilot acts — raises a recommendation, drafts a document, or flags it.</p>
        </div>
        <div className="flex items-center gap-2">
          <Button size="sm" variant="secondary" onClick={runNow} loading={running}><Icon name="Zap" className="size-4" /> Run now</Button>
          <Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> New rule</Button>
        </div>
      </div>

      {banner && (
        <div className={cn('flex items-center gap-2 rounded-xl border px-3.5 py-2.5 text-sm',
          banner.tone === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
          : banner.tone === 'danger' ? 'border-danger/30 bg-danger-soft text-danger'
          : 'border-line bg-canvas text-muted')}>
          <Icon name="Zap" className="size-4" /> {banner.text}
        </div>
      )}

      {queue.pending.length > 0 && (
        <div>
          <p className="mb-2 flex items-center gap-2 text-sm font-bold text-ink">
            <Icon name="Inbox" className="size-4 text-navy-600" /> Waiting for your approval
            <Badge tone="amber">{queue.pending.length}</Badge>
          </p>
          <div className="grid gap-3 lg:grid-cols-2">
            {queue.pending.map((a) => (
              <ActionCard key={a.id} action={a} onChanged={loadQueue} />
            ))}
          </div>
        </div>
      )}

      <LoadState loading={loading} error={error} onRetry={load}>
        {rules.length === 0 ? (
          <EmptyState
            icon="Zap"
            title="No automation rules yet"
            description="Create a rule like “when 5+ guests are still awaiting RSVP, draft a reminder” and the copilot will act for you."
            action={<Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> Create your first rule</Button>}
          />
        ) : (
          <div className="grid gap-3 lg:grid-cols-2">
            {rules.map((rule) => {
              const am = ACTION_META[rule.action_type] ?? ACTION_META.flag
              return (
                <Card key={rule.id} className={cn('group p-4', !rule.enabled && 'opacity-60')}>
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <p className="font-bold text-ink">{rule.name}</p>
                      <p className="mt-0.5 text-sm text-muted">
                        When <span className="font-medium text-ink">{rule.trigger_label.toLowerCase()} {formatThreshold(rule)}</span>
                      </p>
                    </div>
                    <Switch on={rule.enabled} onClick={() => toggle(rule)} />
                  </div>
                  <div className="mt-3 flex flex-wrap items-center gap-2">
                    <Badge tone={am.tone === 'muted' ? 'muted' : am.tone}><Icon name={am.icon} className="mr-1 inline size-3" />{am.label}</Badge>
                    <Badge tone="navy">{rule.scope === 'all' ? 'All events' : (rule.event_title ?? 'Event')}</Badge>
                    {rule.runs_count > 0 && <span className="text-xs text-muted">· fired {rule.runs_count}×</span>}
                    <div className="ml-auto flex items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                      <IconBtn icon="Pencil" title="Edit" onClick={() => openEdit(rule)} />
                      <IconBtn icon="Trash2" title="Delete" danger onClick={() => remove(rule)} />
                    </div>
                  </div>
                  {rule.last_fired_at && <p className="mt-2 text-[11px] text-muted">Last fired {formatRelative(rule.last_fired_at)}</p>}
                </Card>
              )
            })}
          </div>
        )}

        {/* Activity feed */}
        {runs.length > 0 && (
          <div>
            <p className="mb-2 mt-6 flex items-center gap-2 text-sm font-bold text-ink"><Icon name="Activity" className="size-4 text-navy-600" /> Recent activity</p>
            <Card className="divide-y divide-line p-0">
              {runs.map((run) => {
                const am = ACTION_META[run.action_type] ?? ACTION_META.flag
                const href = run.result_type === 'document' ? `${BASE}/documents/${run.result_id}`
                  : run.result_type === 'recommendation' ? `${BASE}/recommendations` : null
                return (
                  <div key={run.id} className="flex items-start gap-3 p-3.5">
                    <span className="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-canvas text-navy-700"><Icon name={am.icon} className="size-4" /></span>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm text-ink"><span className="font-semibold">{run.rule_name}</span> · {run.event_title}</p>
                      <p className="text-xs text-muted">{run.summary}</p>
                    </div>
                    <div className="flex shrink-0 flex-col items-end gap-1">
                      <span className="text-[11px] text-muted">{formatRelative(run.created_at)}</span>
                      {href && <Link to={href} className="text-xs font-semibold text-navy-700 hover:underline">{run.result_type === 'document' ? 'View doc' : 'View'}</Link>}
                    </div>
                  </div>
                )
              })}
            </Card>
          </div>
        )}
      </LoadState>

      {open && form && (
        <Modal open onClose={() => setOpen(false)} title={editing ? 'Edit rule' : 'New automation rule'}>
          <div className="space-y-4">
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">Rule name</label>
              <input
                value={form.name}
                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                placeholder="e.g. Chase pending RSVPs"
                className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
              />
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">Applies to</label>
              <ListboxSelect options={eventOptions} value={form.event_id} onChange={(e) => setForm((f) => ({ ...f, event_id: e.target.value }))} />
            </div>
            <div className="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
              <div>
                <label className="mb-1.5 block text-sm font-medium text-ink">When…</label>
                <ListboxSelect
                  options={triggers.map((t) => ({ value: t.value, label: t.label }))}
                  value={form.trigger_type}
                  onChange={(e) => {
                    const t = triggers.find((x) => x.value === e.target.value)
                    setForm((f) => ({ ...f, trigger_type: e.target.value, threshold: t?.default ?? f.threshold }))
                  }}
                />
              </div>
              <div className="w-32">
                <label className="mb-1.5 block text-sm font-medium text-ink">Threshold</label>
                <div className="relative">
                  <input
                    type="number"
                    value={form.threshold}
                    onChange={(e) => setForm((f) => ({ ...f, threshold: e.target.value }))}
                    className="h-11 w-full rounded-xl border border-line bg-surface pl-3.5 pr-12 text-sm text-ink outline-none focus:border-navy-300"
                  />
                  <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">{currentTrigger?.unit}</span>
                </div>
              </div>
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">…the copilot will</label>
              <ListboxSelect options={actions.map((a) => ({ value: a.value, label: a.label }))} value={form.action_type} onChange={(e) => setForm((f) => ({ ...f, action_type: e.target.value }))} />
              {form.action_type === 'draft_document' && (
                <p className="mt-1.5 text-xs text-muted">Drafts the most relevant template for this trigger, grounded in the event.</p>
              )}
            </div>
            {formError && <p className="text-sm text-danger">{formError}</p>}
            <div className="flex justify-end gap-2 pt-1">
              <Button size="sm" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
              <Button size="sm" onClick={save} loading={saving} disabled={!form.name.trim()}>{editing ? 'Save changes' : 'Create rule'}</Button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  )
}

function formatThreshold(rule) {
  if (rule.threshold == null) return ''
  const n = Number.isInteger(rule.threshold) ? rule.threshold : rule.threshold
  return rule.unit === 'TZS' ? `TZS ${Number(n).toLocaleString()}` : `${n} ${rule.unit}`.trim()
}

function Switch({ on, onClick }) {
  return (
    <button
      type="button"
      onClick={onClick}
      title={on ? 'Enabled' : 'Disabled'}
      className={cn('relative h-6 w-11 shrink-0 rounded-full transition-colors', on ? 'bg-emerald-500' : 'bg-line')}
    >
      <span className={cn('absolute top-0.5 size-5 rounded-full bg-white shadow transition-transform', on ? 'translate-x-[22px]' : 'translate-x-0.5')} />
    </button>
  )
}

function IconBtn({ icon, title, onClick, danger }) {
  return (
    <button
      type="button"
      title={title}
      onClick={onClick}
      className={cn('grid size-7 place-items-center rounded-lg text-muted hover:bg-canvas', danger ? 'hover:text-danger' : 'hover:text-navy-700')}
    >
      <Icon name={icon} className="size-4" />
    </button>
  )
}
