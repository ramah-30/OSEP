import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import Card from '../../../components/ui/Card'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import Badge from '../../../components/ui/Badge'
import Alert from '../../../components/ui/Alert'
import PageHeader from '../../../components/ui/PageHeader'
import { Field } from '../../../components/ui/Field'
import Textarea from '../../../components/ui/Textarea'
import AvatarUploader from '../../../components/dashboard/AvatarUploader'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { api, applyServerErrors, parseApiError } from '../../../lib/api'

const VERIFY_TONE = { verified: 'emerald', pending: 'amber', rejected: 'danger' }

export default function VendorProfile() {
  const { data, loading, error, reload } = useResource('/profile')
  const [saved, setSaved] = useState(false)
  const [formError, setFormError] = useState(null)
  const profile = data?.profile

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm()

  useEffect(() => {
    if (profile) {
      reset({
        business_name: profile.business_name ?? '',
        location: profile.location ?? '',
        phone: profile.phone ?? '',
        website: profile.website ?? '',
        description: profile.description ?? '',
        instagram: profile.social_links?.instagram ?? '',
        facebook: profile.social_links?.facebook ?? '',
      })
    }
  }, [profile, reset])

  const onSubmit = async ({ instagram, facebook, ...values }) => {
    setSaved(false)
    setFormError(null)
    try {
      await api.put('/profile', {
        ...values,
        social_links: { instagram, facebook },
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
      <PageHeader
        title="Business Profile"
        description="This is what planners see when they discover your business."
        actions={
          profile && (
            <Badge tone={VERIFY_TONE[profile.verification_status] ?? 'muted'} dot>
              {profile.verification_status_label}
            </Badge>
          )
        }
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        <Card className="p-6 md:p-8">
          <AvatarUploader label="Business logo" shape="square" />
        </Card>

        <Card className="p-6 md:p-8">
          {saved && <Alert tone="success" className="mb-6">Your business profile has been updated.</Alert>}
          {formError && <Alert tone="error" className="mb-6">{formError}</Alert>}

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            <div className="grid gap-5 sm:grid-cols-2">
              <Field label="Business name" error={errors.business_name?.message} {...register('business_name')} />
              <Field label="Location" error={errors.location?.message} {...register('location')} />
              <Field label="Phone" error={errors.phone?.message} {...register('phone')} />
              <Field label="Website" type="url" placeholder="https://" error={errors.website?.message} {...register('website')} />
              <Field label="Instagram URL" placeholder="https://instagram.com/…" error={errors['social_links.instagram']?.message} {...register('instagram')} />
              <Field label="Facebook URL" placeholder="https://facebook.com/…" className="sm:col-span-2" {...register('facebook')} />
            </div>
            <Textarea label="Description" rows={5} error={errors.description?.message} {...register('description')} />

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
