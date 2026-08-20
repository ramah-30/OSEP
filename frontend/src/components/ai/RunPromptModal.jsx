import { useMemo, useState } from 'react'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import Modal from '../ui/Modal'
import ListboxSelect from '../ui/ListboxSelect'
import { api, parseApiError } from '../../lib/api'
import { humanizeVar } from '../../lib/ai'
import { useAiChat } from '../../context/AiChatContext'

/**
 * Fill a prompt's {{variables}}, pick the event to ground it in, and run it. The
 * server opens a fresh conversation and answers grounded in that event's live
 * data; we then pop the floating chat widget open on that conversation.
 */
export default function RunPromptModal({ prompt, events = [], onClose, onRan }) {
  const { open: openChat } = useAiChat()
  const vars = prompt.variables ?? []
  const [values, setValues] = useState(() => Object.fromEntries(vars.map((v) => [v, ''])))
  const [eventId, setEventId] = useState(prompt.event_id ? String(prompt.event_id) : '')
  const [running, setRunning] = useState(false)
  const [error, setError] = useState(null)

  const eventOptions = useMemo(
    () => [{ value: '', label: 'No event (general)' }, ...events.map((e) => ({ value: String(e.id), label: e.title }))],
    [events],
  )

  const run = async () => {
    setRunning(true); setError(null)
    try {
      const res = await api.post(`/ai/prompts/${prompt.id}/run`, {
        variables: values,
        event_id: eventId ? Number(eventId) : null,
      })
      const conversation = res.data.data.conversation
      onRan?.(conversation)
      onClose?.()
      openChat({ conversationId: conversation.id })
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setRunning(false)
    }
  }

  return (
    <Modal open onClose={onClose} title={`Run · ${prompt.name}`}>
      <div className="space-y-4">
        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink">Ground in event</label>
          <ListboxSelect options={eventOptions} value={eventId} onChange={(e) => setEventId(e.target.value)} />
          <p className="mt-1 text-xs text-muted">The reply is grounded in this event's live budget, timeline, guests and vendors.</p>
        </div>

        {vars.length > 0 && (
          <div className="space-y-3">
            <p className="text-sm font-medium text-ink">Fill in the details</p>
            {vars.map((v) => (
              <div key={v}>
                <label className="mb-1.5 block text-sm text-muted">{humanizeVar(v)}</label>
                <input
                  value={values[v] ?? ''}
                  onChange={(e) => setValues((s) => ({ ...s, [v]: e.target.value }))}
                  placeholder={`Leave blank to keep [${humanizeVar(v).toLowerCase()}]`}
                  className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
                />
              </div>
            ))}
          </div>
        )}

        {error && <p className="text-sm text-danger">{error}</p>}
        <div className="flex justify-end gap-2 pt-1">
          <Button size="sm" variant="ghost" onClick={onClose}>Cancel</Button>
          <Button size="sm" onClick={run} loading={running}><Icon name="Play" className="size-4" /> Run prompt</Button>
        </div>
      </div>
    </Modal>
  )
}
