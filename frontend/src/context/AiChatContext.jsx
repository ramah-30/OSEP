import { createContext, useCallback, useContext, useState } from 'react'

/**
 * Drives the floating OSEP AI chat widget. Any dashboard page can pop the chat
 * open — optionally seeding it with a specific conversation, an event context
 * or a starter prompt — without owning the widget itself.
 */
const AiChatContext = createContext(null)

export function AiChatProvider({ children }) {
  const [isOpen, setIsOpen] = useState(false)
  const [seed, setSeed] = useState(null) // { conversationId?, eventId?, prompt? }

  const open = useCallback((opts = {}) => {
    setSeed(opts && Object.keys(opts).length ? opts : null)
    setIsOpen(true)
  }, [])

  const close = useCallback(() => setIsOpen(false), [])
  const toggle = useCallback(() => setIsOpen((o) => !o), [])
  const clearSeed = useCallback(() => setSeed(null), [])

  return (
    <AiChatContext.Provider value={{ isOpen, seed, open, close, toggle, clearSeed }}>
      {children}
    </AiChatContext.Provider>
  )
}

export function useAiChat() {
  const ctx = useContext(AiChatContext)
  if (!ctx) throw new Error('useAiChat must be used inside an <AiChatProvider>.')
  return ctx
}
