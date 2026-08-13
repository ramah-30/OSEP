import { useEffect, useRef, useState } from 'react'
import Icon from '../ui/Icon'
import { api } from '../../lib/api'
import { cn } from '../../lib/cn'

/**
 * Thumbs up/down on a piece of AI output (an assistant message or a generated
 * document). Clicking a thumb sets the rating; clicking the active thumb again
 * clears it. A down vote reveals an optional one-line reason. Fully controlled
 * by the server via /ai/feedback — the parent just supplies the subject and the
 * planner's current rating.
 */
export default function FeedbackButtons({ subjectType, subjectId, initialRating = null, className }) {
  const [rating, setRating] = useState(initialRating)
  const [busy, setBusy] = useState(false)
  const [showReason, setShowReason] = useState(false)
  const [reason, setReason] = useState('')
  const [savedReason, setSavedReason] = useState(false)
  const lastSubject = useRef(subjectId)

  // Re-sync when the parent swaps in a different subject or a fresh rating.
  useEffect(() => {
    if (lastSubject.current !== subjectId) {
      lastSubject.current = subjectId
      setRating(initialRating)
      setShowReason(false)
      setReason('')
      setSavedReason(false)
    }
  }, [subjectId, initialRating])

  const rate = async (next) => {
    if (busy) return
    setBusy(true)
    try {
      if (rating === next) {
        await api.delete(`/ai/feedback/${subjectType}/${subjectId}`)
        setRating(null)
        setShowReason(false)
      } else {
        await api.post('/ai/feedback', { subject_type: subjectType, subject_id: subjectId, rating: next })
        setRating(next)
        setShowReason(next === 'down')
        setSavedReason(false)
      }
    } catch { /* leave state as-is on failure */ } finally {
      setBusy(false)
    }
  }

  const submitReason = async () => {
    if (!reason.trim()) { setShowReason(false); return }
    try {
      await api.post('/ai/feedback', { subject_type: subjectType, subject_id: subjectId, rating: 'down', reason: reason.trim() })
      setSavedReason(true)
      setShowReason(false)
    } catch { /* ignore */ }
  }

  return (
    <div className={cn('flex flex-col gap-1.5', className)}>
      <div className="flex items-center gap-1">
        <Thumb icon="ThumbsUp" active={rating === 'up'} onClick={() => rate('up')} title="Helpful" />
        <Thumb icon="ThumbsDown" active={rating === 'down'} onClick={() => rate('down')} title="Not helpful" />
        {savedReason && <span className="ml-1 text-[11px] text-muted">Thanks for the note</span>}
      </div>
      {showReason && (
        <div className="flex items-center gap-1.5">
          <input
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); submitReason() } }}
            placeholder="What was off? (optional)"
            autoFocus
            className="h-8 flex-1 rounded-lg border border-line bg-surface px-2.5 text-xs text-ink outline-none focus:border-navy-300"
          />
          <button type="button" onClick={submitReason} className="grid size-8 place-items-center rounded-lg bg-navy-800 text-white hover:bg-navy-900">
            <Icon name="Send" className="size-3.5" />
          </button>
        </div>
      )}
    </div>
  )
}

function Thumb({ icon, active, onClick, title }) {
  return (
    <button
      type="button"
      onClick={onClick}
      title={title}
      className={cn(
        'grid size-7 place-items-center rounded-lg transition-colors',
        active
          ? (icon === 'ThumbsUp' ? 'bg-emerald-50 text-emerald-600' : 'bg-danger-soft text-danger')
          : 'text-muted hover:bg-canvas hover:text-ink',
      )}
    >
      <Icon name={icon} className="size-3.5" />
    </button>
  )
}
