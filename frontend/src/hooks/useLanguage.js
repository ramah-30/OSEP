import { useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { useAuth } from '../context/AuthContext'

/**
 * Hook to sync user language preference with i18next.
 * Automatically switches language when user changes their preference in settings.
 */
export function useLanguage() {
  const { i18n } = useTranslation()
  const { user } = useAuth()

  useEffect(() => {
    if (user?.preferences?.locale) {
      const locale = user.preferences.locale
      if (i18n.language !== locale) {
        i18n.changeLanguage(locale)
        localStorage.setItem('i18nextLng', locale)
      }
    }
  }, [user?.preferences?.locale, i18n])

  return { currentLanguage: i18n.language, changeLanguage: i18n.changeLanguage }
}
