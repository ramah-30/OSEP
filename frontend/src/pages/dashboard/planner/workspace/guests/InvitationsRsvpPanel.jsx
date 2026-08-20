import { useMemo, useState } from 'react'
import Button from '../../../../../components/ui/Button'
import Card from '../../../../../components/ui/Card'
import Badge from '../../../../../components/ui/Badge'
import Icon from '../../../../../components/ui/Icon'
import Alert from '../../../../../components/ui/Alert'
import { Field, SelectField } from '../../../../../components/ui/Field'
import Textarea from '../../../../../components/ui/Textarea'
import { Table, THead, TH, TBody, TR, TD } from '../../../../../components/ui/Table'
import LoadState from '../../../../../components/dashboard/LoadState'
import { useResource } from '../../../../../lib/useResource'
import { api } from '../../../../../lib/api'
import { formatRelative } from '../../../../../lib/format'
import { CHANNEL_OPTIONS, GATEWAY_CHANNELS, INVITATION_TONE, COMM_TYPE_META } from '../../../../../lib/guestConstants'
import { cn } from '../../../../../lib/cn'

const RESPONSE_TONE = { attending: 'emerald', not_attending: 'danger', maybe: 'amber' }

const LOWER_VIEWS = [
  { value: 'invitations', label: 'Invitations', icon: 'Mail' },
  { value: 'responses', label: 'Responses', icon: 'MailCheck' },
  { value: 'reminders', label: 'Reminders', icon: 'Bell' },
  { value: 'activity', label: 'Activity', icon: 'MessageSquare' },
]

const ACTIVITY_TYPES = [
  { value: '', label: 'All activity' },
  { value: 'invitation', label: 'Invitations' },
  { value: 'reminder', label: 'Reminders' },
  { value: 'rsvp', label: 'RSVP' },
  { value: 'checkin', label: 'Check-ins' },
  { value: 'note', label: 'Notes' },
]

/**
 * The whole send-and-track flow in one place: compose invitations OR reminders,
 * then review delivery, responses, reminders and the full activity log.
 */
export default function InvitationsRsvpPanel({ eventId }) {
  const { data: stats } = useResource(`/events/${eventId}/guests/dashboard`)
  const { data: guestData } = useResource(`/events/${eventId}/guests`)
  const { data: tplData } = useResource('/invitation-templates')
  const { data: invData, loading, error, reload: reloadInv } = useResource(`/events/${eventId}/invitations`)
  const { data: rsvpData } = useResource(`/events/${eventId}/rsvp`)
  const { data: remData, reload: reloadRem } = useResource(`/events/${eventId}/reminders`)

  const guests = guestData?.guests ?? []
  const templates = tplData?.templates ?? []
  const summary = invData?.summary

  const [mode, setMode] = useState('invitation')
  const [lower, setLower] = useState('invitations')

  // Shared compose fields
  const [channel, setChannel] = useState('whatsapp')
  const [scheduledFor, setScheduledFor] = useState('')
  const [body, setBody] = useState('')
  // Invitation-only
  const [audience, setAudience] = useState('not_invited')
  const [templateId, setTemplateId] = useState('')
  const [subject, setSubject] = useState('')
  // Reminder-only
  const [target, setTarget] = useState('pending')

  const [busy, setBusy] = useState(false)
  const [flash, setFlash] = useState(null)

  const categories = useMemo(() => [...new Set(guests.map((g) => g.category).filter(Boolean))], [guests])

  const targetIds = useMemo(() => {
    if (audience === 'all') return null
    if (audience === 'not_invited') return guests.filter((g) => g.invitation_status === 'draft').map((g) => g.id)
    return guests.filter((g) => g.category === audience).map((g) => g.id)
  }, [audience, guests])

  const targetCount = targetIds === null ? guests.length : targetIds.length
  const gatewayChannel = GATEWAY_CHANNELS.includes(channel)

  function pickTemplate(id) {
    setTemplateId(id)
    const tpl = templates.find((t) => String(t.id) === id)
    if (tpl) { setSubject(tpl.subject ?? ''); setBody(tpl.body ?? '') }
  }

  async function send() {
    setBusy(true); setFlash(null)
    try {
      if (mode === 'invitation') {
        const payload = {
          channel,
          ...(templateId ? { template_id: Number(templateId) } : {}),
          ...(subject ? { subject } : {}),
          ...(body ? { body } : {}),
          ...(scheduledFor ? { scheduled_for: new Date(scheduledFor).toISOString() } : {}),
          ...(targetIds === null ? { all: true } : { guest_ids: targetIds }),
        }
        const r = await api.post(`/events/${eventId}/invitations/send`, payload)
        setFlash({ tone: 'success', text: r.data.message })
        reloadInv()
        setLower('invitations')
      } else {
        const r = await api.post(`/events/${eventId}/reminders/send`, {
          target, channel,
          ...(body ? { body } : {}),
          ...(scheduledFor ? { scheduled_for: new Date(scheduledFor).toISOString() } : {}),
        })
        setFlash({ tone: 'success', text: r.data.message })
        reloadRem()
        setLower('reminders')
      }
    } catch (e) {
      setFlash({ tone: 'error', text: e.response?.data?.message ?? 'Could not send.' })
    } finally { setBusy(false) }
  }

  async function resend(id) {
    await api.post(`/events/${eventId}/invitations/${id}/resend`)
    reloadInv()
  }

  const isInvite = mode === 'invitation'

  return (
    <div className="space-y-5">
      <div>
        <h2 className="text-lg font-extrabold text-ink">Invitations &amp; RSVP</h2>
        <p className="text-sm text-muted">Send invitations and reminders, then track delivery and responses.</p>
      </div>

      {stats && (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <Stat label="Confirmed" value={stats.cards.confirmed} tone="text-emerald-600" />
          <Stat label="Declined" value={stats.cards.declined} tone="text-danger" />
          <Stat label="Pending" value={stats.cards.pending} tone="text-ink" />
          <Stat label="Response rate" value={`${stats.response_rate}%`} tone="text-navy-700" />
        </div>
      )}

      {/* Compose */}
      <Card className="space-y-4 p-5">
        <div className="flex items-center gap-1 rounded-btn border border-line bg-canvas p-1 w-fit">
          {[['invitation', 'Invitation'], ['reminder', 'Reminder']].map(([value, label]) => (
            <button key={value} type="button" onClick={() => setMode(value)}
              className={cn('rounded-[9px] px-4 py-1.5 text-sm font-semibold transition-colors',
                mode === value ? 'bg-navy-800 text-white' : 'text-muted hover:text-ink')}>
              {label}
            </button>
          ))}
        </div>

        {flash && <Alert tone={flash.tone}>{flash.text}</Alert>}

        <div className="grid gap-4 sm:grid-cols-2">
          {isInvite ? (
            <SelectField label="Audience" value={audience} onChange={(e) => setAudience(e.target.value)}>
              <option value="not_invited">Not yet invited</option>
              <option value="all">All guests</option>
              {categories.map((c) => <option key={c} value={c}>Category: {c}</option>)}
            </SelectField>
          ) : (
            <SelectField label="Recipients" value={target} onChange={(e) => setTarget(e.target.value)}>
              <option value="pending">Guests who haven't responded</option>
              <option value="all">All guests</option>
            </SelectField>
          )}
          <SelectField label="Channel" value={channel} onChange={(e) => setChannel(e.target.value)} options={CHANNEL_OPTIONS} />
          {isInvite && (
            <SelectField label="Template" value={templateId} onChange={(e) => pickTemplate(e.target.value)}>
              <option value="">Default message</option>
              {templates.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
            </SelectField>
          )}
          <Field type="datetime-local" label="Schedule for (optional)" value={scheduledFor} onChange={(e) => setScheduledFor(e.target.value)} />
        </div>

        {isInvite && (
          <Field label="Subject (optional)" value={subject} onChange={(e) => setSubject(e.target.value)} placeholder="Uses the template/default subject" />
        )}
        <Textarea label="Message (optional)" rows={4} value={body} onChange={(e) => setBody(e.target.value)}
          placeholder="Personalise with {{first_name}}, {{event}}. Leave blank to use the default." />

        {gatewayChannel && (
          <Alert tone="info">
            {channel === 'whatsapp' ? 'WhatsApp' : 'Message'} invitations open on each guest's device.
            Sends are recorded here for tracking — to open a chat with a single guest, use the
            {channel === 'whatsapp' ? ' “Invite via WhatsApp” ' : ' “Send message” '}action on the Guest List.
          </Alert>
        )}

        <div className="flex items-center justify-between">
          <p className="text-sm text-muted">
            {isInvite ? `${targetCount} recipient${targetCount === 1 ? '' : 's'}` : ''}
          </p>
          <Button onClick={send} loading={busy} disabled={isInvite && targetCount === 0}>
            <Icon name={scheduledFor ? 'CalendarClock' : (isInvite ? 'Send' : 'Bell')} className="size-4" />
            {scheduledFor ? 'Schedule' : (isInvite ? 'Send invitations' : 'Send reminder')}
          </Button>
        </div>
      </Card>

      {/* Summary + lower views */}
      {summary && (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
          {[['Total', summary.total], ['Sent', summary.sent], ['Delivered', summary.delivered], ['Opened', summary.opened], ['Scheduled', summary.scheduled], ['Failed', summary.failed]].map(([label, value]) => (
            <Card key={label} className="p-4"><p className="text-xs text-muted">{label}</p><p className="mt-0.5 text-xl font-extrabold text-ink tabular-nums">{value}</p></Card>
          ))}
        </div>
      )}

      <nav className="flex gap-1 overflow-x-auto rounded-xl border border-line bg-surface p-1">
        {LOWER_VIEWS.map((v) => (
          <button key={v.value} type="button" onClick={() => setLower(v.value)}
            className={cn('flex shrink-0 items-center gap-1.5 rounded-btn px-3 py-2 text-sm font-semibold transition-colors',
              lower === v.value ? 'bg-navy-800 text-white' : 'text-muted hover:bg-canvas hover:text-ink')}>
            <Icon name={v.icon} className="size-4" /> {v.label}
          </button>
        ))}
      </nav>

      {lower === 'invitations' && (
        <LoadState loading={loading} error={error} onRetry={reloadInv}>
          {invData && (invData.invitations.length ? (
            <Table>
              <THead><TR><TH>Guest</TH><TH>Channel</TH><TH>Status</TH><TH>Sent</TH><TH /></TR></THead>
              <TBody>
                {invData.invitations.map((inv) => (
                  <TR key={inv.id}>
                    <TD className="font-semibold">{inv.guest_name}{inv.kind === 'reminder' && <Badge tone="amber" className="ml-2">Reminder</Badge>}</TD>
                    <TD className="text-muted">{inv.channel_label}</TD>
                    <TD><Badge tone={INVITATION_TONE[inv.status] ?? 'muted'}>{inv.status_label}</Badge></TD>
                    <TD className="text-muted">{inv.sent_at ? formatRelative(inv.sent_at) : (inv.scheduled_for ? `Scheduled ${formatRelative(inv.scheduled_for)}` : '—')}</TD>
                    <TD><div className="flex justify-end"><Button size="sm" variant="ghost" onClick={() => resend(inv.id)}><Icon name="Send" className="size-4" /> Resend</Button></div></TD>
                  </TR>
                ))}
              </TBody>
            </Table>
          ) : <p className="py-8 text-center text-sm text-muted">No invitations yet.</p>)}
        </LoadState>
      )}

      {lower === 'responses' && (
        rsvpData && (rsvpData.responses.length ? (
          <Card className="p-5">
            <Table>
              <THead><TR><TH>Guest</TH><TH>Response</TH><TH>Party</TH><TH>Meal</TH><TH>Message</TH><TH>When</TH></TR></THead>
              <TBody>
                {rsvpData.responses.map((r) => (
                  <TR key={r.id}>
                    <TD className="font-semibold">{r.guest_name}</TD>
                    <TD><Badge tone={RESPONSE_TONE[r.response] ?? 'muted'}>{r.response_label}</Badge></TD>
                    <TD className="text-muted">{1 + r.additional_guests}</TD>
                    <TD className="text-muted">{r.meal_choice ?? '—'}</TD>
                    <TD className="max-w-xs truncate text-muted">{r.message ?? '—'}</TD>
                    <TD className="text-muted">{formatRelative(r.responded_at)}</TD>
                  </TR>
                ))}
              </TBody>
            </Table>
          </Card>
        ) : <p className="py-8 text-center text-sm text-muted">No responses yet.</p>)
      )}

      {lower === 'reminders' && (
        remData && (remData.reminders?.length ? (
          <Card className="p-5">
            <Table>
              <THead><TR><TH>Guest</TH><TH>Status</TH><TH>When</TH></TR></THead>
              <TBody>
                {remData.reminders.map((r) => (
                  <TR key={r.id}>
                    <TD className="font-semibold">{r.guest_name}</TD>
                    <TD><Badge tone={INVITATION_TONE[r.status] ?? 'muted'}>{r.status_label}</Badge></TD>
                    <TD className="text-muted">{r.sent_at ? formatRelative(r.sent_at) : (r.scheduled_for ? `Scheduled ${formatRelative(r.scheduled_for)}` : '—')}</TD>
                  </TR>
                ))}
              </TBody>
            </Table>
          </Card>
        ) : <p className="py-8 text-center text-sm text-muted">No reminders sent yet.</p>)
      )}

      {lower === 'activity' && <ActivityLog eventId={eventId} />}
    </div>
  )
}

function Stat({ label, value, tone }) {
  return (
    <Card className="p-4">
      <p className="text-xs text-muted">{label}</p>
      <p className={cn('mt-0.5 text-xl font-extrabold tabular-nums', tone)}>{value}</p>
    </Card>
  )
}

function ActivityLog({ eventId }) {
  const [type, setType] = useState('')
  const { data, loading, error, reload } = useResource(`/events/${eventId}/communications${type ? `?type=${type}` : ''}`)
  const logs = data?.logs ?? []

  return (
    <div className="space-y-3">
      <div className="flex justify-end">
        <SelectField className="max-w-xs" value={type} onChange={(e) => setType(e.target.value)} options={ACTIVITY_TYPES} />
      </div>
      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (logs.length ? (
          <Card className="p-5">
            <ul className="space-y-4">
              {logs.map((log) => {
                const meta = COMM_TYPE_META[log.type] ?? COMM_TYPE_META.system
                return (
                  <li key={log.id} className="flex gap-3">
                    <span className="mt-0.5 grid size-9 shrink-0 place-items-center rounded-full bg-canvas">
                      <Icon name={meta.icon} className="size-4 text-muted" />
                    </span>
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <p className="text-sm font-semibold text-ink">
                          {log.title}
                          {log.guest_name && <span className="ml-1.5 font-normal text-muted">· {log.guest_name}</span>}
                        </p>
                        <span className="text-xs text-muted">{formatRelative(log.created_at)}</span>
                      </div>
                      {log.detail && <p className="text-sm text-muted">{log.detail}</p>}
                    </div>
                  </li>
                )
              })}
            </ul>
          </Card>
        ) : <p className="py-10 text-center text-sm text-muted">No communication logged yet.</p>)}
      </LoadState>
    </div>
  )
}
