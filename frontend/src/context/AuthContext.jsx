import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { api, setUnauthorizedHandler, tokenStore } from '../lib/api'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [token, setToken] = useState(() => tokenStore.get())
  // Starts true so guards wait for the session check instead of bouncing a
  // signed-in user to /login on a hard refresh.
  const [loading, setLoading] = useState(Boolean(tokenStore.get()))

  const clearSession = useCallback(() => {
    tokenStore.clear()
    setToken(null)
    setUser(null)
  }, [])

  const persistSession = useCallback((nextToken, nextUser) => {
    tokenStore.set(nextToken)
    setToken(nextToken)
    setUser(nextUser)
  }, [])

  useEffect(() => {
    setUnauthorizedHandler(() => {
      setToken(null)
      setUser(null)
    })
  }, [])

  const refreshUser = useCallback(async () => {
    if (!tokenStore.get()) {
      setUser(null)
      setLoading(false)
      return null
    }

    try {
      const { data } = await api.get('/auth/me')
      setUser(data.data.user)
      return data.data.user
    } catch {
      clearSession()
      return null
    } finally {
      setLoading(false)
    }
  }, [clearSession])

  // Rehydrate on first paint so a refresh keeps the user signed in.
  useEffect(() => {
    refreshUser()
  }, [refreshUser])

  const login = useCallback(
    async (credentials) => {
      const { data } = await api.post('/auth/login', credentials)
      persistSession(data.data.token, data.data.user)
      return data.data.user
    },
    [persistSession],
  )

  const register = useCallback(
    async (payload) => {
      const { data } = await api.post('/auth/register', payload)
      persistSession(data.data.token, data.data.user)
      return data.data.user
    },
    [persistSession],
  )

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout')
    } catch {
      // A token that is already gone server-side is still a successful logout
      // from the user's point of view.
    } finally {
      clearSession()
    }
  }, [clearSession])

  const value = useMemo(
    () => ({
      user,
      token,
      loading,
      isAuthenticated: Boolean(user),
      login,
      register,
      logout,
      refreshUser,
      setUser,
    }),
    [user, token, loading, login, register, logout, refreshUser],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used inside an <AuthProvider>.')
  }

  return context
}
