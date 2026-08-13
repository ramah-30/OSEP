import { useState } from 'react'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import { api } from '../../lib/api'
import { useAiChat } from '../../context/AiChatContext'
import GenerateDocumentModal from './GenerateDocumentModal'

/**
 * Contextual "Ask OSEP AI" button. Drop it into any module and it opens the
 * floating copilot seeded with the current event and a starter prompt, so the
 * planner can ask about exactly what they are looking at.
 */
export function AskAiButton({ eventId = null, prompt = '', label = 'Ask AI', size = 'sm', variant = 'secondary', className }) {
  const { open } = useAiChat()

  return (
    <Button
      size={size}
      variant={variant}
      className={className}
      onClick={() => open({ eventId: eventId ?? undefined, prompt: prompt || undefined })}
    >
      <Icon name="Sparkles" className="size-4" /> {label}
    </Button>
  )
}

/**
 * Contextual "Generate with AI" button. Lazily loads the requested template plus
 * the planner's events, then opens the shared generate dialog locked to the
 * current event — so a module can offer the one document that fits it (an RSVP
 * reminder on the guest tab, a budget guide on the budget tab, and so on).
 */
export function GenerateAiButton({
  templateKey,
  eventId = null,
  label = 'Generate with AI',
  size = 'sm',
  variant = 'secondary',
  className,
  onGenerated,
}) {
  const [loading, setLoading] = useState(false)
  const [template, setTemplate] = useState(null)
  const [events, setEvents] = useState([])
  const [error, setError] = useState(null)

  const openDialog = async () => {
    setLoading(true)
    setError(null)
    try {
      const [t, m] = await Promise.all([api.get('/ai/templates'), api.get('/ai/meta')])
      const found = (t.data.data.templates ?? []).find((x) => x.key === templateKey)
      if (!found) { setError('Template unavailable.'); return }
      setEvents(m.data.data.events ?? [])
      setTemplate(found)
    } catch {
      setError('Could not start the generator.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <>
      <Button size={size} variant={variant} className={className} onClick={openDialog} loading={loading}>
        <Icon name="Wand2" className="size-4" /> {label}
      </Button>
      {error && <span className="ml-2 text-xs text-danger">{error}</span>}
      {template && (
        <GenerateDocumentModal
          template={template}
          events={events}
          lockedEventId={eventId}
          onClose={() => setTemplate(null)}
          onGenerated={onGenerated}
        />
      )}
    </>
  )
}
