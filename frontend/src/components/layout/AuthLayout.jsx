import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { AnimatePresence, motion } from 'framer-motion'
import { AUTH_CAROUSEL } from '../../lib/content'
import Icon from '../ui/Icon'
import Logo from '../ui/Logo'

/**
 * Split screen: the form on the left stays quiet and focused, the navy panel on
 * the right carries the brand. The panel is hidden below `lg` so small screens
 * get the form immediately.
 */
export default function AuthLayout({ title, subtitle, children, footer }) {
  const [index, setIndex] = useState(0)

  useEffect(() => {
    const timer = setInterval(() => setIndex((current) => (current + 1) % AUTH_CAROUSEL.length), 6000)

    return () => clearInterval(timer)
  }, [])

  const active = AUTH_CAROUSEL[index]

  return (
    <div className="flex min-h-dvh bg-canvas">
      {/* The form stays first in the DOM so it is also first in the tab order;
          `order` alone moves the decorative panel to the left visually. */}
      <div className="flex w-full flex-col lg:order-2 lg:w-[54%]">
        <header className="flex items-center justify-between px-6 py-6 md:px-10">
          <Logo />
          <Link
            to="/"
            className="flex items-center gap-1.5 text-sm font-medium text-muted transition-colors duration-200 hover:text-navy-800"
          >
            <Icon name="ArrowRight" className="size-4 rotate-180" />
            Back to site
          </Link>
        </header>

        <main className="flex flex-1 items-center justify-center px-6 py-8 md:px-10">
          <div className="w-full max-w-md">
            <motion.div
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.28, ease: [0.16, 1, 0.3, 1] }}
            >
              <h1 className="text-h3 font-extrabold text-ink">{title}</h1>
              {subtitle && <p className="mt-2 leading-relaxed text-muted">{subtitle}</p>}

              <div className="mt-8">{children}</div>

              {footer && <div className="mt-8 text-center text-[0.95rem] text-muted">{footer}</div>}
            </motion.div>
          </div>
        </main>
      </div>

      <aside className="relative hidden overflow-hidden lg:order-1 lg:block lg:w-[46%]">
        <AnimatePresence mode="sync">
          <motion.img
            key={active.image}
            src={active.image}
            alt=""
            className="absolute inset-0 size-full object-cover"
            loading="eager"
            initial={{ opacity: 0, scale: 1.04 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.9, ease: [0.16, 1, 0.3, 1] }}
          />
        </AnimatePresence>
        <div className="scrim-navy absolute inset-0" />

        <div className="relative flex h-full flex-col justify-between p-12 text-white">
          <div>
            <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-sm font-semibold ring-1 ring-inset ring-white/20">
              <span className="size-1.5 rounded-full bg-emerald-400" />
              Trusted by 12,500+ events
            </span>
          </div>

          <div>
            <p className="max-w-sm text-h3 font-extrabold leading-tight text-balance">
              Plan smarter. Create unforgettable events.
            </p>

            <div className="mt-10 min-h-[7.5rem]">
              <AnimatePresence mode="wait">
                <motion.div
                  key={active.title}
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -12 }}
                  transition={{ duration: 0.3, ease: [0.16, 1, 0.3, 1] }}
                  className="flex gap-4"
                >
                  <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-white/12 ring-1 ring-white/20">
                    <Icon name={active.icon} className="size-5 text-emerald-300" />
                  </span>
                  <div>
                    <p className="font-semibold">{active.title}</p>
                    <p className="mt-1 max-w-xs leading-relaxed text-white/70">{active.body}</p>
                  </div>
                </motion.div>
              </AnimatePresence>
            </div>

            <div className="mt-6 flex gap-2">
              {AUTH_CAROUSEL.map((prop, propIndex) => (
                <button
                  key={prop.title}
                  type="button"
                  onClick={() => setIndex(propIndex)}
                  aria-label={`Show: ${prop.title}`}
                  className={`h-1 rounded-full transition-all duration-300 ${
                    propIndex === index ? 'w-8 bg-white' : 'w-4 bg-white/30 hover:bg-white/50'
                  }`}
                />
              ))}
            </div>
          </div>
        </div>
      </aside>
    </div>
  )
}
