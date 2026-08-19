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

    // A few retries with backoff before giving up — the free-tier backend
    // can take 30-80s to wake from an idle sleep, and the first request or
    // two often times out or 5xx's even though the token itself is fine.
    const retryDelaysMs = [2000, 4000, 8000]

    for (let attempt = 0; attempt <= retryDelaysMs.length; attempt++) {
      try {
        const { data } = await api.get('/auth/me')
        setUser(data.data.user)
        setLoading(false)
        return data.data.user
      } catch (error) {
        // A genuine 401 means the token really is invalid — sign out.
        if (error?.response?.status === 401) {
          clearSession()
          setLoading(false)
          return null
        }

        // Anything else (network blip, cold start, 5xx) — retry with
        // backoff, and even after giving up don't wipe the token: a stray
        // failure shouldn't force a real re-login when the session may
        // still be good on the next successful check.
        if (attempt < retryDelaysMs.length) {
          await new Promise((resolve) => setTimeout(resolve, retryDelaysMs[attempt]))
        }
      }
    }

    setLoading(false)
    return null
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
