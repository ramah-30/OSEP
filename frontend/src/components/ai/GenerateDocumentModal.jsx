import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import Icon from '../ui/Icon'
import Button from '../ui/Button'
import Modal from '../ui/Modal'
import { Field, SelectField } from '../ui/Field'
import Textarea from '../ui/Textarea'
import { api, parseApiError } from '../../lib/api'

/**
 * The shared "generate a document" dialog. Collects a template's variables plus
 * the event to ground on, POSTs to /ai/documents and (by default) opens the new
 * document. Used both by the Templates gallery and by the inline
 * "Generate with AI" buttons scattered across the module pages.
 *
 * Pass `lockedEventId` to pre-select (and, when the template requires an event,
 * fix) the grounding event — that is how the in-context module buttons keep the
 * document tied to the event the planner is looking at.
 */
export default function GenerateDocumentModal({ template, events = [], lockedEventId = null, onClose, onGenerated }) {
  const navigate = useNavigate()
  const [eventId, setEventId] = useState(
    lockedEventId != null ? String(lockedEventId) : (events[0] ? String(events[0].id) : ''),
  )
  const [values, setValues] = useState({})
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState(null)
  const [fieldErrors, setFieldErrors] = useState({})

  const lockEvent = lockedEventId != null
  const eventOptions = [
    { value: '', label: template.requires_event ? 'Choose an event…' : 'No event (general)' },
    ...events.map((e) => ({ value: String(e.id), label: e.title })),
  ]

  const setVal = (key, v) => setValues((prev) => ({ ...prev, [key]: v }))

  const submit = async () => {
    setBusy(true)
    setError(null)
    setFieldErrors({})
    try {
      const r = await api.post('/ai/documents', {
        template_key: template.key,
        event_id: eventId ? Number(eventId) : null,
        inputs: values,
      })
      const doc = r.data.data.document
      onClose?.()
      if (onGenerated) onGenerated(doc)
      else navigate(`/dashboard/planner/ai-assistant/documents/${doc.id}`)
    } catch (err) {
      const parsed = parseApiError(err)
      setError(parsed.message)
      setFieldErrors(parsed.errors ?? {})
    } finally {
      setBusy(false)
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title={`Generate: ${template.name}`}
      description={template.description}
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={busy}>Cancel</Button>
          <Button onClick={submit} loading={busy}>
            <Icon name="Sparkles" className="size-4" /> Generate
          </Button>
        </>
      }
    >
      <div className="space-y-4">
        {lockEvent ? (
          events.length > 0 && (
            <p className="rounded-btn border border-line bg-canvas px-3 py-2 text-sm text-muted">
              <Icon name="CalendarClock" className="mr-1.5 inline size-4 text-navy-600" />
              Grounded in <span className="font-semibold text-ink">{events.find((e) => String(e.id) === eventId)?.title ?? 'this event'}</span>.
            </p>
          )
        ) : (
          <SelectField
            label={template.requires_event ? 'Event (required)' : 'Ground in an event (optional)'}
            options={eventOptions}
            value={eventId}
            onChange={(e) => setEventId(e.target.value)}
            error={fieldErrors['event_id']?.[0]}
            hint={template.requires_event ? 'The document is grounded in this event’s real data.' : undefined}
          />
        )}

        {(template.variables ?? []).map((v) => {
          const err = fieldErrors[`inputs.${v.key}`]?.[0]
          const label = v.label + (v.required ? ' (required)' : '')
          if (v.type === 'select') {
            return (
              <SelectField
                key={v.key}
                label={label}
                options={[{ value: '', label: v.placeholder || 'Select…' }, ...(v.options ?? []).map((o) => ({ value: o, label: o }))]}
                value={values[v.key] ?? ''}
                onChange={(e) => setVal(v.key, e.target.value)}
                error={err}
              />
            )
          }
          if (v.type === 'textarea') {
            return (
              <Textarea
                key={v.key}
                label={label}
                placeholder={v.placeholder}
                value={values[v.key] ?? ''}
                onChange={(e) => setVal(v.key, e.target.value)}
                error={err}
              />
            )
          }
          return (
            <Field
              key={v.key}
              label={label}
              type={v.type === 'number' ? 'number' : 'text'}
              placeholder={v.placeholder}
              value={values[v.key] ?? ''}
              onChange={(e) => setVal(v.key, e.target.value)}
              error={err}
            />
          )
        })}

        {error && !Object.keys(fieldErrors).length && (
          <p className="text-sm text-danger">{error}</p>
        )}
      </div>
    </Modal>
  )
}
