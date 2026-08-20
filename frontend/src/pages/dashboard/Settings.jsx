import { useState } from 'react'
import { useForm } from 'react-hook-form'
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

const TABS = [
  { value: 'account', label: 'Account' },
  { value: 'email', label: 'Email' },
  { value: 'password', label: 'Password' },
  { value: 'preferences', label: 'Preferences' },
]

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
        return 'Account details updated.'
      })}
      className="space-y-5"
    >
      {saved && <Alert tone="success">{saved}</Alert>}
      {formError && <Alert tone="error">{formError}</Alert>}
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="First name" error={formState.errors.first_name?.message} {...register('first_name')} />
        <Field label="Last name" error={formState.errors.last_name?.message} {...register('last_name')} />
        <Field label="Phone" error={formState.errors.phone?.message} {...register('phone')} />
        <Field label="Country" error={formState.errors.country?.message} {...register('country')} />
      </div>
      <div className="flex justify-end">
        <Button type="submit" loading={formState.isSubmitting}>Save changes</Button>
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
            <Icon name="TriangleAlert" className="size-4" /> Delete account
          </h3>
          <p className="mt-1 max-w-prose text-sm text-muted">
            Permanently delete your account and all related data — events, guests, budgets,
            messages and everything tied to it. This cannot be undone.
          </p>
        </div>
        <Button type="button" variant="danger" size="sm" onClick={() => setOpen(true)}>
          <Icon name="Trash2" className="size-4" /> Delete account
        </Button>
      </div>

      <Modal
        open={open}
        onClose={close}
        title="Delete your account?"
        description="This permanently removes your account and every record connected to it. There is no way to recover it."
        footer={
          <>
            <Button variant="ghost" size="sm" onClick={close} disabled={busy}>Cancel</Button>
            <Button variant="danger" size="sm" onClick={confirmDelete} loading={busy} disabled={!canDelete}>
              Delete my account
            </Button>
          </>
        }
      >
        <div className="space-y-3">
          {error && <Alert tone="error">{error}</Alert>}
          <p className="text-sm text-ink">
            To confirm, type <span className="font-bold">{DELETE_PHRASE}</span> below.
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
        return 'Email updated. Check your inbox to confirm the new address.'
      })}
      className="space-y-5"
    >
      {saved && <Alert tone="success">{saved}</Alert>}
      {formError && <Alert tone="error">{formError}</Alert>}
      {!user.email_verified && (
        <Alert tone="warning">Your email address is not confirmed yet.</Alert>
      )}
      <Field label="Email address" type="email" error={formState.errors.email?.message} {...register('email')} />
      <div className="flex justify-end">
        <Button type="submit" loading={formState.isSubmitting}>Update email</Button>
      </div>
    </form>
  )
}

function PasswordTab() {
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
        return 'Password changed. Other sessions have been signed out.'
      })}
      className="space-y-5"
    >
      {saved && <Alert tone="success">{saved}</Alert>}
      {formError && <Alert tone="error">{formError}</Alert>}
      <Field label="Current password" type="password" error={formState.errors.current_password?.message} {...register('current_password')} />
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="New password" type="password" error={formState.errors.password?.message} {...register('password')} />
        <Field label="Confirm new password" type="password" {...register('password_confirmation')} />
      </div>
      <p className="text-sm text-muted">
        Use at least 8 characters with upper &amp; lower case, a number and a symbol.
      </p>
      <div className="flex justify-end">
        <Button type="submit" loading={formState.isSubmitting}>Change password</Button>
      </div>
    </form>
  )
}

const THEMES = [
  { value: 'light', label: 'Light', icon: 'Sun' },
  { value: 'dark', label: 'Dark', icon: 'Moon' },
  { value: 'system', label: 'System', icon: 'Monitor' },
]

function PreferencesTab() {
  const { user, refreshUser } = useAuth()
  const { setPreference } = useTheme()
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
        await api.put('/settings/preferences', values)
        await refreshUser()
        return 'Preferences saved.'
      })}
      className="space-y-6"
    >
      {saved && <Alert tone="success">{saved}</Alert>}
      {formError && <Alert tone="error">{formError}</Alert>}

      <div className="grid gap-5 sm:grid-cols-2">
        <SelectField label="Language" {...register('locale')}>
          <option value="en">English</option>
          <option value="sw">Kiswahili</option>
          <option value="fr">Français</option>
        </SelectField>
        <SelectField label="Timezone" {...register('timezone')}>
          {timezones.map((tz) => (
            <option key={tz} value={tz}>
              {tz.replace('_', ' ')}
            </option>
          ))}
        </SelectField>
      </div>

      <div>
        <p className="mb-2 text-sm font-semibold text-ink">Theme</p>
        <div className="grid max-w-md grid-cols-3 gap-2">
          {THEMES.map((t) => {
            const active = theme === t.value
            return (
              <button
                key={t.value}
                type="button"
                onClick={() => {
                  setValue('theme', t.value, { shouldDirty: true })
                  setPreference(t.value)
                }}
                className={cn(
                  'flex flex-col items-center gap-2 rounded-card border p-4 text-sm font-semibold transition-colors',
                  active
                    ? 'border-navy-600 bg-navy-50 text-navy-700'
                    : 'border-line text-muted hover:border-navy-200',
                )}
              >
                <Icon name={t.icon} className="size-5" />
                {t.label}
              </button>
            )
          })}
        </div>
      </div>

      <div className="flex justify-end">
        <Button type="submit" loading={formState.isSubmitting}>Save preferences</Button>
      </div>
    </form>
  )
}

export default function Settings() {
  const [tab, setTab] = useState('account')

  return (
    <div className="space-y-6">
      <PageHeader title="Settings" description="Manage your account, security and preferences." />

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
