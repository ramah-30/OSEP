import { useCallback, useEffect, useRef, useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import Icon from '../ui/Icon'
import Spinner from '../ui/Spinner'
import Markdown from './Markdown'
import ModeToggle from './ModeToggle'
import RoleActionCard from './RoleActionCard'
import { api } from '../../lib/api'
import { cn } from '../../lib/cn'
import { formatRelative } from '../../lib/format'
import { useAiChat } from '../../context/AiChatContext'

/**
 * Endpoint bases + copy for each role copilot. Vendor and client never coexist
 * for one user, so a single widget (driven by the shared AiChatContext) serves
 * whichever role's dashboard is mounted.
 */
export const ROLE_AI_CONFIG = {
  vendor: {
    base: '/marketplace/vendor/ai',
    defaultName: 'OSEP Vendor Copilot',
    launcher: 'Ask OSEP AI',
    placeholder: 'Ask your business copilot...',
    intro: 'Ask about your pipeline, quotes, revenue, reviews or availability.',
  },
  client: {
    base: '/client/ai',
    defaultName: 'OSEP Planning Concierge',
    launcher: 'Ask OSEP AI',
    placeholder: 'Ask your concierge...',
    intro: 'Ask about your event, approvals, payments, guests or updates from your planner.',
  },
}

/**
 * A floating role copilot (vendor or client) mirroring the planner's
 * AiChatWidget: a corner launcher opens a compact chat window grounded in the
 * role's own offline endpoints, with full conversation history - reopen a past
 * thread or delete it - available on every page of the role's workspace.
 */
export default function RoleAiChatWidget({ config }) {
  const { base, launcher, defaultName, placeholder, intro } = config
  const { isOpen, seed, open, close, clearSeed } = useAiChat()

  const [meta, setMeta] = useState(null)
  const [loadedMeta, setLoadedMeta] = useState(false)
  const [conversations, setConversations] = useState([])
  const [historyOpen, setHistoryOpen] = useState(false)
  const [conversationId, setConversationId] = useState(null)
  const [messages, setMessages] = useState([])
  const [threadLoading, setThreadLoading] = useState(false)
  const [body, setBody] = useState('')
  const [sending, setSending] = useState(false)
  const [events, setEvents] = useState([])
  const [selectedEventId, setSelectedEventId] = useState(null)
  const scrollRef = useRef(null)
  const inputRef = useRef(null)

  const loadConversations = useCallback(() => {
    api.get(`${base}/conversations`).then((r) => setConversations(r.data.data.conversations)).catch(() => {})
  }, [base])

  // Lazy-load meta + history the first time it opens.
  useEffect(() => {
    if (isOpen && !loadedMeta) {
      setLoadedMeta(true)
      api.get(`${base}/meta`).then((r) => {
        setMeta(r.data.data)
        if (r.data.data?.events && r.data.data.events.length > 0) {
          setEvents(r.data.data.events)
          setSelectedEventId(r.data.data.events[0].id)
        }
      }).catch(() => {})
      loadConversations()
    }
  }, [isOpen, loadedMeta, base, loadConversations])

  // A page can pop the widget open pre-filled with a prompt via open({ prompt }).
  useEffect(() => {
    if (isOpen && seed?.prompt) {
      setBody(seed.prompt)
      clearSeed()
      requestAnimationFrame(() => inputRef.current?.focus())
    }
  }, [isOpen, seed, clearSeed])

  useEffect(() => {
    scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight, behavior: 'smooth' })
  }, [messages, sending, isOpen, threadLoading])

  // Map a stored AiMessage into the widget's lightweight shape.
  const toMessage = (m) => ({ id: m.id, role: m.role, content: m.content, action: m.action ?? null })

  const openThread = async (id) => {
    setHistoryOpen(false)
    setConversationId(id)
    setThreadLoading(true)
    try {
      const r = await api.get(`${base}/conversations/${id}`)
      setMessages((r.data.data.conversation.messages ?? []).map(toMessage))
    } catch {
      setMessages([])
    } finally {
      setThreadLoading(false)
    }
  }

  const send = async (text) => {
    const message = (text ?? body).trim()
    if (!message || sending) return
    setBody('')
    setMessages((m) => [...m, { role: 'user', content: message }])
    setSending(true)
    try {
      const r = await api.post(`${base}/chat`, {
        message,
        conversation_id: conversationId,
        event_id: selectedEventId,
      })
      setConversationId(r.data.data.conversation.id)
      const msg = r.data.data.message
      setMessages((m) => [...m, { id: msg.id, role: 'assistant', content: msg.content, action: msg.action }])
      loadConversations()
    } catch {
      setMessages((m) => [...m, { role: 'assistant', content: 'Sorry - I couldn\'t answer that just now. Please try again.' }])
    } finally {
      setSending(false)
    }
  }

  const resolveAction = async (idx, id, approve) => {
    try {
      const r = await api.post(`${base}/actions/${id}/${approve ? 'approve' : 'reject'}`)
      const status = r.data.data.action.status
      setMessages((m) => m.map((msg, i) => (i === idx ? { ...msg, action: { ...msg.action, status } } : msg)))
      setMessages((m) => [...m, { role: 'assistant', content: r.data.message || (approve ? 'Done.' : 'Dismissed.') }])
    } catch {
      setMessages((m) => [...m, { role: 'assistant', content: "That didn't go through - please try again." }])
    }
  }

  const deleteConversation = async (conv, e) => {
    e.stopPropagation()
    try {
      await api.delete(`${base}/conversations/${conv.id}`)
      if (conversationId === conv.id) startNew()
      loadConversations()
    } catch { /* ignore */ }
  }

  const startNew = () => { setMessages([]); setConversationId(null); setBody(''); setHistoryOpen(false) }

  const name = meta?.assistant_name ?? defaultName
  const prompts = meta?.suggested_prompts ?? []

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
            aria-label={launcher}
          >
            <span className="grid size-8 place-items-center rounded-full bg-purple-500/90">
              <Icon name="Sparkles" className="size-5" />
            </span>
            <span className="text-sm font-semibold">{launcher}</span>
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
                  <p className="text-sm font-bold leading-tight">{name}</p>
                  <p className="text-[11px] text-white/70">
                    {meta ? (meta.is_live ? `Live · ${meta.driver}` : 'Offline engine') : 'Your copilot'}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-0.5">
                <HeaderBtn icon="MessagesSquare" title="History" active={historyOpen} onClick={() => setHistoryOpen((v) => !v)} />
                <HeaderBtn icon="Plus" title="New chat" onClick={startNew} />
                <HeaderBtn icon="X" title="Close" onClick={close} />
              </div>
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
                              onClick={() => openThread(conv.id)}
                              className={cn(
                                'group flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left transition-colors',
                                conversationId === conv.id ? 'bg-navy-50' : 'hover:bg-surface',
                              )}
                            >
                              <Icon name="MessagesSquare" className="size-3.5 shrink-0 text-muted" />
                              <span className="min-w-0 flex-1">
                                <span className="block truncate text-xs font-semibold text-ink">{conv.title}</span>
                                {conv.last_message_at && <span className="block text-[10px] text-muted">{formatRelative(conv.last_message_at)}</span>}
                              </span>
                              <span
                                role="button"
                                tabIndex={0}
                                onClick={(e) => deleteConversation(conv, e)}
                                className="grid size-6 shrink-0 place-items-center rounded text-muted opacity-0 hover:text-danger group-hover:opacity-100"
                                title="Delete conversation"
                              >
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

            {/* Mode: offline engine ↔ live model */}
            <div className="flex items-center justify-between gap-2 border-b border-line px-3 py-2">
              <span className="text-[11px] font-medium text-muted">Answering engine</span>
              <ModeToggle base={base} onChange={() => api.get(`${base}/meta`).then((r) => setMeta(r.data.data)).catch(() => {})} />
            </div>

            {/* Event context selector (client only) */}
            {events.length > 0 && (
              <div className="border-b border-line px-3 py-2">
                <label className="block text-[11px] font-medium text-muted mb-1.5">Ask about</label>
                <select
                  value={selectedEventId || ''}
                  onChange={(e) => setSelectedEventId(Number(e.target.value) || null)}
                  className="w-full rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-ink outline-none transition-colors focus:border-navy-300"
                >
                  <option value="">General questions</option>
                  {events.map((evt) => (
                    <option key={evt.id} value={evt.id}>
                      {evt.title} ({evt.date})
                    </option>
                  ))}
                </select>
              </div>
            )}

            {/* Messages */}
            <div ref={scrollRef} className="flex-1 space-y-3 overflow-y-auto px-3 py-4">
              {threadLoading ? (
                <div className="grid h-full place-items-center"><Spinner className="size-6" /></div>
              ) : messages.length === 0 ? (
                <div className="flex h-full flex-col items-center justify-center">
                  <div className="px-2 text-center">
                    <span className="mx-auto grid size-12 place-items-center rounded-2xl bg-purple-50 text-purple-600">
                      <Icon name="Sparkles" className="size-6" />
                    </span>
                    <p className="mt-3 text-sm font-bold text-ink">How can I help?</p>
                    <p className="mt-1 text-xs text-muted">{intro}</p>
                  </div>
                  {prompts.length > 0 && (
                    <div className="mt-5 w-full space-y-2">
                      <p className="px-1 text-[11px] font-semibold uppercase tracking-wide text-muted">Quick prompts</p>
                      {prompts.map((p) => (
                        <button
                          key={p}
                          type="button"
                          disabled={sending}
                          onClick={() => send(p)}
                          className="flex w-full items-center gap-2.5 rounded-xl border border-line bg-canvas px-3 py-2.5 text-left text-sm text-ink transition-colors hover:border-navy-300 hover:bg-navy-50 disabled:opacity-50"
                        >
                          <span className="grid size-7 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-600">
                            <Icon name="Sparkles" className="size-4" />
                          </span>
                          <span className="min-w-0 flex-1 font-medium">{p}</span>
                          <Icon name="ArrowRight" className="size-4 shrink-0 text-muted" />
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              ) : (
                messages.map((m, i) => (
                  <div key={m.id ?? i} className={cn('flex flex-col gap-2', m.role === 'user' ? 'items-end' : 'items-start')}>
                    <div
                      className={cn(
                        'max-w-[88%] rounded-2xl px-3.5 py-2.5 text-sm',
                        m.role === 'user' ? 'bg-navy-800 text-white' : 'border border-line bg-canvas text-ink',
                      )}
                    >
                      {m.role === 'assistant'
                        ? <Markdown content={m.content} className="text-sm" />
                        : <p className="whitespace-pre-wrap leading-relaxed">{m.content}</p>}
                    </div>
                    {m.action && (
                      <RoleActionCard action={m.action} onResolve={(approve) => resolveAction(i, m.action.id, approve)} />
                    )}
                  </div>
                ))
              )}
              {sending && (
                <div className="flex justify-start">
                  <div className="flex items-center gap-2 rounded-2xl border border-line bg-canvas px-3.5 py-2.5 text-sm text-muted">
                    <Spinner className="size-4" /> Thinking...
                  </div>
                </div>
              )}
            </div>

            {/* Composer */}
            <form onSubmit={(e) => { e.preventDefault(); send() }} className="border-t border-line p-2.5">
              <div className="flex items-end gap-2">
                <input
                  ref={inputRef}
                  value={body}
                  onChange={(e) => setBody(e.target.value)}
                  placeholder={placeholder}
                  className="h-[42px] flex-1 rounded-xl border border-line bg-surface px-3 text-sm text-ink outline-none transition-colors focus:border-navy-300"
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
