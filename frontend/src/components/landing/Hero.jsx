import { motion } from 'framer-motion'
import { IMAGES } from '../../lib/content'
import { useAuth } from '../../context/AuthContext'
import Button from '../ui/Button'
import Icon from '../ui/Icon'

const FLOATING = [
  { icon: 'Wallet', label: 'Budget on track', value: 'TSh 42.8M of TSh 45M', tone: 'emerald' },
  { icon: 'Sparkles', label: 'AI drafted the timeline', value: '38 tasks · 6 owners', tone: 'purple' },
]

/**
 * Explicit per-element entrance rather than parent/child variants — variant
 * propagation left the copy stuck at opacity 0 on first paint, and there is no
 * reason for the hero's headline to depend on a parent resolving a label.
 */
const entrance = (index) => ({
  initial: { opacity: 0, y: 22 },
  animate: { opacity: 1, y: 0 },
  transition: { duration: 0.32, delay: 0.05 + index * 0.08, ease: [0.16, 1, 0.3, 1] },
})

export default function Hero() {
  const { isAuthenticated, user } = useAuth()

  return (
    <section className="relative isolate flex min-h-[88vh] items-center overflow-hidden pt-28 pb-16">
      <img
        src={IMAGES.heroWedding}
        alt=""
        fetchPriority="high"
        className="absolute inset-0 -z-20 size-full object-cover"
      />
      <div className="scrim-navy absolute inset-0 -z-10" />

      <div className="container-page">
        <div className="max-w-3xl">
          <motion.span
            {...entrance(0)}
            className="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/20 backdrop-blur-sm"
          >
            <span className="grid size-5 place-items-center rounded-full bg-purple-500">
              <Icon name="Sparkles" className="size-3" />
            </span>
            AI-powered event planning
          </motion.span>

          <motion.h1
            {...entrance(1)}
            className="mt-6 text-display font-extrabold text-white text-balance"
          >
            Plan smarter. Create unforgettable events.
          </motion.h1>

          <motion.p
            {...entrance(2)}
            className="mt-6 max-w-2xl text-lead text-white/80 text-pretty"
          >
            From the first brief to the last guest, OSEP brings planning, budgets, vendors and
            guest lists into one calm workspace — for planners, vendors and the people they do it
            all for.
          </motion.p>

          {isAuthenticated ? (
            <motion.div {...entrance(3)} className="mt-10 flex flex-wrap gap-4">
              <Button to={user?.dashboard_path ?? '/dashboard/client'} size="lg">
                Go to dashboard
                <Icon name="ArrowRight" className="size-[18px]" />
              </Button>
            </motion.div>
          ) : (
            <>
              <motion.div {...entrance(3)} className="mt-10 flex flex-wrap gap-4">
                <Button to="/register" size="lg">
                  Get Started free
                  <Icon name="ArrowRight" className="size-[18px]" />
                </Button>
                <Button to="/login" size="lg" variant="light">
                  I already have an account
                </Button>
              </motion.div>

              <motion.p {...entrance(4)} className="mt-6 flex items-center gap-2 text-sm text-white/60">
                <Icon name="CheckCircle2" className="size-4 text-emerald-400" />
                Free to join · No credit card required
              </motion.p>
            </>
          )}
        </div>

        <div className="pointer-events-none mt-12 hidden gap-4 lg:flex">
          {FLOATING.map((chip, index) => (
            <motion.div
              key={chip.label}
              initial={{ opacity: 0, y: 24 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.45 + index * 0.12, ease: [0.16, 1, 0.3, 1] }}
              className="flex items-center gap-3 rounded-card bg-white/10 px-5 py-4 ring-1 ring-inset ring-white/20 backdrop-blur-md"
            >
              <span
                className={`grid size-10 place-items-center rounded-xl ${
                  chip.tone === 'emerald' ? 'bg-emerald-500/25 text-emerald-300' : 'bg-purple-500/25 text-purple-200'
                }`}
              >
                <Icon name={chip.icon} className="size-5" />
              </span>
              <span className="text-white">
                <span className="block text-sm text-white/65">{chip.label}</span>
                <span className="font-semibold">{chip.value}</span>
              </span>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  )
}
