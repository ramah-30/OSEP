import { useCallback, useEffect, useState } from 'react'
import { api, parseApiError } from './api'

/**
 * Minimal GET-and-cache hook for the dashboard pages. Returns the unwrapped
 * `data` envelope plus loading/error state and a `reload` for after mutations.
 */
export function useResource(path) {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const response = await api.get(path)
      setData(response.data.data)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [path])

  useEffect(() => {
    load()
  }, [load])

  return { data, loading, error, reload: load, setData }
}
