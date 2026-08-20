import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate, useParams, Link } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import Spinner from '../../../../components/ui/Spinner'
import EmptyState from '../../../../components/ui/EmptyState'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import Markdown from '../../../../components/ai/Markdown'
import FeedbackButtons from '../../../../components/ai/FeedbackButtons'
import { api, parseApiError } from '../../../../lib/api'
import { formatRelative } from '../../../../lib/format'
import { docCategoryMeta } from '../../../../lib/ai'

const BASE = '/dashboard/planner/ai-assistant'

export default function AiDocument() {
  const { id } = useParams()
  const navigate = useNavigate()
  const printRef = useRef(null)

  const [doc, setDoc] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const [editing, setEditing] = useState(false)
  const [draftTitle, setDraftTitle] = useState('')
  const [draftContent, setDraftContent] = useState('')
  const [saving, setSaving] = useState(false)
  const [copied, setCopied] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)
  const [deleting, setDeleting] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const r = await api.get(`/ai/documents/${id}`)
      setDoc(r.data.data.document)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [id])

  useEffect(() => { load() }, [load])

  const startEdit = () => {
    setDraftTitle(doc.title)
    setDraftContent(doc.content)
    setEditing(true)
  }

  const save = async () => {
    setSaving(true)
    try {
      const r = await api.put(`/ai/documents/${id}`, { title: draftTitle, content: draftContent })
      setDoc(r.data.data.document)
      setEditing(false)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  const toggleStatus = async () => {
    const next = doc.status === 'final' ? 'draft' : 'final'
    try {
      const r = await api.put(`/ai/documents/${id}`, { status: next })
      setDoc(r.data.data.document)
    } catch (err) {
      setError(parseApiError(err).message)
    }
  }

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(doc.content)
      setCopied(true)
      setTimeout(() => setCopied(false), 1800)
    } catch { /* clipboard unavailable */ }
  }

  const print = () => {
    const html = printRef.current?.innerHTML ?? ''
    const win = window.open('', '_blank', 'width=820,height=900')
    if (!win) return
    win.document.write(`<!doctype html><html><head><title>${doc.title}</title>
      <style>
        body{font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#1e293b;max-width:720px;margin:40px auto;padding:0 24px;line-height:1.55}
        h1,h2,h3,p{margin:0 0 .6em}h1{font-size:1.6rem}h2{font-size:1.2rem;margin-top:1.2em}h3{font-size:1.05rem}
        ul,ol{margin:0 0 .8em 1.4em}li{margin:.15em 0}
        table{border-collapse:collapse;width:100%;margin:.6em 0}th,td{border:1px solid #e2e8f0;padding:6px 10px;text-align:left}th{background:#f8fafc}
        blockquote{border-left:3px solid #cbd5e1;margin:.6em 0;padding:.2em 0 .2em 12px;color:#64748b}
        hr{border:none;border-top:1px solid #e2e8f0;margin:1em 0}
        code{background:#f1f5f9;padding:1px 5px;border-radius:4px;font-size:.9em}
      </style></head><body>${html}
      <script>window.onload=function(){window.print()}</script></body></html>`)
    win.document.close()
  }

  const remove = async () => {
    setDeleting(true)
    try {
      await api.delete(`/ai/documents/${id}`)
      navigate(`${BASE}/documents`)
    } catch (err) {
      setError(parseApiError(err).message)
      setDeleting(false)
      setConfirmDelete(false)
    }
  }

  if (loading) return <div className="grid min-h-64 place-items-center"><Spinner className="size-7" /></div>
  if (error && !doc) return <EmptyState icon="TriangleAlert" title="Couldn't load this document" description={error} action={<Button onClick={load}>Retry</Button>} />
  if (!doc) return null

  const meta = docCategoryMeta(doc.category)
  const driverLabel = doc.model === 'local-composer' ? 'Offline engine' : (doc.driver === 'live' ? 'Live model' : doc.model)

  return (
    <div className="space-y-4">
      <Link to={`${BASE}/documents`} className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <Icon name="ArrowLeft" className="size-4" /> All documents
      </Link>

      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="flex min-w-0 items-start gap-3">
          <span className="grid size-11 shrink-0 place-items-center rounded-2xl bg-purple-50 text-purple-600">
            <Icon name={meta.icon} className="size-6" />
          </span>
          <div className="min-w-0">
            {editing ? (
              <input
                value={draftTitle}
                onChange={(e) => setDraftTitle(e.target.value)}
                className="w-full rounded-btn border border-line bg-surface px-3 py-1.5 text-lg font-extrabold text-ink outline-none focus:border-navy-600"
              />
            ) : (
              <h1 className="truncate text-h3 font-extrabold tracking-tight text-ink">{doc.title}</h1>
            )}
            <div className="mt-1.5 flex flex-wrap items-center gap-1.5">
              <Badge tone={meta.accent === 'emerald' ? 'emerald' : 'navy'}>{meta.label}</Badge>
              <Badge tone={doc.status === 'final' ? 'emerald' : 'muted'}>{doc.status === 'final' ? 'Final' : 'Draft'}</Badge>
              {doc.grounded ? (
                <Badge tone="navy" dot>Grounded in event data</Badge>
              ) : (
                <Badge tone="muted">General guidance</Badge>
              )}
              {doc.event_title && <span className="text-xs text-muted">· {doc.event_title}</span>}
            </div>
          </div>
        </div>
      </div>

      {/* Toolbar */}
      <div className="flex flex-wrap items-center gap-2">
        {editing ? (
          <>
            <Button size="sm" onClick={save} loading={saving}><Icon name="Check" className="size-4" /> Save</Button>
            <Button size="sm" variant="ghost" onClick={() => setEditing(false)} disabled={saving}>Cancel</Button>
          </>
        ) : (
          <>
            <Button size="sm" variant="secondary" onClick={startEdit}><Icon name="Pencil" className="size-4" /> Edit</Button>
            <Button size="sm" variant="secondary" onClick={copy}>
              <Icon name={copied ? 'Check' : 'Copy'} className="size-4" /> {copied ? 'Copied' : 'Copy'}
            </Button>
            <Button size="sm" variant="secondary" onClick={print}><Icon name="Printer" className="size-4" /> Print / PDF</Button>
            <Button size="sm" variant="secondary" onClick={toggleStatus}>
              <Icon name={doc.status === 'final' ? 'PenLine' : 'CheckCircle2'} className="size-4" />
              {doc.status === 'final' ? 'Mark draft' : 'Mark final'}
            </Button>
            <Button size="sm" variant="ghost" className="ml-auto text-danger" onClick={() => setConfirmDelete(true)}>
              <Icon name="Trash2" className="size-4" /> Delete
            </Button>
          </>
        )}
      </div>

      {error && <p className="text-sm text-danger">{error}</p>}

      {/* Body */}
      <Card className="p-6 sm:p-8">
        {editing ? (
          <textarea
            value={draftContent}
            onChange={(e) => setDraftContent(e.target.value)}
            className="min-h-[60vh] w-full resize-y rounded-btn border border-line bg-surface p-4 font-mono text-sm leading-relaxed text-ink outline-none focus:border-navy-600"
          />
        ) : (
          <div ref={printRef}>
            <Markdown content={doc.content} className="text-[0.95rem] text-ink" />
          </div>
        )}
      </Card>

      {!editing && (
        <div className="flex flex-col items-center gap-2 border-t border-line pt-4">
          <p className="text-xs font-medium text-muted">Was this document helpful?</p>
          <FeedbackButtons subjectType="document" subjectId={doc.id} initialRating={doc.my_rating ?? null} />
        </div>
      )}

      <p className="text-center text-xs text-muted">
        Generated by OSEP AI · {driverLabel} · {formatRelative(doc.created_at)}
      </p>

      <ConfirmDialog
        open={confirmDelete}
        onClose={() => setConfirmDelete(false)}
        onConfirm={remove}
        title="Delete this document?"
        confirmLabel="Delete"
        loading={deleting}
      />
    </div>
  )
}
