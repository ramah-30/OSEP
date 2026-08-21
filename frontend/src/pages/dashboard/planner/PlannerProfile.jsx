import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
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

function BookingLinkCard({ slug, t }) {
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
          <p className="font-semibold text-ink">{t('profile.yourBookingLink')}</p>
          <p className="mt-0.5 text-sm text-muted">{t('profile.shareBookingLink')}</p>
          <div className="mt-3 flex items-center gap-2 rounded-btn border border-line bg-neutral-50 px-3 py-2 dark:bg-neutral-900">
            <span className="min-w-0 flex-1 truncate text-sm text-muted">{url}</span>
            <button
              type="button"
              onClick={copy}
              className="shrink-0 rounded p-1 text-muted transition-colors hover:text-ink"
              aria-label={t('profile.copyBookingLink')}
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
  const { t } = useTranslation()
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
      <PageHeader title={t('profile.profile')} description={t('profile.profileDescription')} />

      <LoadState loading={loading} error={error} onRetry={reload}>
        <Card className="p-6 md:p-8">
          <AvatarUploader />
        </Card>

        {data?.profile?.booking_slug && (
          <BookingLinkCard slug={data.profile.booking_slug} t={t} />
        )}

        <Card className="p-6 md:p-8">
          {saved && (
            <Alert tone="success" className="mb-6">
              {t('profile.profileUpdated')}
            </Alert>
          )}
          {formError && (
            <Alert tone="error" className="mb-6">
              {formError}
            </Alert>
          )}

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            <div className="grid gap-5 sm:grid-cols-2">
              <Field label={t('profile.companyName')} error={errors.company_name?.message} {...register('company_name')} />
              <Field
                label={t('profile.yearsOfExperience')}
                type="number"
                min="0"
                error={errors.experience_years?.message}
                {...register('experience_years')}
              />
              <Field label={t('profile.specialization')} error={errors.specialization?.message} {...register('specialization')} />
              <Field label={t('profile.location')} error={errors.location?.message} {...register('location')} />
              <Field
                label={t('profile.website')}
                type="url"
                placeholder="https://"
                className="sm:col-span-2"
                error={errors.website?.message}
                {...register('website')}
              />
            </div>
            <Textarea label={t('profile.aboutStudio')} rows={5} error={errors.bio?.message} {...register('bio')} />

            <div className="flex justify-end">
              <Button type="submit" loading={isSubmitting} disabled={!isDirty && !saved}>
                <Icon name="Check" className="size-4" />
                {t('common.saveChanges')}
              </Button>
            </div>
          </form>
        </Card>
      </LoadState>
    </div>
  )
}
