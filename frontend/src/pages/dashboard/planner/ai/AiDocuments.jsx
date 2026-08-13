import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import EmptyState from '../../../../components/ui/EmptyState'
import LoadState from '../../../../components/dashboard/LoadState'
import { api, parseApiError } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'
import { formatRelative } from '../../../../lib/format'
import { docCategoryMeta } from '../../../../lib/ai'

const BASE = '/dashboard/planner/ai-assistant'
const ACCENTS = {
  navy: 'bg-navy-50 text-navy-700',
  emerald: 'bg-emerald-50 text-emerald-600',
  purple: 'bg-purple-50 text-purple-600',
}

export default function AiDocuments() {
  const [documents, setDocuments] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const r = await api.get('/ai/documents')
      setDocuments(r.data.data.documents)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { load() }, [load])

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold text-ink">Documents</h2>
          <p className="text-sm text-muted">Everything your copilot has generated. Open one to edit, print or export.</p>
        </div>
        <Button to={`${BASE}/templates`} size="sm">
          <Icon name="Wand2" className="size-4" /> New from template
        </Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={load}>
        {documents.length === 0 ? (
          <EmptyState
            icon="FileText"
            title="No documents yet"
            description="Generate a proposal, timeline, email or more from the Templates tab — grounded in your real event data."
            action={<Button to={`${BASE}/templates`}><Icon name="Wand2" className="size-4" /> Browse templates</Button>}
          />
        ) : (
          <div className="grid gap-3 sm:grid-cols-2">
            {documents.map((doc) => {
              const meta = docCategoryMeta(doc.category)
              return (
                <Link key={doc.id} to={`${BASE}/documents/${doc.id}`}>
                  <Card className="h-full p-4 transition-colors hover:border-navy-200">
                    <div className="flex items-start gap-3">
                      <span className={cn('grid size-10 shrink-0 place-items-center rounded-xl', ACCENTS[meta.accent])}>
                        <Icon name={meta.icon} className="size-5" />
                      </span>
                      <div className="min-w-0 flex-1">
                        <p className="truncate font-bold text-ink">{doc.title}</p>
                        <div className="mt-1 flex flex-wrap items-center gap-1.5">
                          <Badge tone={doc.status === 'final' ? 'emerald' : 'muted'}>{doc.status === 'final' ? 'Final' : 'Draft'}</Badge>
                          {doc.grounded && <Badge tone="navy" dot>Grounded</Badge>}
                          {doc.event_title && <span className="truncate text-xs text-muted">· {doc.event_title}</span>}
                        </div>
                      </div>
                    </div>
                    <p className="mt-2.5 line-clamp-2 text-sm text-muted">{doc.preview}</p>
                    <p className="mt-2 text-[11px] text-muted">{formatRelative(doc.created_at)}</p>
                  </Card>
                </Link>
              )
            })}
          </div>
        )}
      </LoadState>
    </div>
  )
}
