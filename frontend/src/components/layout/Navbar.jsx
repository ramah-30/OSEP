import { useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { AnimatePresence, motion } from 'framer-motion'
import { cn } from '../../lib/cn'
import { useAuth } from '../../context/AuthContext'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import Logo from '../ui/Logo'
import MobileDrawer from './MobileDrawer'

export const NAV_LINKS = [
  { label: 'Features', to: '/#features' },
  { label: 'How it works', to: '/#how-it-works' },
  { label: 'About', to: '/#about' },
  { label: 'Contact', to: '/#contact' },
]

export default function Navbar({ transparent = false }) {
  const [scrolled, setScrolled] = useState(false)
  const [drawerOpen, setDrawerOpen] = useState(false)
  const { isAuthenticated, user, logout } = useAuth()
  const location = useLocation()

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 40)

    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })

    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    setDrawerOpen(false)
  }, [location.pathname, location.hash])

  // Over the hero the bar is see-through; past 40px it becomes a solid surface.
  const solid = !transparent || scrolled

  return (
    <>
      <motion.header
        initial={{ y: -24, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        transition={{ duration: 0.3, ease: [0.16, 1, 0.3, 1] }}
        className={cn(
          'fixed inset-x-0 top-0 z-50 transition-[background-color,box-shadow,border-color,backdrop-filter] duration-300',
          solid
            ? 'border-b border-line bg-surface/90 shadow-nav backdrop-blur-xl'
            : 'border-b border-transparent bg-transparent',
        )}
      >
        <nav className="container-page flex h-20 items-center justify-between gap-6">
          <Logo variant={solid ? 'dark' : 'light'} />

          <div className="hidden items-center gap-1 lg:flex">
            {NAV_LINKS.map((link) => (
              <Link
                key={link.label}
                to={link.to}
                className={cn(
                  'rounded-lg px-3.5 py-2 text-[0.95rem] font-medium transition-colors duration-200',
                  solid
                    ? 'text-muted hover:bg-navy-50 hover:text-navy-800'
                    : 'text-white/85 hover:bg-white/10 hover:text-white',
                )}
              >
                {link.label}
              </Link>
            ))}
          </div>

          <div className="hidden items-center gap-3 lg:flex">
            {isAuthenticated ? (
              <>
                <Button to={user?.dashboard_path ?? '/dashboard/client'} size="sm">
                  Go to dashboard
                  <Icon name="ArrowRight" className="size-4" />
                </Button>
                <Button onClick={logout} variant={solid ? 'ghost' : 'light'} size="sm">
                  <Icon name="LogOut" className="size-4" />
                  Sign out
                </Button>
              </>
            ) : (
              <>
                <Button to="/login" variant={solid ? 'ghost' : 'light'} size="sm">
                  Log in
                </Button>
                <Button to="/register" size="sm">
                  Get Started
                  <Icon name="ArrowRight" className="size-4" />
                </Button>
              </>
            )}
          </div>

          <button
            type="button"
            onClick={() => setDrawerOpen(true)}
            aria-label="Open menu"
            aria-expanded={drawerOpen}
            className={cn(
              'grid size-11 place-items-center rounded-btn transition-colors duration-200 lg:hidden',
              solid ? 'text-navy-800 hover:bg-navy-50' : 'text-white hover:bg-white/10',
            )}
          >
            <Icon name="Menu" className="size-6" />
          </button>
        </nav>
      </motion.header>

      <AnimatePresence>
        {drawerOpen && <MobileDrawer onClose={() => setDrawerOpen(false)} />}
      </AnimatePresence>
    </>
  )
}
