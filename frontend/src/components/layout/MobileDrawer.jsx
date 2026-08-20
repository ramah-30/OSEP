import { useEffect } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { useAuth } from '../../context/AuthContext'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import Logo from '../ui/Logo'
import { NAV_LINKS } from './Navbar'

export default function MobileDrawer({ onClose }) {
  const { isAuthenticated, user, logout } = useAuth()

  // Close on Escape and stop the page behind from scrolling.
  useEffect(() => {
    const onKeyDown = (event) => {
      if (event.key === 'Escape') onClose()
    }

    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', onKeyDown)

    return () => {
      document.body.style.overflow = previousOverflow
      window.removeEventListener('keydown', onKeyDown)
    }
  }, [onClose])

  return (
    <div className="fixed inset-0 z-[60] lg:hidden">
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        transition={{ duration: 0.2 }}
        onClick={onClose}
        className="absolute inset-0 bg-navy-950/45 backdrop-blur-sm"
      />

      <motion.aside
        role="dialog"
        aria-modal="true"
        aria-label="Menu"
        initial={{ x: '100%' }}
        animate={{ x: 0 }}
        exit={{ x: '100%' }}
        transition={{ duration: 0.28, ease: [0.16, 1, 0.3, 1] }}
        className="absolute inset-y-0 right-0 flex w-[min(20rem,88vw)] flex-col bg-surface shadow-2xl"
      >
        <div className="flex h-20 items-center justify-between border-b border-line px-5">
          <Logo />
          <button
            type="button"
            onClick={onClose}
            aria-label="Close menu"
            className="grid size-11 place-items-center rounded-btn text-muted transition-colors duration-200 hover:bg-canvas hover:text-ink"
          >
            <Icon name="X" className="size-5" />
          </button>
        </div>

        <nav className="flex-1 overflow-y-auto px-3 py-4">
          {NAV_LINKS.map((link) => (
            <Link
              key={link.label}
              to={link.to}
              onClick={onClose}
              className="flex items-center justify-between rounded-btn px-4 py-3.5 text-[1.0625rem] font-medium text-ink transition-colors duration-200 hover:bg-canvas"
            >
              {link.label}
              <Icon name="ArrowRight" className="size-4 text-muted" />
            </Link>
          ))}
        </nav>

        <div className="space-y-3 border-t border-line p-5">
          {isAuthenticated ? (
            <>
              <Button to={user?.dashboard_path ?? '/dashboard/client'} fullWidth onClick={onClose}>
                Go to dashboard
              </Button>
              <Button variant="secondary" fullWidth onClick={() => { onClose(); logout() }}>
                <Icon name="LogOut" className="size-4" />
                Sign out
              </Button>
            </>
          ) : (
            <>
              <Button to="/login" variant="secondary" fullWidth onClick={onClose}>
                Log in
              </Button>
              <Button to="/register" fullWidth onClick={onClose}>
                Get Started
              </Button>
            </>
          )}
        </div>
      </motion.aside>
    </div>
  )
}
