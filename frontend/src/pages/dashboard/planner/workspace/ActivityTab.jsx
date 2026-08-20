import { useOutletContext } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import ActivityFeed from '../../../../components/dashboard/ActivityFeed'

export default function ActivityTab() {
  const { event } = useOutletContext()

  return (
    <div className="max-w-2xl space-y-5">
      <div>
        <h2 className="text-lg font-extrabold text-ink">Activity log</h2>
        <p className="text-sm text-muted">A running history of everything that happens on this event.</p>
      </div>
      <Card className="p-6">
        <ActivityFeed activities={event.activities} />
      </Card>
    </div>
  )
}
