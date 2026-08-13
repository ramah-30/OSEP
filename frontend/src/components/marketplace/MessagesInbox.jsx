import { useEffect, useRef, useState } from 'react'
import Card from '../ui/Card'
import Icon from '../ui/Icon'
import Avatar from '../ui/Avatar'
import Button from '../ui/Button'
import EmptyState from '../ui/EmptyState'
import Spinner from '../ui/Spinner'
import QuotationView from './QuotationView'
import { cn } from '../../lib/cn'
import { api } from '../../lib/api'
import { useResource } from '../../lib/useResource'
import { useAuth } from '../../context/AuthContext'
import { formatRelative } from '../../lib/format'

/**
 * Two-pane marketplace inbox shared by planners and vendors. The thread list
 * comes from /marketplace/messages; selecting one loads its transcript and marks
 * it read. Message bubbles align by the signed-in user's id.
 */
export default function MessagesInbox() {
  const { user } = useAuth()
  const { data, loading, error, reload } = useResource('/marketplace/messages')
  const [activeId, setActiveId] = useState(null)
  const [thread, setThread] = useState(null)
  const [threadLoading, setThreadLoading] = useState(false)
  const [body, setBody] = useState('')
  const [sending, setSending] = useState(false)
  const endRef = useRef(null)

  const threads = data?.threads ?? []

  useEffect(() => {
    if (!activeId && threads.length) setActiveId(threads[0].id)
  }, [threads, activeId])

  useEffect(() => {
    if (!activeId) return
    setThreadLoading(true)
    api.get(`/marketplace/messages/${activeId}`)
      .then((res) => setThread(res.data.data.thread))
      .finally(() => setThreadLoading(false))
  }, [activeId])

  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [thread])

  const send = async () => {
    if (!body.trim()) return
    setSending(true)
    try {
      await api.post(`/marketplace/messages/${activeId}`, { body })
      setBody('')
      const res = await api.get(`/marketplace/messages/${activeId}`)
      setThread(res.data.data.thread)
      reload()
    } finally {
      setSending(false)
    }
  }

  if (!loading && !error && !threads.length) {
    return <EmptyState icon="MessageSquare" title="No conversations yet" description="Messages with vendors and planners will appear here." />
  }

  return (
    <Card className="grid h-[calc(100vh-16rem)] min-h-96 grid-cols-1 overflow-hidden md:grid-cols-[300px_1fr]">
      {/* Thread list */}
      <div className="hidden flex-col border-r border-line md:flex">
        <div className="border-b border-line p-4 font-bold text-ink">Conversations</div>
        <div className="flex-1 overflow-y-auto">
          {threads.map((t) => (
            <button
              key={t.id}
              onClick={() => setActiveId(t.id)}
              className={cn('flex w-full items-center gap-3 border-b border-line/60 p-3 text-left transition', activeId === t.id ? 'bg-navy-50' : 'hover:bg-canvas')}
            >
              <Avatar name={t.counterparty ?? 'Contact'} />
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-bold text-ink">{t.counterparty ?? 'Contact'}</p>
                <p className="truncate text-xs text-muted">{t.subject ?? 'Conversation'}</p>
              </div>
              {t.unread_count > 0 && <span className="grid size-5 place-items-center rounded-full bg-navy-800 text-[0.65rem] font-bold text-white">{t.unread_count}</span>}
            </button>
          ))}
        </div>
      </div>

      {/* Transcript */}
      <div className="flex min-h-0 flex-col">
        {threadLoading && !thread ? (
          <div className="grid flex-1 place-items-center"><Spinner className="size-6" /></div>
        ) : thread ? (
          <>
            <div className="flex items-center gap-3 border-b border-line p-4">
              <Avatar name={thread.counterparty ?? 'Contact'} />
              <div>
                <p className="font-bold text-ink">{thread.counterparty}</p>
                {thread.subject && <p className="text-xs text-muted">{thread.subject}</p>}
              </div>
            </div>

            <div className="flex-1 space-y-3 overflow-y-auto bg-canvas/40 p-4">
              {(thread.messages ?? []).map((m) => {
                const mine = m.sender_id === user.id
                return (
                  <div key={m.id} className={cn('flex', mine ? 'justify-end' : 'justify-start')}>
                    <div className={cn('max-w-[75%] rounded-card px-4 py-2.5 text-sm shadow-card', mine ? 'bg-navy-800 text-white' : 'bg-surface text-ink')}>
                      {m.body && <p className="whitespace-pre-line">{m.body}</p>}
                      {m.quotation && <div className="mt-2"><QuotationView quotation={m.quotation} /></div>}
                      <p className={cn('mt-1 text-[0.65rem]', mine ? 'text-white/60' : 'text-muted')}>{formatRelative(m.created_at)}</p>
                    </div>
                  </div>
                )
              })}
              <div ref={endRef} />
            </div>

            <div className="flex items-center gap-2 border-t border-line p-3">
              <input
                value={body}
                onChange={(e) => setBody(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && (e.preventDefault(), send())}
                placeholder="Type a message…"
                className="h-11 flex-1 rounded-btn border border-line bg-surface px-4 text-sm text-ink outline-none focus:border-navy-600"
              />
              <Button onClick={send} loading={sending} disabled={!body.trim()}><Icon name="Send" className="size-4" /></Button>
            </div>
          </>
        ) : (
          <div className="grid flex-1 place-items-center text-sm text-muted">Select a conversation</div>
        )}
      </div>
    </Card>
  )
}
