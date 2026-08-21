import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { I18nextProvider } from 'react-i18next'

// Self-hosted so the app renders identically offline and makes no third-party
// font request.
import '@fontsource/plus-jakarta-sans/400.css'
import '@fontsource/plus-jakarta-sans/500.css'
import '@fontsource/plus-jakarta-sans/600.css'
import '@fontsource/plus-jakarta-sans/700.css'
import '@fontsource/plus-jakarta-sans/800.css'
import '@fontsource/inter/400.css'
import '@fontsource/inter/600.css'

import './index.css'
import './i18n/config'
import i18n from './i18n/config'
import App from './App.jsx'
import { AuthProvider } from './context/AuthContext.jsx'
import { ThemeProvider } from './context/ThemeContext.jsx'
import { NotificationsProvider } from './context/NotificationsContext.jsx'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <BrowserRouter>
      <I18nextProvider i18n={i18n}>
        <AuthProvider>
          <ThemeProvider>
            <NotificationsProvider>
              <App />
            </NotificationsProvider>
          </ThemeProvider>
        </AuthProvider>
      </I18nextProvider>
    </BrowserRouter>
  </StrictMode>,
)
