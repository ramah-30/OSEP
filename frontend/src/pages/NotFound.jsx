import Button from '../components/ui/Button'
import Icon from '../components/ui/Icon'

export default function NotFound() {
  return (
    <div className="container-page flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
      <p className="text-sm font-bold uppercase tracking-[0.2em] text-muted">Error 404</p>
      <h1 className="mt-4 text-h2 font-extrabold text-ink text-balance">
        We could not find that page
      </h1>
      <p className="mt-4 max-w-md text-lead text-muted text-pretty">
        The link may be out of date, or the page may have moved. Everything else is where you left
        it.
      </p>

      <div className="mt-9 flex flex-wrap justify-center gap-3">
        <Button to="/" size="lg">
          Back to home
          <Icon name="ArrowRight" className="size-[18px]" />
        </Button>
        <Button to="/register" variant="secondary" size="lg">
          Create an account
        </Button>
      </div>
    </div>
  )
}
