import { useEffect, useState } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { AnimatePresence, motion } from 'framer-motion'
import Sidebar from '../../components/dashboard/Sidebar'
import Topbar from '../../components/dashboard/Topbar'
import AiChatWidget from '../../components/ai/AiChatWidget'
import RoleAiChatWidget, { ROLE_AI_CONFIG } from '../../components/ai/RoleAiChatWidget'
import { hrefFor, navFor } from '../../lib/dashboardNav'
import { useAuth } from '../../context/AuthContext'
import { useTheme } from '../../context/ThemeContext'
import { AiChatProvider } from '../../context/AiChatContext'

/**
 * The workspace frame shared by all three roles: a fixed navy rail on desktop,
 * a slide-over drawer on mobile, and a sticky top bar over the routed page.
 * `data-theme` here scopes dark mode to the dashboard — the marketing site
 * never sets it and stays light.
 */
export default function DashboardLayout() {
  const { user } = useAuth()
  const { effective } = useTheme()
  const { pathname } = useLocation()
  const [drawerOpen, setDrawerOpen] = useState(false)

  // Close the mobile drawer whenever the route changes.
  useEffect(() => {
    setDrawerOpen(false)
  }, [pathname])

  // Derive the page title from the matching nav item (longest match wins so
  // '/dashboard/client/my-events' beats the '/dashboard/client' overview).
  const items = navFor(user.account_type, user)
  const active = [...items]
    .map((item) => ({ item, href: hrefFor(user.account_type, item) }))
    .filter(({ href }) => pathname === href || pathname.startsWith(`${href}/`))
    .sort((a, b) => b.href.length - a.href.length)[0]
  const title = active?.item.label ?? 'Dashboard'
  const isPlanner = user.account_type === 'event_planner'
  const isVendor = user.account_type === 'vendor'
  const isClient = user.account_type === 'client'

  return (
    <AiChatProvider>
    <div data-theme={effective} className="min-h-dvh bg-app text-ink">
      {/* Desktop sidebar */}
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 lg:block">
        <Sidebar />
      </aside>

      {/* Mobile drawer */}
      <AnimatePresence>
        {drawerOpen && (
          <motion.div
            className="fixed inset-0 z-50 lg:hidden"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          >
            <button
              type="button"
              aria-label="Close navigation"
              onClick={() => setDrawerOpen(false)}
              className="absolute inset-0 bg-navy-950/50"
            />
            <motion.aside
              className="absolute inset-y-0 left-0 w-64"
              initial={{ x: '-100%' }}
              animate={{ x: 0 }}
              exit={{ x: '-100%' }}
              transition={{ duration: 0.25, ease: [0.16, 1, 0.3, 1] }}
            >
              <Sidebar onNavigate={() => setDrawerOpen(false)} />
            </motion.aside>
          </motion.div>
        )}
      </AnimatePresence>

      <div className="lg:pl-64">
        <Topbar title={title} onOpenSidebar={() => setDrawerOpen(true)} />

        <main className="mx-auto max-w-6xl px-4 py-8 md:px-8 md:py-10">
          <motion.div
            key={pathname}
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, ease: [0.16, 1, 0.3, 1] }}
          >
            <Outlet />
          </motion.div>
        </main>
      </div>

      {/* Floating OSEP AI copilot — one per role */}
      {isPlanner && <AiChatWidget />}
      {isVendor && <RoleAiChatWidget config={ROLE_AI_CONFIG.vendor} />}
      {isClient && <RoleAiChatWidget config={ROLE_AI_CONFIG.client} />}
    </div>
    </AiChatProvider>
  )
}
