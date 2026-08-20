import { useState } from 'react'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import LoadState from '../../../../components/dashboard/LoadState'
import ContractsList from '../../../../components/marketplace/ContractsList'
import PaySimulationDrawer from '../../../../components/payments/PaySimulationDrawer'
import { api } from '../../../../lib/api'
import { useResource } from '../../../../lib/useResource'

const PAYABLE_STATUSES = ['signed', 'active', 'completed']

export default function Contracts() {
  const { data, loading, error, reload } = useResource('/marketplace/contracts')
  const [paying, setPaying] = useState(null)

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
          renderActions={(c) => (
            <>
              {c.status === 'sent' && (
                <Button size="sm" onClick={() => sign(c.id)}><Icon name="PenLine" className="size-4" /> Sign contract</Button>
              )}
              {PAYABLE_STATUSES.includes(c.status) && c.balance > 0 && (
                <Button size="sm" variant="secondary" onClick={() => setPaying(c)}>
                  <Icon name="Smartphone" className="size-4" /> Pay vendor
                </Button>
              )}
            </>
          )}
        />
      )}

      <PaySimulationDrawer
        open={Boolean(paying)}
        onClose={() => { setPaying(null); reload() }}
        payee={paying ? { name: paying.provider_name, phone: paying.provider_phone } : null}
        balance={paying?.balance ?? 0}
        currency={paying?.currency}
        onSubmit={async ({ amount, payer_phone, network }) => {
          const { data } = await api.post(`/marketplace/contracts/${paying.id}/pay`, { amount, payer_phone, network })
          return {
            status: data.data.payment.status,
            receiptNumber: data.data.payment.receipt?.receipt_number,
            message: data.message,
          }
        }}
      />
    </LoadState>
  )
}
