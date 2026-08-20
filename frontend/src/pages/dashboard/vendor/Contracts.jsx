import PageHeader from '../../../components/ui/PageHeader'
import Button from '../../../components/ui/Button'
import LoadState from '../../../components/dashboard/LoadState'
import ContractsList from '../../../components/marketplace/ContractsList'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'

const NEXT = {
  draft: { label: 'Send to planner', to: 'sent' },
  signed: { label: 'Mark active', to: 'active' },
  active: { label: 'Mark completed', to: 'completed' },
}

export default function Contracts() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/contracts')

  const transition = async (id, status) => {
    await api.post(`/marketplace/vendor/contracts/${id}/transition`, { status })
    reload()
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Contracts" description="Move contracts from draft through to completion." />
      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <ContractsList
            contracts={data.contracts}
            perspective="vendor"
            renderActions={(c) => {
              const next = NEXT[c.status]
              return (
                <>
                  {next && <Button size="sm" onClick={() => transition(c.id, next.to)}>{next.label}</Button>}
                  {c.status !== 'completed' && c.status !== 'cancelled' && (
                    <Button size="sm" variant="ghost" onClick={() => transition(c.id, 'cancelled')}>Cancel</Button>
                  )}
                </>
              )
            }}
          />
        )}
      </LoadState>
    </div>
  )
}
