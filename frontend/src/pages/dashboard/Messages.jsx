import { useCallback, useEffect, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import PageHeader from '../../components/ui/PageHeader'
import Card from '../../components/ui/Card'
import Icon from '../../components/ui/Icon'
import Avatar from '../../components/ui/Avatar'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'
import EmptyState from '../../components/ui/EmptyState'
import Modal from '../../components/ui/Modal'
import { api, parseApiError } from '../../lib/api'
import { useNotifications } from '../../context/NotificationsContext'
import { formatRelative } from '../../lib/format'
import { cn } from '../../lib/cn'

export default function Messages() {
  const { t } = useTranslation()
  const { load: reloadNotifications } = useNotifications()
  const [searchParams, setSearchParams] = useSearchParams()

  const [conversations, setConversations] = useState([])
  const [listLoading, setListLoading] = useState(true)
  const [activeId, setActiveId] = useState(null)
  const [active, setActive] = useState(null) // { id, participant, messages }
  const [threadLoading, setThreadLoading] = useState(false)
  const [body, setBody] = useState('')
  const [sending, setSending] = useState(false)
  const [error, setError] = useState(null)

  const [contactsOpen, setContactsOpen] = useState(false)
  const [contacts, setContacts] = useState([])
  const [contactsLoading, setContactsLoading] = useState(false)

  const scrollRef = useRef(null)
  const lastCountRef = useRef(0)

  const loadList = useCallback(async () => {
    try {
      const res = await api.get('/conversations')
      setConversations(res.data.data.conversations)
    } catch {
      /* keep the current list on a transient error */
    } finally {
      setListLoading(false)
    }
  }, [])

  const loadThread = useCallback(async (id, { silent = false } = {}) => {
    if (!silent) setThreadLoading(true)
    try {
      const res = await api.get(`/conversations/${id}`)
      setActive(res.data.data.conversation)
      reloadNotifications?.()
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setThreadLoading(false)
    }
  }, [reloadNotifications])

  // Open (or create) a thread with a specific user — used by "Message" buttons
  // across the app via ?to=<userId>.
  const startWith = useCallback(async (recipientId) => {
    setError(null)
    try {
      const res = await api.post('/conversations', { recipient_id: recipientId })
      const conv = res.data.data.conversation
      setActiveId(conv.id)
      loadList()
      return conv
    } catch (err) {
      setError(parseApiError(err).message)
      return null
    }
  }, [loadList])

  // Initial list load.
  useEffect(() => { loadList() }, [loadList])

  // Deep-link: ?to=<userId> opens the exact conversation, then clears the param.
  useEffect(() => {
    const to = searchParams.get('to')
    if (!to) return
    startWith(Number(to))
    searchParams.delete('to')
    setSearchParams(searchParams, { replace: true })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchParams])

  // Load the thread whenever the active conversation changes.
  useEffect(() => {
    if (activeId) loadThread(activeId)
    else setActive(null)
  }, [activeId, loadThread])

  // Light polling: refresh the open thread and the inbox so replies show up.
  useEffect(() => {
    const t = setInterval(() => {
      loadList()
      if (activeId) loadThread(activeId, { silent: true })
    }, 8000)
    return () => clearInterval(t)
  }, [activeId, loadList, loadThread])

  // Keep the thread scrolled to the newest message.
  useEffect(() => {
    const count = active?.messages?.length ?? 0
    if (count !== lastCountRef.current) {
      lastCountRef.current = count
      scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight })
    }
  }, [active])

  const send = async (e) => {
    e.preventDefault()
    const text = body.trim()
    if (!text || !activeId) return
    setSending(true)
    setError(null)
    try {
      const res = await api.post(`/conversations/${activeId}`, { body: text })
      const message = res.data.data.message
      setActive((c) => (c ? { ...c, messages: [...(c.messages ?? []), message] } : c))
      setBody('')
      loadList()
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSending(false)
    }
  }

  const openContacts = async () => {
    setContactsOpen(true)
    setContactsLoading(true)
    try {
      const res = await api.get('/conversations/contacts')
      setContacts(res.data.data.contacts)
    } finally {
      setContactsLoading(false)
    }
  }

  const pickContact = async (contactId) => {
    setContactsOpen(false)
    await startWith(contactId)
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t('messages.messagesPageTitle')}
        description={t('messages.messagesPageDesc')}
        actions={<Button size="sm" onClick={openContacts}><Icon name="PenLine" className="size-4" /> {t('messages.newMessage')}</Button>}
      />

      <Card className="grid h-[calc(100vh-16rem)] min-h-[26rem] grid-cols-1 overflow-hidden p-0 md:grid-cols-[20rem_1fr]">
        {/* Conversation list */}
        <aside className={cn('flex flex-col border-r border-line', active && 'hidden md:flex')}>
          <div className="border-b border-line px-4 py-3 text-sm font-bold uppercase tracking-wide text-muted">
            {t('messages.conversations')}
          </div>
          <div className="flex-1 overflow-y-auto">
            {listLoading ? (
              <div className="grid h-full place-items-center"><Spinner className="size-6" /></div>
            ) : conversations.length === 0 ? (
              <div className="p-6 text-center text-sm text-muted">
                {t('messages.noConversations')}
              </div>
            ) : (
              conversations.map((c) => (
                <button
                  key={c.id}
                  type="button"
                  onClick={() => setActiveId(c.id)}
                  className={cn(
                    'flex w-full items-center gap-3 border-b border-line/70 px-4 py-3 text-left transition-colors hover:bg-canvas',
                    activeId === c.id && 'bg-canvas',
                  )}
                >
                  <Avatar name={c.participant?.full_name} src={c.participant?.avatar_url} size="sm" />
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center justify-between gap-2">
                      <span className="truncate font-semibold text-ink">{c.participant?.full_name ?? t('messages.unknown')}</span>
                      {c.last_message_at && (
                        <span className="shrink-0 text-[0.7rem] text-muted">{formatRelative(c.last_message_at)}</span>
                      )}
                    </div>
                    <div className="flex items-center justify-between gap-2">
                      <span className="truncate text-sm text-muted">
                        {c.last_message ? `${c.last_message.mine ? t('messages.you') + ': ' : ''}${c.last_message.body}` : c.participant?.account_type_label}
                      </span>
                      {c.unread_count > 0 && (
                        <span className="grid min-w-5 shrink-0 place-items-center rounded-full bg-navy-700 px-1.5 text-[0.65rem] font-bold text-white">
                          {c.unread_count}
                        </span>
                      )}
                    </div>
                  </div>
                </button>
              ))
            )}
          </div>
        </aside>

        {/* Active thread */}
        <section className={cn('flex flex-col', !active && 'hidden md:flex')}>
          {!active ? (
            <div className="grid flex-1 place-items-center p-6">
              <EmptyState icon="MessageSquare" title={t('messages.selectConversation')} description={t('messages.selectConversationDesc')} />
            </div>
          ) : (
            <>
              <div className="flex items-center gap-3 border-b border-line px-4 py-3">
                <button type="button" onClick={() => setActiveId(null)} className="grid size-9 place-items-center rounded-btn text-muted hover:bg-canvas md:hidden">
                  <Icon name="ArrowLeft" className="size-5" />
                </button>
                <Avatar name={active.participant?.full_name} src={active.participant?.avatar_url} size="sm" />
                <div className="min-w-0">
                  <p className="truncate font-bold text-ink">{active.participant?.full_name ?? t('messages.unknown')}</p>
                  <p className="text-xs text-muted">{active.participant?.account_type_label}</p>
                </div>
              </div>

              <div ref={scrollRef} className="flex-1 space-y-3 overflow-y-auto bg-canvas/40 p-4">
                {threadLoading ? (
                  <div className="grid h-full place-items-center"><Spinner className="size-6" /></div>
                ) : (active.messages?.length ?? 0) === 0 ? (
                  <p className="py-8 text-center text-sm text-muted">{t('messages.noMessagesYet')}</p>
                ) : (
                  active.messages.map((m) => (
                    <div key={m.id} className={cn('flex', m.mine ? 'justify-end' : 'justify-start')}>
                      <div
                        className={cn(
                          'max-w-[75%] rounded-2xl px-4 py-2 text-sm',
                          m.mine ? 'rounded-br-sm bg-navy-700 text-white' : 'rounded-bl-sm bg-surface text-ink ring-1 ring-line',
                        )}
                      >
                        <p className="whitespace-pre-wrap break-words">{m.body}</p>
                        <p className={cn('mt-1 text-[0.65rem]', m.mine ? 'text-white/60' : 'text-muted')}>
                          {formatRelative(m.created_at)}
                        </p>
                      </div>
                    </div>
                  ))
                )}
              </div>

              {error && <p className="border-t border-line bg-danger-soft px-4 py-2 text-sm text-danger">{error}</p>}

              <form onSubmit={send} className="flex items-end gap-2 border-t border-line p-3">
                <textarea
                  value={body}
                  onChange={(e) => setBody(e.target.value)}
                  onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(e) } }}
                  rows={1}
                  placeholder={t('messages.writeMessage')}
                  className="max-h-32 min-h-[2.75rem] flex-1 resize-none rounded-btn border border-line bg-surface px-4 py-2.5 text-sm text-ink outline-none focus:border-navy-600"
                />
                <Button type="submit" loading={sending} disabled={!body.trim()}>
                  <Icon name="Send" className="size-4" />
                </Button>
              </form>
            </>
          )}
        </section>
      </Card>

      <Modal open={contactsOpen} onClose={() => setContactsOpen(false)} title={t('messages.newMessage')} description={t('messages.chooseContact')}>
        {contactsLoading ? (
          <div className="grid h-32 place-items-center"><Spinner className="size-6" /></div>
        ) : contacts.length === 0 ? (
          <EmptyState icon="Users" title={t('messages.noContacts')} description={t('messages.noContactsDesc')} />
        ) : (
          <div className="max-h-80 space-y-1 overflow-y-auto">
            {contacts.map((c) => (
              <button
                key={c.id}
                type="button"
                onClick={() => pickContact(c.id)}
                className="flex w-full items-center gap-3 rounded-btn px-3 py-2.5 text-left transition-colors hover:bg-canvas"
              >
                <Avatar name={c.full_name} src={c.avatar_url} size="sm" />
                <div className="min-w-0">
                  <p className="truncate font-semibold text-ink">{c.full_name}</p>
                  <p className="text-xs text-muted">{c.account_type_label}</p>
                </div>
              </button>
            ))}
          </div>
        )}
      </Modal>
    </div>
  )
}
