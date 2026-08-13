import Spinner from '../ui/Spinner'
import EmptyState from '../ui/EmptyState'

/**
 * Wraps a page body so every dashboard screen handles loading and error the
 * same way. Renders `children` once data is ready.
 */
export default function LoadState({ loading, error, onRetry, children }) {
  if (loading) {
    return (
      <div className="grid min-h-64 place-items-center">
        <Spinner className="size-7" />
      </div>
    )
  }

  if (error) {
    return (
      <EmptyState
        icon="TriangleAlert"
        title="Couldn't load this yet"
        description={error}
        action={
          onRetry && (
            <button
              type="button"
              onClick={onRetry}
              className="text-sm font-semibold text-navy-700 hover:underline"
            >
              Try again
            </button>
          )
        }
      />
    )
  }

  return children
}
