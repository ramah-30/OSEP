import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { api } from '../lib/api'
import { useAuth } from './AuthContext'

const NotificationsContext = createContext(null)

/**
 * Loads the signed-in user's notifications once the session is ready and keeps
 * the unread badge in sync as items are read. Kept deliberately simple —
 * polling/websockets can layer on later without touching consumers.
 */
export function NotificationsProvider({ children }) {
  const { isAuthenticated } = useAuth()
  const [items, setItems] = useState([])
  const [unread, setUnread] = useState(0)
  const [loading, setLoading] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const { data } = await api.get('/notifications')
      setItems(data.data.notifications)
      setUnread(data.data.unread_count)
    } catch {
      // A failed fetch just leaves the bell empty; nothing user-facing to do.
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (isAuthenticated) {
      load()
    } else {
      setItems([])
      setUnread(0)
    }
  }, [isAuthenticated, load])

  const markRead = useCallback(async (id) => {
    // Optimistic: flip the row and decrement immediately.
    setItems((current) =>
      current.map((n) => (n.id === id && !n.read ? { ...n, read: true } : n)),
    )
    setUnread((count) => Math.max(0, count - 1))

    try {
      const { data } = await api.put(`/notifications/${id}/read`)
      setUnread(data.data.unread_count)
    } catch {
      load()
    }
  }, [load])

  const remove = useCallback(async (id) => {
    // Optimistic: drop the row and, if it was unread, decrement the badge.
    let wasUnread = false
    setItems((current) => {
      wasUnread = current.some((n) => n.id === id && !n.read)
      return current.filter((n) => n.id !== id)
    })
    if (wasUnread) setUnread((count) => Math.max(0, count - 1))

    try {
      const { data } = await api.delete(`/notifications/${id}`)
      setUnread(data.data.unread_count)
    } catch {
      load()
    }
  }, [load])

  const markAllRead = useCallback(async () => {
    setItems((current) => current.map((n) => ({ ...n, read: true })))
    setUnread(0)

    try {
      await api.post('/notifications/read-all')
    } catch {
      load()
    }
  }, [load])

  const value = useMemo(
    () => ({ items, unread, loading, load, markRead, markAllRead, remove }),
    [items, unread, loading, load, markRead, markAllRead, remove],
  )

  return <NotificationsContext.Provider value={value}>{children}</NotificationsContext.Provider>
}

export function useNotifications() {
  const context = useContext(NotificationsContext)

  if (!context) {
    throw new Error('useNotifications must be used inside a <NotificationsProvider>.')
  }

  return context
}
