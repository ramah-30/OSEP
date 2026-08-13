import axios from 'axios'

const TOKEN_KEY = 'osep.token'

export const tokenStore = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (token) => localStorage.setItem(TOKEN_KEY, token),
  clear: () => localStorage.removeItem(TOKEN_KEY),
}

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = tokenStore.get()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

/** Callback the AuthProvider registers so a dead token can clear app state. */
let onUnauthorized = null
export const setUnauthorizedHandler = (handler) => {
  onUnauthorized = handler
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && tokenStore.get()) {
      tokenStore.clear()
      onUnauthorized?.()
    }

    return Promise.reject(error)
  },
)

/**
 * Flattens the API's fixed envelope into something a form can consume:
 * a human message plus per-field errors keyed exactly as the inputs are named.
 */
export function parseApiError(error) {
  const status = error?.response?.status ?? 0
  const payload = error?.response?.data

  if (payload && typeof payload === 'object' && 'success' in payload) {
    return {
      status,
      message: payload.message || 'Something went wrong. Please try again.',
      errors: payload.errors ?? {},
    }
  }

  if (error?.code === 'ERR_NETWORK') {
    return {
      status,
      message: 'Cannot reach the OSEP API. Check that the server is running and try again.',
      errors: {},
    }
  }

  return {
    status,
    message: 'Something went wrong. Please try again.',
    errors: {},
  }
}

/**
 * Push server-side validation errors onto the matching react-hook-form fields.
 */
export function applyServerErrors(errors, setError) {
  Object.entries(errors ?? {}).forEach(([field, messages]) => {
    setError(field, {
      type: 'server',
      message: Array.isArray(messages) ? messages[0] : String(messages),
    })
  })
}
