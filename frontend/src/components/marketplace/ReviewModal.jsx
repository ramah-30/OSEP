import { useEffect, useState } from 'react'
import Modal from '../ui/Modal'
import Button from '../ui/Button'
import { Field } from '../ui/Field'
import Textarea from '../ui/Textarea'
import StarRatingInput from './StarRatingInput'
import { api, parseApiError } from '../../lib/api'

const CATEGORIES = [
  ['professionalism', 'Professionalism'],
  ['communication', 'Communication'],
  ['quality', 'Quality'],
  ['value', 'Value'],
  ['timeliness', 'Timeliness'],
]

/** Leave a categorised 1–5 review for a vendor or venue. */
export default function ReviewModal({ open, onClose, provider, onSubmitted }) {
  const [scores, setScores] = useState({ professionalism: 5, communication: 5, quality: 5, value: 5, timeliness: 5 })
  const [title, setTitle] = useState('')
  const [comment, setComment] = useState('')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (open) {
      setScores({ professionalism: 5, communication: 5, quality: 5, value: 5, timeliness: 5 })
      setTitle('')
      setComment('')
      setError(null)
    }
  }, [open])

  const submit = async () => {
    setSaving(true)
    setError(null)
    try {
      await api.post('/marketplace/reviews', {
        provider_type: provider.type,
        provider_id: provider.id,
        ...scores,
        title: title || null,
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

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={`Review ${provider?.name ?? ''}`}
      description="Rate each category from 1 to 5 stars."
      footer={
        <div className="flex justify-end gap-3">
          <Button variant="ghost" onClick={onClose}>Cancel</Button>
          <Button onClick={submit} loading={saving}>Submit review</Button>
        </div>
      }
    >
      <div className="space-y-3">
        {error && <p className="rounded-btn bg-danger-soft px-3 py-2 text-sm text-danger">{error}</p>}
        <div className="space-y-2 rounded-btn bg-canvas p-4">
          {CATEGORIES.map(([key, label]) => (
            <StarRatingInput key={key} label={label} value={scores[key]} onChange={(v) => setScores((s) => ({ ...s, [key]: v }))} />
          ))}
        </div>
        <Field label="Title (optional)" value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Sum it up" />
        <Textarea label="Your review" rows={4} value={comment} onChange={(e) => setComment(e.target.value)} placeholder="Share the details of your experience…" />
      </div>
    </Modal>
  )
}
