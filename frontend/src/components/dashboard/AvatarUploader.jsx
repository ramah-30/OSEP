import { useRef, useState } from 'react'
import Avatar from '../ui/Avatar'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import { api, parseApiError } from '../../lib/api'
import { useAuth } from '../../context/AuthContext'

const MAX_BYTES = 4 * 1024 * 1024

/**
 * Picks an image, previews it locally, then uploads on confirm. On success the
 * session user is refreshed so the new avatar shows everywhere at once.
 */
export default function AvatarUploader({ label = 'Profile photo', shape = 'round' }) {
  const { user, refreshUser } = useAuth()
  const inputRef = useRef(null)
  const [preview, setPreview] = useState(null)
  const [file, setFile] = useState(null)
  const [error, setError] = useState(null)
  const [saving, setSaving] = useState(false)

  const onPick = (event) => {
    const picked = event.target.files?.[0]
    if (!picked) return

    setError(null)

    if (!picked.type.startsWith('image/')) {
      setError('Choose an image file (JPG, PNG or WebP).')
      return
    }
    if (picked.size > MAX_BYTES) {
      setError('That image is larger than 4 MB.')
      return
    }

    setFile(picked)
    setPreview(URL.createObjectURL(picked))
  }

  const cancel = () => {
    setFile(null)
    setPreview(null)
    setError(null)
    if (inputRef.current) inputRef.current.value = ''
  }

  const upload = async () => {
    if (!file) return

    setSaving(true)
    setError(null)

    const form = new FormData()
    form.append('image', file)

    try {
      await api.post('/profile/image', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      await refreshUser()
      cancel()
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
      {shape === 'round' ? (
        <Avatar src={preview ?? user.avatar_url} name={user.full_name} initials={user.initials} size="xl" />
      ) : (
        <span className="grid size-24 place-items-center overflow-hidden rounded-card border border-line bg-canvas">
          {preview ?? user.avatar_url ? (
            <img src={preview ?? user.avatar_url} alt="" className="size-full object-cover" />
          ) : (
            <Icon name="Building2" className="size-8 text-muted" />
          )}
        </span>
      )}

      <div className="space-y-2">
        <p className="text-sm font-semibold text-ink">{label}</p>
        <input
          ref={inputRef}
          type="file"
          accept="image/png,image/jpeg,image/webp"
          onChange={onPick}
          className="hidden"
        />

        {file ? (
          <div className="flex gap-2">
            <Button size="sm" onClick={upload} loading={saving}>
              <Icon name="Upload" className="size-4" />
              Save photo
            </Button>
            <Button size="sm" variant="ghost" onClick={cancel} disabled={saving}>
              Cancel
            </Button>
          </div>
        ) : (
          <Button size="sm" variant="secondary" onClick={() => inputRef.current?.click()}>
            <Icon name="Camera" className="size-4" />
            Change photo
          </Button>
        )}

        <p className="text-xs text-muted">JPG, PNG or WebP · up to 4 MB</p>
        {error && <p className="text-sm text-danger">{error}</p>}
      </div>
    </div>
  )
}
