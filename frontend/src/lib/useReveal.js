import { useEffect, useRef, useState } from 'react'

/**
 * Reveal-on-scroll built on a plain IntersectionObserver.
 *
 * Deliberately not Framer Motion's `whileInView`: with `once: true` that can
 * latch across a StrictMode remount, leaving whole sections stuck at opacity 0
 * when a visitor lands mid-page (for example on "/#about"). An observer reports
 * the current intersection as soon as it starts observing, so anything already
 * on screen reveals immediately.
 */
export function useReveal({ threshold = 0.12, rootMargin = '0px 0px -6% 0px' } = {}) {
  const ref = useRef(null)
  const [visible, setVisible] = useState(false)

  useEffect(() => {
    const element = ref.current

    if (!element) return

    // No observer support (or a very old browser): show the content, always.
    if (typeof IntersectionObserver === 'undefined') {
      setVisible(true)
      return
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setVisible(true)
          observer.disconnect()
        }
      },
      { threshold, rootMargin },
    )

    observer.observe(element)

    return () => observer.disconnect()
  }, [threshold, rootMargin])

  return [ref, visible]
}
