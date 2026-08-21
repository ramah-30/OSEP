import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import enTranslations from './locales/en.json'
import swTranslations from './locales/sw.json'
import frTranslations from './locales/fr.json'

const resources = {
  en: { translation: enTranslations },
  sw: { translation: swTranslations },
  fr: { translation: frTranslations },
}

i18n
  .use(initReactI18next)
  .init({
    resources,
    lng: localStorage.getItem('i18nextLng') || 'en',
    fallbackLng: 'en',
    interpolation: {
      escapeValue: false,
    },
  })

export default i18n
