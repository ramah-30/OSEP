import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import LoadState from '../../../../components/dashboard/LoadState'
import ContractsList from '../../../../components/marketplace/ContractsList'
import { api } from '../../../../lib/api'
import { useResource } from '../../../../lib/useResource'

export default function Contracts() {
  const { data, loading, error, reload } = useResource('/marketplace/contracts')

  const sign = async (id) => {
    await api.post(`/marketplace/contracts/${id}/sign`)
    reload()
  }

  return (
    <LoadState loading={loading} error={error} onRetry={reload}>
      {data && (
        <ContractsList
          contracts={data.contracts}
          perspective="planner"
          renderActions={(c) =>
            c.status === 'sent' ? (
              <Button size="sm" onClick={() => sign(c.id)}><Icon name="PenLine" className="size-4" /> Sign contract</Button>
            ) : null
          }
        />
      )}
    </LoadState>
  )
}
