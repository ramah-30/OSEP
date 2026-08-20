import { useMemo, useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Avatar from '../../../components/ui/Avatar'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import Tabs from '../../../components/ui/Tabs'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import RatingStars from '../../../components/marketplace/RatingStars'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'
import { formatRelative } from '../../../lib/format'

const TONES = { published: 'emerald', pending: 'amber', hidden: 'muted' }

export default function AdminReviews() {
  const [filter, setFilter] = useState('')
  const path = useMemo(() => `/admin/marketplace/reviews${filter ? `?status=${filter}` : ''}`, [filter])
  const { data, loading, error, reload } = useResource(path)

  const moderate = async (id, status) => {
    await api.put(`/admin/marketplace/reviews/${id}/moderate`, { status })
    reload()
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Review moderation" description="Hide abusive reviews or restore them." />

      <Tabs
        value={filter}
        onChange={setFilter}
        tabs={[{ value: '', label: 'All' }, { value: 'published', label: 'Published' }, { value: 'pending', label: 'Pending' }, { value: 'hidden', label: 'Hidden' }]}
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.reviews.length ? (
          <div className="space-y-4">
            {data.reviews.map((r) => (
              <Card key={r.id} className="p-5">
                <div className="flex items-start justify-between gap-3">
                  <div className="flex items-center gap-3">
                    <Avatar name={r.reviewer_name ?? 'Planner'} />
                    <div>
                      <p className="font-bold text-ink">{r.reviewer_name ?? 'Planner'}</p>
                      <p className="text-xs text-muted">on {r.provider_name} · {formatRelative(r.created_at)}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <RatingStars rating={r.overall_rating} showValue={false} />
                    <Badge tone={TONES[r.status]}>{r.status_label}</Badge>
                  </div>
                </div>
                {r.title && <p className="mt-3 font-semibold text-ink">{r.title}</p>}
                {r.comment && <p className="mt-1 text-sm text-muted">{r.comment}</p>}

                <div className="mt-4 flex gap-2">
                  {r.status !== 'published' && <Button size="sm" variant="emerald" onClick={() => moderate(r.id, 'published')}>Publish</Button>}
                  {r.status !== 'hidden' && <Button size="sm" variant="danger" onClick={() => moderate(r.id, 'hidden')}>Hide</Button>}
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="Star" title="No reviews" description="Nothing to moderate in this view." />
        ))}
      </LoadState>
    </div>
  )
}
