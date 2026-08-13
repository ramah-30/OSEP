import { useEffect, useState } from 'react'
import Modal from '../ui/Modal'
import Button from '../ui/Button'
import { Field } from '../ui/Field'
import Textarea from '../ui/Textarea'
import { api, parseApiError } from '../../lib/api'

/** Start a conversation with a provider from their storefront. */
export default function MessageComposeModal({ open, onClose, provider, onSent }) {
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (open) {
      setSubject('')
      setBody('')
      setError(null)
    }
  }, [open])

  const submit = async () => {
    setSaving(true)
    setError(null)
    try {
      await api.post('/marketplace/messages', {
        provider_type: provider.type,
        provider_id: provider.id,
        subject: subject || null,
        body,
      })
      onSent?.()
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
      title={`Message ${provider?.name ?? ''}`}
      footer={
        <div className="flex justify-end gap-3">
          <Button variant="ghost" onClick={onClose}>Cancel</Button>
          <Button onClick={submit} loading={saving} disabled={!body.trim()}>Send</Button>
        </div>
      }
    >
      <div className="space-y-4">
        {error && <p className="rounded-btn bg-danger-soft px-3 py-2 text-sm text-danger">{error}</p>}
        <Field label="Subject (optional)" value={subject} onChange={(e) => setSubject(e.target.value)} />
        <Textarea label="Message" rows={5} value={body} onChange={(e) => setBody(e.target.value)} placeholder="Write your message…" />
      </div>
    </Modal>
  )
}
