import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import { useAuth } from './AuthContext'

const ThemeContext = createContext(null)

const prefersDark = () =>
  typeof window !== 'undefined' && window.matchMedia?.('(prefers-color-scheme: dark)').matches

/**
 * Resolves the user's theme preference ('system' | 'light' | 'dark') into an
 * effective 'light'/'dark' that the dashboard shell stamps onto its root as
 * `data-theme`. Dark tokens in index.css are scoped to that attribute, so the
 * marketing site — which never sets it — stays light.
 */
export function ThemeProvider({ children }) {
  const { user } = useAuth()
  // Defaults to light: the dashboard should never surprise a user with a dark
  // screen. Dark is opt-in via Settings → Preferences.
  const [preference, setPreference] = useState(user?.preferences?.theme ?? 'light')
  const [systemDark, setSystemDark] = useState(prefersDark)

  // Keep local state in step with the persisted preference.
  useEffect(() => {
    setPreference(user?.preferences?.theme ?? 'light')
  }, [user?.preferences?.theme])

  // Track the OS setting while the preference is 'system'.
  useEffect(() => {
    const mql = window.matchMedia?.('(prefers-color-scheme: dark)')
    if (!mql) return

    const onChange = (event) => setSystemDark(event.matches)
    mql.addEventListener('change', onChange)
    return () => mql.removeEventListener('change', onChange)
  }, [])

  const effective = preference === 'system' ? (systemDark ? 'dark' : 'light') : preference

  const value = useMemo(
    () => ({ preference, effective, setPreference }),
    [preference, effective],
  )

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
}

export function useTheme() {
  const context = useContext(ThemeContext)

  if (!context) {
    throw new Error('useTheme must be used inside a <ThemeProvider>.')
  }

  return context
}
