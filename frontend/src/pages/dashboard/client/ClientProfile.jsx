import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import Card from '../../../components/ui/Card'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import Alert from '../../../components/ui/Alert'
import PageHeader from '../../../components/ui/PageHeader'
import { Field, SelectField } from '../../../components/ui/Field'
import AvatarUploader from '../../../components/dashboard/AvatarUploader'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { api, applyServerErrors, parseApiError } from '../../../lib/api'

// Wedding-only platform for now.
const EVENT_TYPES = ['Wedding']
const COMMS = ['Email', 'Phone', 'WhatsApp', 'SMS']

export default function ClientProfile() {
  const { data, loading, error, reload } = useResource('/profile')
  const [saved, setSaved] = useState(false)
  const [formError, setFormError] = useState(null)
  const [types, setTypes] = useState([])

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm()

  useEffect(() => {
    if (data?.profile) {
      const p = data.profile
      reset({ communication_preference: p.communication_preference ?? '', location: p.location ?? '' })
      setTypes(p.preferred_event_types ?? [])
    }
  }, [data, reset])

  const toggleType = (type) =>
    setTypes((current) =>
      current.includes(type) ? current.filter((t) => t !== type) : [...current, type],
    )

  const onSubmit = async (values) => {
    setSaved(false)
    setFormError(null)
    try {
      await api.put('/profile', { ...values, preferred_event_types: types })
      setSaved(true)
      reload()
    } catch (err) {
      const parsed = parseApiError(err)
      setFormError(parsed.message)
      applyServerErrors(parsed.errors, setError)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Profile" description="Your details and event preferences." />

      <LoadState loading={loading} error={error} onRetry={reload}>
        <Card className="p-6 md:p-8">
          <AvatarUploader />
        </Card>

        <Card className="p-6 md:p-8">
          {saved && <Alert tone="success" className="mb-6">Your profile has been updated.</Alert>}
          {formError && <Alert tone="error" className="mb-6">{formError}</Alert>}

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            <div className="grid gap-5 sm:grid-cols-2">
              <SelectField label="Communication preference" {...register('communication_preference')}>
                <option value="">No preference</option>
                {COMMS.map((c) => (
                  <option key={c} value={c}>
                    {c}
                  </option>
                ))}
              </SelectField>
              <Field label="Location" error={errors.location?.message} {...register('location')} />
            </div>

            <div>
              <p className="mb-2 text-sm font-semibold text-ink">Preferred event types</p>
              <div className="flex flex-wrap gap-2">
                {EVENT_TYPES.map((type) => {
                  const active = types.includes(type)
                  return (
                    <button
                      key={type}
                      type="button"
                      onClick={() => toggleType(type)}
                      className={
                        active
                          ? 'rounded-full border border-navy-600 bg-navy-50 px-3.5 py-1.5 text-sm font-semibold text-navy-700'
                          : 'rounded-full border border-line px-3.5 py-1.5 text-sm font-medium text-muted transition-colors hover:border-navy-200'
                      }
                    >
                      {active && <Icon name="Check" className="mr-1 inline size-3.5" />}
                      {type}
                    </button>
                  )
                })}
              </div>
            </div>

            <div className="flex justify-end">
              <Button type="submit" loading={isSubmitting}>
                <Icon name="Check" className="size-4" />
                Save changes
              </Button>
            </div>
          </form>
        </Card>
      </LoadState>
    </div>
  )
}
