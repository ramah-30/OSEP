import { useCallback, useEffect, useState } from 'react'
import { api } from './api'

const keyFor = (provider) => `${provider.type ?? (provider.venue_type !== undefined ? 'venue' : 'vendor')}:${provider.id}`

/**
 * Planner-side saved-list state shared by the browse grids and the Saved page.
 * Exposes the set of already-saved provider keys and a `save` that drops a
 * vendor/venue into the planner's default collection (creating one on demand).
 */
export function useSaved() {
  const [collections, setCollections] = useState([])
  const [loading, setLoading] = useState(true)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const { data } = await api.get('/marketplace/collections')
      setCollections(data.data.collections)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
  }, [load])

  const savedKeys = new Set()
  collections.forEach((c) =>
    (c.items ?? []).forEach((item) => {
      savedKeys.add(item.vendor_id ? `vendor:${item.vendor_id}` : `venue:${item.venue_id}`)
    }),
  )

  const ensureDefault = useCallback(async () => {
    const existing = collections[0]
    if (existing) return existing.id
    const { data } = await api.post('/marketplace/collections', { name: 'My Saved' })
    return data.data.collection.id
  }, [collections])

  const save = useCallback(
    async (provider, providerType) => {
      const type = providerType ?? (provider.type ?? 'vendor')
      const collectionId = await ensureDefault()
      await api.post(`/marketplace/collections/${collectionId}/items`, {
        provider_type: type,
        provider_id: provider.id,
      })
      await load()
    },
    [ensureDefault, load],
  )

  return { collections, savedKeys, save, reload: load, loading, keyFor }
}
