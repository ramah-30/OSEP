import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import Icon from '../ui/Icon'
import Badge from '../ui/Badge'
import Spinner from '../ui/Spinner'
import ListboxSelect from '../ui/ListboxSelect'
import Markdown from './Markdown'
import ModeToggle from './ModeToggle'
import FeedbackButtons from './FeedbackButtons'
import ActionCard from './ActionCard'
import { api, parseApiError } from '../../lib/api'
import { formatRelative } from '../../lib/format'
import { cn } from '../../lib/cn'
import { useAiChat } from '../../context/AiChatContext'

const AGENT_LABELS = {
  budget: 'Budget agent', vendor: 'Vendor agent', guest: 'Guest agent',
  planning: 'Planning agent', analytics: 'Analytics agent', conversation: 'Copilot',
}

// An editable template for the "Create a wedding" prompt. Clicking drops this
// into the composer (rather than sending) so the planner replaces the bracketed
// placeholders with real details, then sends to create the event.
const CREATE_WEDDING_TEMPLATE =
  'Create a wedding called [couple names] on [December 20, 2026] from [15:00] to [23:00] ' +
  'for client [client email] with [150] guests, priority [high], budget [10,000,000], ' +
  'theme [theme name], description [a short description]'

// Starter prompts shown on an empty thread to get planners going fast. A prompt
// with `fill` pre-populates the composer for editing; otherwise it sends `text`.
const QUICK_PROMPTS = [
  { icon: 'CalendarPlus', label: 'Create a wedding', fill: CREATE_WEDDING_TEMPLATE },
  { icon: 'BellRing', label: 'Send RSVP reminders', text: 'Send RSVP reminders to guests still awaiting a response.' },
  { icon: 'Users', label: 'Guest & RSVP status', text: 'Give me a summary of guest and RSVP status across my active events.' },
  { icon: 'ListChecks', label: "What's next?", text: 'What are the most important tasks I should focus on next?' },
]

/**
 * The floating OSEP AI copilot. A launcher button in the corner opens a compact
 * chat window with full conversation history, event context and grounded
 * replies — available on every planner page.
 */
export default function AiChatWidget() {
  const { isOpen, seed, open, close, clearSeed } = useAiChat()

  const [meta, setMeta] = useState(null)
  const [conversations, setConversations] = useState([])
  const [historyOpen, setHistoryOpen] = useState(false)

  const [activeId, setActiveId] = useState(null)
  const [active, setActive] = useState(null)
  const [threadLoading, setThreadLoading] = useState(false)

  const [draftEventId, setDraftEventId] = useState('')
  const [body, setBody] = useState('')
  const [sending, setSending] = useState(false)
  const [error, setError] = useState(null)

  const scrollRef = useRef(null)
  const textareaRef = useRef(null)
  const loadedMeta = useRef(false)

  // Drop a template into the composer for the planner to edit, then send.
  const fillComposer = (text) => {
    setBody(text)
    requestAnimationFrame(() => {
      const el = textareaRef.current
      if (el) { el.focus(); el.setSelectionRange(0, 0); el.scrollTop = 0 }
    })
  }

  // ---- data loads --------------------------------------------------
  const loadMeta = useCallback(async () => {
    try {
      const r = await api.get('/ai/meta')
      setMeta(r.data.data)
    } catch { /* non-fatal */ }
  }, [])

  const loadList = useCallback(async () => {
    try {
      const r = await api.get('/ai/conversations')
      setConversations(r.data.data.conversations)
    } catch { /* keep list */ }
  }, [])

  const loadThread = useCallback(async (id) => {
    setThreadLoading(true); setError(null)
    try {
      const r = await api.get(`/ai/conversations/${id}`)
      setActive(r.data.data.conversation)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setThreadLoading(false)
    }
  }, [])

  // Lazy-load meta + history the first time the widget opens.
  useEffect(() => {
    if (isOpen && !loadedMeta.current) {
      loadedMeta.current = true
      loadMeta(); loadList()
    }
  }, [isOpen, loadMeta, loadList])

  useEffect(() => {
    if (activeId) loadThread(activeId)
  }, [activeId, loadThread])

  // Apply a seed (deep-link from elsewhere) once the widget is open.
  useEffect(() => {
    if (!isOpen || !seed) return
    if (seed.conversationId) {
      setHistoryOpen(false)
      setActiveId(seed.conversationId)
    } else {
      setActiveId(null)
      setActive({ id: null, title: 'New conversation', event_id: seed.eventId || null, messages: [] })
      setDraftEventId(seed.eventId ? String(seed.eventId) : '')
      if (seed.prompt) setBody(seed.prompt)
    }
    clearSeed()
  }, [isOpen, seed, clearSeed])

  // Keep pinned to the newest message.
  useEffect(() => {
    scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight, behavior: 'smooth' })
  }, [active, sending, isOpen])

  // Auto-grow the composer to fit its content (up to a cap) so a pasted/filled
  // template is fully visible; the messages area above flexes down to make room.
  useEffect(() => {
    const el = textareaRef.current
    if (!el) return
    el.style.height = 'auto'
    el.style.height = `${Math.min(el.scrollHeight, 200)}px`
  }, [body, isOpen])

  // ---- actions -----------------------------------------------------
  const startDraft = () => {
    setActiveId(null)
    setActive({ id: null, title: 'New conversation', event_id: draftEventId || null, messages: [] })
    setHistoryOpen(false)
    setError(null)
  }

  const send = async (e, override) => {
    e?.preventDefault()
    const text = (override ?? body).trim()
    if (!text || sending) return

    setActive((prev) => ({
      ...(prev ?? { id: null, messages: [] }),
      messages: [...(prev?.messages ?? []), { id: `tmp-${Date.now()}`, role: 'user', content: text }],
    }))
    setBody('')
    setSending(true)
    setError(null)

    try {
      const r = await api.post('/ai/chat', {
        message: text,
        conversation_id: activeId ?? undefined,
        event_id: activeId ? undefined : (draftEventId ? Number(draftEventId) : undefined),
      })
      const conv = r.data.data.conversation
      setActiveId(conv.id)
      await loadThread(conv.id)
      loadList()
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSending(false)
    }
  }

  const removeConversation = async (conv, ev) => {
    ev.stopPropagation()
    try {
      await api.delete(`/ai/conversations/${conv.id}`)
      if (activeId === conv.id) { setActiveId(null); setActive(null) }
      loadList()
    } catch { /* ignore */ }
  }

  const eventOptions = useMemo(
    () => [{ value: '', label: 'General (no event)' }, ...(meta?.events ?? []).map((e) => ({ value: String(e.id), label: e.title }))],
    [meta],
  )

  const messages = active?.messages ?? []
  const showSuggestions = messages.length === 0 && !threadLoading

  return (
    <>
      {/* Launcher */}
      <AnimatePresence>
        {!isOpen && (
          <motion.button
            type="button"
            onClick={() => open()}
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            exit={{ scale: 0, opacity: 0 }}
            transition={{ type: 'spring', stiffness: 400, damping: 25 }}
            className="fixed bottom-6 right-6 z-40 flex h-14 items-center gap-2 rounded-full bg-navy-800 pl-4 pr-5 text-white shadow-[0_16px_40px_-12px_rgba(30,58,138,0.7)] transition-colors hover:bg-navy-900"
            aria-label="Open OSEP AI"
          >
            <span className="grid size-8 place-items-center rounded-full bg-purple-500/90">
              <Icon name="Sparkles" className="size-5" />
            </span>
            <span className="text-sm font-semibold">Ask OSEP AI</span>
          </motion.button>
        )}
      </AnimatePresence>

      {/* Chat window */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, y: 24, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 24, scale: 0.98 }}
            transition={{ duration: 0.2, ease: [0.16, 1, 0.3, 1] }}
            className="fixed inset-x-0 bottom-0 z-50 flex h-[85dvh] flex-col overflow-hidden border border-line bg-surface shadow-2xl sm:inset-x-auto sm:bottom-6 sm:right-6 sm:h-[620px] sm:w-[400px] sm:rounded-3xl"
          >
            {/* Header */}
            <div className="flex items-center justify-between gap-2 border-b border-line bg-navy-800 px-4 py-3 text-white">
              <div className="flex items-center gap-2.5">
                <span className="grid size-9 place-items-center rounded-xl bg-purple-500/90">
                  <Icon name="Sparkles" className="size-5" />
                </span>
                <div>
                  <p className="text-sm font-bold leading-tight">{meta?.assistant_name ?? 'OSEP AI'}</p>
                  <p className="text-[11px] text-white/70">
                    {meta ? (meta.is_live ? `Live · ${meta.driver}` : 'Offline engine') : 'Your planning copilot'}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-0.5">
                <HeaderBtn icon="MessagesSquare" title="History" active={historyOpen} onClick={() => setHistoryOpen((v) => !v)} />
                <HeaderBtn icon="Plus" title="New chat" onClick={startDraft} />
                <HeaderBtn icon="X" title="Close" onClick={close} />
              </div>
            </div>

            {/* Mode: offline engine ↔ live model */}
            <div className="flex items-center justify-between gap-2 border-b border-line px-3 py-2">
              <span className="text-[11px] font-medium text-muted">Answering engine</span>
              <ModeToggle onChange={() => loadMeta()} />
            </div>

            {/* History drawer */}
            <AnimatePresence>
              {historyOpen && (
                <motion.div
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: 'auto', opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  className="overflow-hidden border-b border-line bg-canvas"
                >
                  <div className="max-h-52 overflow-y-auto p-2">
                    {conversations.length === 0 ? (
                      <p className="px-2 py-4 text-center text-xs text-muted">No conversations yet.</p>
                    ) : (
                      <ul className="space-y-0.5">
                        {conversations.map((conv) => (
                          <li key={conv.id}>
                            <button
                              type="button"
                              onClick={() => { setActiveId(conv.id); setHistoryOpen(false) }}
                              className={cn(
                                'group flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left transition-colors',
                                activeId === conv.id ? 'bg-navy-50' : 'hover:bg-surface',
                              )}
                            >
                              <Icon name={conv.event_id ? 'CalendarClock' : 'MessagesSquare'} className="size-3.5 shrink-0 text-muted" />
                              <span className="min-w-0 flex-1">
                                <span className="block truncate text-xs font-semibold text-ink">{conv.title}</span>
                                {conv.last_message_at && <span className="block text-[10px] text-muted">{formatRelative(conv.last_message_at)}</span>}
                              </span>
                              <span role="button" tabIndex={0} onClick={(e) => removeConversation(conv, e)} className="grid size-6 shrink-0 place-items-center rounded text-muted opacity-0 hover:text-danger group-hover:opacity-100">
                                <Icon name="Trash2" className="size-3.5" />
                              </span>
                            </button>
                          </li>
                        ))}
                      </ul>
                    )}
                  </div>
                </motion.div>
              )}
            </AnimatePresence>

            {/* Context selector for a fresh thread */}
            {!activeId && (
              <div className="flex items-center gap-2 border-b border-line px-3 py-2">
                <span className="text-[11px] font-medium text-muted">Context</span>
                <ListboxSelect
                  heightClass="h-8"
                  className="min-w-0 flex-1 text-xs"
                  options={eventOptions}
                  value={draftEventId}
                  onChange={(e) => { setDraftEventId(e.target.value); setActive((p) => ({ ...(p ?? {}), event_id: e.target.value || null })) }}
                />
              </div>
            )}
            {activeId && active?.event_title && (
              <div className="flex items-center gap-2 border-b border-line px-3 py-2">
                <Badge tone="navy">{active.event_title}</Badge>
              </div>
            )}

            {/* Messages */}
            <div ref={scrollRef} className="flex-1 space-y-3 overflow-y-auto px-3 py-4">
              {threadLoading && messages.length === 0 ? (
                <div className="grid h-full place-items-center"><Spinner className="size-6" /></div>
              ) : showSuggestions ? (
                <div className="flex h-full flex-col items-center justify-center">
                  <div className="px-2 text-center">
                    <span className="mx-auto grid size-12 place-items-center rounded-2xl bg-purple-50 text-purple-600">
                      <Icon name="Sparkles" className="size-6" />
                    </span>
                    <p className="mt-3 text-sm font-bold text-ink">How can I help?</p>
                    <p className="mt-1 text-xs text-muted">Grounded in your real event data.</p>
                  </div>
                  <div className="mt-5 w-full space-y-2">
                    <p className="px-1 text-[11px] font-semibold uppercase tracking-wide text-muted">Quick prompts</p>
                    {QUICK_PROMPTS.map((q) => (
                      <button
                        key={q.label}
                        type="button"
                        disabled={sending}
                        onClick={() => (q.fill ? fillComposer(q.fill) : send(null, q.text))}
                        className="flex w-full items-center gap-2.5 rounded-xl border border-line bg-canvas px-3 py-2.5 text-left text-sm text-ink transition-colors hover:border-navy-300 hover:bg-navy-50 disabled:opacity-50"
                      >
                        <span className="grid size-7 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-600">
                          <Icon name={q.icon} className="size-4" />
                        </span>
                        <span className="min-w-0 flex-1 font-medium">{q.label}</span>
                        <Icon name="ArrowRight" className="size-4 shrink-0 text-muted" />
                      </button>
                    ))}
                  </div>
                </div>
              ) : (
                messages.map((m) => (
                  <div key={m.id} className={cn('flex', m.role === 'user' ? 'justify-end' : 'justify-start')}>
                    <div
                      className={cn(
                        'max-w-[88%] rounded-2xl px-3.5 py-2.5 text-sm',
                        m.role === 'user' ? 'bg-navy-800 text-white' : 'border border-line bg-canvas text-ink',
                      )}
                    >
                      {m.role === 'assistant' && (
                        <div className="mb-1 flex items-center gap-1 text-[10px] font-semibold text-purple-600">
                          <Icon name="Sparkles" className="size-2.5" />
                          {AGENT_LABELS[m.agent] ?? 'Copilot'}
                        </div>
                      )}
                      {m.role === 'assistant'
                        ? <Markdown content={m.content} className="text-sm" />
                        : <p className="whitespace-pre-wrap leading-relaxed">{m.content}</p>}
                      {m.action && (
                        <ActionCard
                          action={m.action}
                          onChanged={() => activeId && loadThread(activeId)}
                          className="mt-2"
                        />
                      )}
                      {m.role === 'assistant' && typeof m.id === 'number' && (
                        <div className="mt-1.5 border-t border-line/60 pt-1.5">
                          <FeedbackButtons subjectType="message" subjectId={m.id} initialRating={m.my_rating ?? null} />
                        </div>
                      )}
                    </div>
                  </div>
                ))
              )}
              {sending && (
                <div className="flex justify-start">
                  <div className="flex items-center gap-2 rounded-2xl border border-line bg-canvas px-3.5 py-2.5 text-sm text-muted">
                    <Spinner className="size-4" /> Thinking…
                  </div>
                </div>
              )}
            </div>

            {/* Composer */}
            <form onSubmit={send} className="border-t border-line p-2.5">
              {error && <p className="mb-1.5 px-1 text-xs text-danger">{error}</p>}
              <div className="flex items-end gap-2">
                <textarea
                  ref={textareaRef}
                  value={body}
                  onChange={(e) => setBody(e.target.value)}
                  onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(e) } }}
                  rows={1}
                  placeholder="Ask about budget, guests, vendors…"
                  className="max-h-[200px] min-h-[42px] flex-1 resize-none overflow-y-auto rounded-xl border border-line bg-surface px-3 py-2.5 text-sm text-ink outline-none transition-colors focus:border-navy-300"
                />
                <button
                  type="submit"
                  disabled={!body.trim() || sending}
                  className="grid size-[42px] shrink-0 place-items-center rounded-xl bg-navy-800 text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                >
                  <Icon name="Send" className="size-4" />
                </button>
              </div>
            </form>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  )
}

function HeaderBtn({ icon, title, onClick, active }) {
  return (
    <button
      type="button"
      title={title}
      onClick={onClick}
      className={cn('grid size-8 place-items-center rounded-lg transition-colors', active ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white')}
    >
      <Icon name={icon} className="size-4" />
    </button>
  )
}
