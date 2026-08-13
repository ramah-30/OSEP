import { useEffect, useState } from 'react'
import Modal from '../ui/Modal'
import Button from '../ui/Button'
import Textarea from '../ui/Textarea'
import StarRatingInput from './StarRatingInput'
import { api, parseApiError } from '../../lib/api'

/**
 * Leave (or update) a single overall rating + comment for the planner behind one
 * of the client's events. Lighter than the vendor review flow — one star score,
 * no categories.
 */
export default function PlannerReviewModal({ open, onClose, event, existing, onSubmitted }) {
  const [rating, setRating] = useState(5)
  const [comment, setComment] = useState('')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (open) {
      setRating(existing?.rating ?? 5)
      setComment(existing?.comment ?? '')
      setError(null)
    }
  }, [open, existing])

  const submit = async () => {
    setSaving(true)
    setError(null)
    try {
      await api.post('/planner-reviews', {
        event_id: event.id,
        rating,
        comment: comment || null,
      })
      onSubmitted?.()
      onClose()
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  const plannerName = event?.planner?.company_name ?? event?.planner?.full_name ?? 'your planner'

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={existing ? 'Edit your review' : `Review ${plannerName}`}
      description="Rate your experience from 1 to 5 stars."
      footer={
        <div className="flex justify-end gap-3">
          <Button variant="ghost" onClick={onClose}>Cancel</Button>
          <Button onClick={submit} loading={saving}>{existing ? 'Update review' : 'Submit review'}</Button>
        </div>
      }
    >
      <div className="space-y-3">
        {error && <p className="rounded-btn bg-danger-soft px-3 py-2 text-sm text-danger">{error}</p>}
        <div className="rounded-btn bg-canvas p-4">
          <StarRatingInput label="Overall rating" value={rating} onChange={setRating} />
        </div>
        <Textarea
          label="Your review"
          rows={4}
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          placeholder="Share how the planning went — what stood out, and anything future clients should know…"
        />
      </div>
    </Modal>
  )
}
