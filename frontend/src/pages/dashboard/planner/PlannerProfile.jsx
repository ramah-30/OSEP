import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import Card from '../../../components/ui/Card'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import Alert from '../../../components/ui/Alert'
import PageHeader from '../../../components/ui/PageHeader'
import { Field } from '../../../components/ui/Field'
import Textarea from '../../../components/ui/Textarea'
import AvatarUploader from '../../../components/dashboard/AvatarUploader'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { api, applyServerErrors, parseApiError } from '../../../lib/api'

function BookingLinkCard({ slug }) {
  const [copied, setCopied] = useState(false)
  const url = `${window.location.origin}/book/${slug}`

  function copy() {
    navigator.clipboard.writeText(url).then(() => {
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    })
  }

  return (
    <Card className="p-6 md:p-8">
      <div className="flex items-start gap-3">
        <div className="grid size-10 shrink-0 place-items-center rounded-btn bg-navy-50 dark:bg-navy-950">
          <Icon name="Link2" className="size-5 text-navy-700 dark:text-navy-300" />
        </div>
        <div className="min-w-0 flex-1">
          <p className="font-semibold text-ink">Your booking link</p>
          <p className="mt-0.5 text-sm text-muted">Share this link so clients can book your services directly.</p>
          <div className="mt-3 flex items-center gap-2 rounded-btn border border-line bg-neutral-50 px-3 py-2 dark:bg-neutral-900">
            <span className="min-w-0 flex-1 truncate text-sm text-muted">{url}</span>
            <button
              type="button"
              onClick={copy}
              className="shrink-0 rounded p-1 text-muted transition-colors hover:text-ink"
              aria-label="Copy booking link"
            >
              <Icon name={copied ? 'Check' : 'Copy'} className="size-4" />
            </button>
          </div>
        </div>
      </div>
    </Card>
  )
}

export default function PlannerProfile() {
  const { data, loading, error, reload } = useResource('/profile')
  const [saved, setSaved] = useState(false)
  const [formError, setFormError] = useState(null)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting, isDirty },
  } = useForm()

  useEffect(() => {
    if (data?.profile) {
      const p = data.profile
      reset({
        company_name: p.company_name ?? '',
        experience_years: p.experience_years ?? '',
        specialization: p.specialization ?? '',
        location: p.location ?? '',
        website: p.website ?? '',
        bio: p.bio ?? '',
      })
    }
  }, [data, reset])

  const onSubmit = async (values) => {
    setSaved(false)
    setFormError(null)
    try {
      await api.put('/profile', {
        ...values,
        experience_years: values.experience_years === '' ? null : Number(values.experience_years),
      })
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
      <PageHeader title="Profile" description="How your studio appears across OSEP." />

      <LoadState loading={loading} error={error} onRetry={reload}>
        <Card className="p-6 md:p-8">
          <AvatarUploader />
        </Card>

        {data?.profile?.booking_slug && (
          <BookingLinkCard slug={data.profile.booking_slug} />
        )}

        <Card className="p-6 md:p-8">
          {saved && (
            <Alert tone="success" className="mb-6">
              Your profile has been updated.
            </Alert>
          )}
          {formError && (
            <Alert tone="error" className="mb-6">
              {formError}
            </Alert>
          )}

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            <div className="grid gap-5 sm:grid-cols-2">
              <Field label="Company name" error={errors.company_name?.message} {...register('company_name')} />
              <Field
                label="Years of experience"
                type="number"
                min="0"
                error={errors.experience_years?.message}
                {...register('experience_years')}
              />
              <Field label="Specialization" error={errors.specialization?.message} {...register('specialization')} />
              <Field label="Location" error={errors.location?.message} {...register('location')} />
              <Field
                label="Website"
                type="url"
                placeholder="https://"
                className="sm:col-span-2"
                error={errors.website?.message}
                {...register('website')}
              />
            </div>
            <Textarea label="About your studio" rows={5} error={errors.bio?.message} {...register('bio')} />

            <div className="flex justify-end">
              <Button type="submit" loading={isSubmitting} disabled={!isDirty && !saved}>
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
