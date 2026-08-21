import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import Card from '../../components/ui/Card'
import Button from '../../components/ui/Button'
import Icon from '../../components/ui/Icon'
import Alert from '../../components/ui/Alert'
import Modal from '../../components/ui/Modal'
import Tabs from '../../components/ui/Tabs'
import PageHeader from '../../components/ui/PageHeader'
import { Field, SelectField } from '../../components/ui/Field'
import { cn } from '../../lib/cn'
import { api, applyServerErrors, parseApiError } from '../../lib/api'
import { useAuth } from '../../context/AuthContext'
import { useTheme } from '../../context/ThemeContext'

const DELETE_PHRASE = 'delete my account'

/** Shared form scaffold: success/error banners + submit wiring. */
function useSettingsForm(defaults) {
  const form = useForm({ defaultValues: defaults })
  const [saved, setSaved] = useState(null)
  const [formError, setFormError] = useState(null)

  const submit = (fn) =>
    form.handleSubmit(async (values) => {
      setSaved(null)
      setFormError(null)
      try {
        const message = await fn(values)
        setSaved(message ?? 'Saved.')
      } catch (err) {
        const parsed = parseApiError(err)
        setFormError(parsed.message)
        applyServerErrors(parsed.errors, form.setError)
      }
    })

  return { ...form, saved, formError, submit }
}

function AccountTab() {
  const { t } = useTranslation()
  const { user, refreshUser } = useAuth()
  const { register, submit, saved, formError, formState } = useSettingsForm({
    first_name: user.first_name,
    last_name: user.last_name,
    phone: user.phone ?? '',
    country: user.country ?? '',
  })

  return (
    <form
      onSubmit={submit(async (values) => {
        await api.put('/settings/account', values)
        await refreshUser()
        return t('settings.accountUpdated')
      })}
      className="space-y-5"
    >
      {saved && <Alert tone="success">{saved}</Alert>}
      {formError && <Alert tone="error">{formError}</Alert>}
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label={t('settings.firstName')} error={formState.errors.first_name?.message} {...register('first_name')} />
        <Field label={t('settings.lastName')} error={formState.errors.last_name?.message} {...register('last_name')} />
        <Field label={t('settings.phone')} error={formState.errors.phone?.message} {...register('phone')} />
        <Field label={t('settings.country')} error={formState.errors.country?.message} {...register('country')} />
      </div>
      <div className="flex justify-end">
        <Button type="submit" loading={formState.isSubmitting}>{t('settings.saveChanges')}</Button>
      </div>

      {user.account_type !== 'admin' && <DeleteAccountSection />}
    </form>
  )
}

/**
 * Danger zone: permanently delete the signed-in user's own account. The action
 * only fires once the exact phrase is typed, then the session is cleared.
 */
function DeleteAccountSection() {
  const { t } = useTranslation()
  const { logout } = useAuth()
  const [open, setOpen] = useState(false)
  const [phrase, setPhrase] = useState('')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState(null)

  const canDelete = phrase.trim().toLowerCase() === DELETE_PHRASE

  function close() {
    if (busy) return
    setOpen(false)
    setPhrase('')
    setError(null)
  }

  async function confirmDelete() {
    if (!canDelete) return
    setBusy(true)
    setError(null)
    try {
      await api.delete('/settings/account', { data: { confirmation: phrase.trim() } })
      // Account and token are gone server-side; clear the local session.
      await logout()
      // logout() redirects via auth state; nothing more to do here.
    } catch (err) {
      setError(parseApiError(err).message)
      setBusy(false)
    }
  }

  return (
    <div className="mt-8 rounded-card border border-danger/30 bg-danger-soft/40 p-5">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 className="flex items-center gap-2 font-bold text-danger">
            <Icon name="TriangleAlert" className="size-4" /> {t('settings.deleteAccount')}
          </h3>
          <p className="mt-1 max-w-prose text-sm text-muted">
            {t('settings.deleteAccountDescription')}
          </p>
        </div>
        <Button type="button" variant="danger" size="sm" onClick={() => setOpen(true)}>
          <Icon name="Trash2" className="size-4" /> {t('settings.deleteAccount')}
        </Button>
      </div>

      <Modal
        open={open}
        onClose={close}
        title={t('settings.deleteAccountConfirm')}
        description={t('settings.deleteAccountConfirmDescription')}
        footer={
          <>
            <Button variant="ghost" size="sm" onClick={close} disabled={busy}>{t('common.cancel')}</Button>
            <Button variant="danger" size="sm" onClick={confirmDelete} loading={busy} disabled={!canDelete}>
              {t('settings.deleteMyAccount')}
            </Button>
          </>
        }
      >
        <div className="space-y-3">
          {error && <Alert tone="error">{error}</Alert>}
          <p className="text-sm text-ink">
            {t('settings.deleteAccountWarning')} <span className="font-bold">{DELETE_PHRASE}</span> {t('common.below')}.
          </p>
          <Field
            value={phrase}
            onChange={(e) => setPhrase(e.target.value)}
            placeholder={DELETE_PHRASE}
            autoComplete="off"
            aria-label={`Type ${DELETE_PHRASE} to confirm`}
          />
        </div>
      </Modal>
    </div>
  )
}

function EmailTab() {
  const { t } = useTranslation()
  const { user, refreshUser } = useAuth()
  const { register, submit, saved, formError, formState, reset } = useSettingsForm({
    email: user.email,
  })

  return (
    <form
      onSubmit={submit(async (values) => {
        await api.put('/settings/email', values)
        await refreshUser()
        reset({ email: values.email })
        return t('settings.emailUpdateSuccess')
      })}
      className="space-y-5"
    >
      {saved && <Alert tone="success">{saved}</Alert>}
      {formError && <Alert tone="error">{formError}</Alert>}
      {!user.email_verified && (
        <Alert tone="warning">{t('settings.emailNotVerified')}</Alert>
      )}
      <Field label={t('settings.emailAddress')} type="email" error={formState.errors.email?.message} {...register('email')} />
      <div className="flex justify-end">
        <Button type="submit" loading={formState.isSubmitting}>{t('settings.updateEmail')}</Button>
      </div>
    </form>
  )
}

function PasswordTab() {
  const { t } = useTranslation()
  const { register, submit, saved, formError, formState, reset } = useSettingsForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  })

  return (
    <form
      onSubmit={submit(async (values) => {
        await api.put('/settings/password', values)
        reset({ current_password: '', password: '', password_confirmation: '' })
        return t('settings.passwordChangeSuccess')
      })}
      className="space-y-5"
    >
      {saved && <Alert tone="success">{saved}</Alert>}
      {formError && <Alert tone="error">{formError}</Alert>}
      <Field label={t('settings.currentPassword')} type="password" error={formState.errors.current_password?.message} {...register('current_password')} />
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label={t('settings.newPassword')} type="password" error={formState.errors.password?.message} {...register('password')} />
        <Field label={t('settings.confirmNewPassword')} type="password" {...register('password_confirmation')} />
      </div>
      <p className="text-sm text-muted">
        {t('settings.passwordRequirements')}
      </p>
      <div className="flex justify-end">
        <Button type="submit" loading={formState.isSubmitting}>{t('settings.changePassword')}</Button>
      </div>
    </form>
  )
}

const THEMES = [
  { value: 'light', label: 'light', icon: 'Sun' },
  { value: 'dark', label: 'dark', icon: 'Moon' },
  { value: 'system', label: 'system', icon: 'Monitor' },
]

function PreferencesTab() {
  const { t } = useTranslation()
  const { user, refreshUser } = useAuth()
  const { setPreference } = useTheme()
  const { i18n } = useTranslation()
  const { register, submit, saved, formError, formState, watch, setValue } = useSettingsForm({
    locale: user.preferences?.locale ?? 'en',
    timezone: user.preferences?.timezone ?? 'Africa/Dar_es_Salaam',
    theme: user.preferences?.theme ?? 'system',
  })

  const theme = watch('theme')

  const timezones = [
    'Africa/Dar_es_Salaam', 'Africa/Nairobi', 'Africa/Lagos', 'Africa/Cairo',
    'Europe/London', 'Europe/Paris', 'America/New_York', 'Asia/Dubai', 'UTC',
  ]

  return (
    <form
      onSubmit={submit(async (values) => {
        if (values.locale) {
          i18n.changeLanguage(values.locale)
          localStorage.setItem('i18nextLng', values.locale)
        }
        await api.put('/settings/preferences', values)
        await refreshUser()
        return t('settings.preferencesUpdated')
      })}
      className="space-y-6"
    >
      {saved && <Alert tone="success">{saved}</Alert>}
      {formError && <Alert tone="error">{formError}</Alert>}

      <div className="grid gap-5 sm:grid-cols-2">
        <SelectField label={t('settings.language')} {...register('locale')}>
          <option value="en">English</option>
          <option value="sw">Kiswahili</option>
          <option value="fr">Francais</option>
        </SelectField>
        <SelectField label={t('settings.timezone')} {...register('timezone')}>
          {timezones.map((tz) => (
            <option key={tz} value={tz}>
              {tz.replace('_', ' ')}
            </option>
          ))}
        </SelectField>
      </div>

      <div>
        <p className="mb-2 text-sm font-semibold text-ink">{t('settings.theme')}</p>
        <div className="grid max-w-md grid-cols-3 gap-2">
          {THEMES.map((themeOption) => {
            const active = theme === themeOption.value
            return (
              <button
                key={themeOption.value}
                type="button"
                onClick={() => {
                  setValue('theme', themeOption.value, { shouldDirty: true })
                  setPreference(themeOption.value)
                }}
                className={cn(
                  'flex flex-col items-center gap-2 rounded-card border p-4 text-sm font-semibold transition-colors',
                  active
                    ? 'border-navy-600 bg-navy-50 text-navy-700'
                    : 'border-line text-muted hover:border-navy-200',
                )}
              >
                <Icon name={themeOption.icon} className="size-5" />
                {t(`settings.${themeOption.label}`)}
              </button>
            )
          })}
        </div>
      </div>

      <div className="flex justify-end">
        <Button type="submit" loading={formState.isSubmitting}>{t('settings.savePreferences')}</Button>
      </div>
    </form>
  )
}

export default function Settings() {
  const { t } = useTranslation()
  const [tab, setTab] = useState('account')

  const TABS = [
    { value: 'account', label: t('settings.account') },
    { value: 'email', label: t('settings.email') },
    { value: 'password', label: t('settings.password') },
    { value: 'preferences', label: t('settings.preferences') },
  ]

  return (
    <div className="space-y-6">
      <PageHeader title={t('settings.settings')} description={t('settings.settingsDescription')} />

      <Card className="overflow-hidden">
        <div className="px-6 pt-2">
          <Tabs tabs={TABS} value={tab} onChange={setTab} />
        </div>
        <div className="p-6 md:p-8">
          {tab === 'account' && <AccountTab />}
          {tab === 'email' && <EmailTab />}
          {tab === 'password' && <PasswordTab />}
          {tab === 'preferences' && <PreferencesTab />}
        </div>
      </Card>
    </div>
  )
}
