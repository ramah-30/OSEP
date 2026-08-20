import { motion } from 'framer-motion'
import { useReveal } from '../../lib/useReveal'

/**
 * Fade-and-rise on scroll. Kept to 260ms so sections feel responsive rather
 * than theatrical; `prefers-reduced-motion` is honoured by the global CSS
 * override, which collapses the transition to nothing.
 */
export default function Reveal({ children, delay = 0, y = 18, className, as = 'div', ...props }) {
  const Component = motion[as] ?? motion.div
  const [ref, visible] = useReveal()

  return (
    <Component
      ref={ref}
      initial={{ opacity: 0, y }}
      animate={visible ? { opacity: 1, y: 0 } : { opacity: 0, y }}
      transition={{ duration: 0.26, delay, ease: [0.16, 1, 0.3, 1] }}
      className={className}
      {...props}
    >
      {children}
    </Component>
  )
}
