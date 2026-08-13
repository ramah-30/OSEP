import { IMAGES } from '../../lib/content'
import { useAuth } from '../../context/AuthContext'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import Reveal from '../ui/Reveal'

export default function CtaBanner() {
  const { isAuthenticated, user } = useAuth()

  return (
    <section className="bg-surface pb-20 md:pb-28">
      <div className="container-page">
        <Reveal className="relative isolate overflow-hidden rounded-card">
          <img src={IMAGES.ctaBanquet} alt="" className="absolute inset-0 -z-20 size-full object-cover" loading="lazy" />
          <div className="scrim-navy absolute inset-0 -z-10" />

          <div className="px-8 py-16 text-center md:px-16 md:py-20">
            <h2 className="mx-auto max-w-2xl text-h2 font-extrabold text-white text-balance">
              Your next event deserves a better starting point
            </h2>
            <p className="mx-auto mt-5 max-w-xl text-lead text-white/75 text-pretty">
              Join 12,500 events already planned on OSEP. Free to start, and your account is ready
              the day the dashboard ships.
            </p>

            <div className="mt-9 flex flex-wrap justify-center gap-4">
              {isAuthenticated ? (
                <Button to={user?.dashboard_path ?? '/dashboard/client'} size="lg">
                  Go to dashboard
                  <Icon name="ArrowRight" className="size-[18px]" />
                </Button>
              ) : (
                <>
                  <Button to="/register" size="lg">
                    Get Started free
                    <Icon name="ArrowRight" className="size-[18px]" />
                  </Button>
                  <Button to="/login" size="lg" variant="light">
                    I already have an account
                  </Button>
                </>
              )}
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
