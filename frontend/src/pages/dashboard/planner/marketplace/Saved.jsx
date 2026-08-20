import { useState } from 'react'
import Icon from '../../../../components/ui/Icon'
import Button from '../../../../components/ui/Button'
import Modal from '../../../../components/ui/Modal'
import { Field } from '../../../../components/ui/Field'
import EmptyState from '../../../../components/ui/EmptyState'
import LoadState from '../../../../components/dashboard/LoadState'
import VendorCard from '../../../../components/marketplace/VendorCard'
import VenueCard from '../../../../components/marketplace/VenueCard'
import { api } from '../../../../lib/api'
import { useResource } from '../../../../lib/useResource'

export default function Saved() {
  const { data, loading, error, reload } = useResource('/marketplace/collections')
  const [creating, setCreating] = useState(false)
  const [name, setName] = useState('')

  const create = async () => {
    if (!name.trim()) return
    await api.post('/marketplace/collections', { name })
    setName('')
    setCreating(false)
    reload()
  }

  const removeItem = async (collectionId, itemId) => {
    await api.delete(`/marketplace/collections/${collectionId}/items/${itemId}`)
    reload()
  }

  const removeCollection = async (id) => {
    await api.delete(`/marketplace/collections/${id}`)
    reload()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted">Your shortlists of vendors and venues.</p>
        <Button size="sm" onClick={() => setCreating(true)}><Icon name="Plus" className="size-4" /> New collection</Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.collections.length ? (
          <div className="space-y-8">
            {data.collections.map((c) => (
              <div key={c.id}>
                <div className="mb-3 flex items-center justify-between">
                  <div>
                    <h3 className="flex items-center gap-2 text-lg font-bold text-ink">
                      <Icon name="Bookmark" className="size-4 text-purple-600" /> {c.name}
                      <span className="text-sm font-normal text-muted">({c.items_count ?? c.items?.length ?? 0})</span>
                    </h3>
                    {c.description && <p className="text-sm text-muted">{c.description}</p>}
                  </div>
                  {!c.is_default && (
                    <button onClick={() => removeCollection(c.id)} className="text-sm font-semibold text-muted hover:text-danger">Delete</button>
                  )}
                </div>

                {c.items?.length ? (
                  <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    {c.items.map((item) => (
                      <div key={item.id} className="relative">
                        <button
                          onClick={() => removeItem(c.id, item.id)}
                          aria-label="Remove"
                          className="absolute right-3 top-3 z-20 grid size-9 place-items-center rounded-full bg-white/90 text-danger shadow backdrop-blur hover:bg-white"
                        >
                          <Icon name="Trash2" className="size-4" />
                        </button>
                        {item.vendor ? <VendorCard vendor={item.vendor} /> : item.venue ? <VenueCard venue={item.venue} /> : null}
                      </div>
                    ))}
                  </div>
                ) : (
                  <EmptyState icon="Bookmark" title="Nothing saved here yet" description="Save vendors and venues from the marketplace to build this list." />
                )}
              </div>
            ))}
          </div>
        ) : (
          <EmptyState icon="Bookmark" title="No collections yet" description="Create a collection to shortlist your favourite vendors and venues." action={<Button onClick={() => setCreating(true)}>New collection</Button>} />
        ))}
      </LoadState>

      <Modal
        open={creating}
        onClose={() => setCreating(false)}
        title="New collection"
        footer={<div className="flex justify-end gap-3"><Button variant="ghost" onClick={() => setCreating(false)}>Cancel</Button><Button onClick={create}>Create</Button></div>}
      >
        <Field label="Collection name" value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Wedding Vendors" autoFocus />
      </Modal>
    </div>
  )
}
