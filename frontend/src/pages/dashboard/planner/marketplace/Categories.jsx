import { useNavigate } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import LoadState from '../../../../components/dashboard/LoadState'
import { useResource } from '../../../../lib/useResource'

export default function Categories() {
  const { data, loading, error, reload } = useResource('/marketplace/categories')
  const navigate = useNavigate()

  return (
    <LoadState loading={loading} error={error} onRetry={reload}>
      {data && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {data.categories.map((c) => (
            <Card
              key={c.id}
              hover
              as="button"
              onClick={() => navigate(`/dashboard/planner/marketplace/vendors?category_id=${c.id}`)}
              className="flex items-center gap-4 p-5 text-left"
            >
              <span className="grid size-12 shrink-0 place-items-center rounded-xl bg-navy-50 text-navy-700">
                <Icon name={c.icon ?? 'Store'} className="size-6" />
              </span>
              <div className="min-w-0">
                <p className="truncate font-bold text-ink">{c.name}</p>
                <p className="text-sm text-muted">{c.vendors_count ?? 0} vendors</p>
              </div>
              <Icon name="ArrowUpRight" className="ml-auto size-4 text-muted" />
            </Card>
          ))}
        </div>
      )}
    </LoadState>
  )
}
