import { useSearchParams, useOutletContext } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import Icon from '../../../../components/ui/Icon'
import { AskAiButton, GenerateAiButton } from '../../../../components/ai/InlineAiButtons'
import { cn } from '../../../../lib/cn'
import { GUEST_VIEWS } from '../../../../lib/guestConstants'
import GuestDashboardPanel from './guests/GuestDashboardPanel'
import GuestListPanel from './guests/GuestListPanel'
import InvitationsRsvpPanel from './guests/InvitationsRsvpPanel'
import SetupPanel from './guests/SetupPanel'

/**
 * The Guest Management, Invitation & RSVP workspace. A single tab with its own
 * deep-linkable sub-navigation (?view=) driving each panel; all panels fetch
 * their own data so switching views is snappy.
 */
export default function Guests() {
  const { t } = useTranslation()
  const { event } = useOutletContext()
  const [params, setParams] = useSearchParams()
  const view = GUEST_VIEWS.some((v) => v.value === params.get('view')) ? params.get('view') : 'overview'

  const setView = (value) => setParams((prev) => {
    const next = new URLSearchParams(prev)
    next.set('view', value)
    return next
  }, { replace: true })

  const panels = {
    overview: <GuestDashboardPanel eventId={event.id} />,
    guests: <GuestListPanel eventId={event.id} event={event} />,
    invitations: <InvitationsRsvpPanel eventId={event.id} />,
    setup: <SetupPanel eventId={event.id} />,
  }

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-end gap-2">
        <AskAiButton eventId={event.id} prompt={`How are RSVPs looking for ${event.title}? Who still needs a nudge?`} label={t('approvals.askAI')} />
        <GenerateAiButton templateKey="rsvp_reminder_email" eventId={event.id} label={t('approvals.rsvpReminder')} />
      </div>

      <nav className="flex gap-1 overflow-x-auto rounded-xl border border-line bg-surface p-1">
        {GUEST_VIEWS.map((v) => (
          <button
            key={v.value}
            type="button"
            onClick={() => setView(v.value)}
            className={cn(
              'flex shrink-0 items-center gap-1.5 rounded-btn px-3 py-2 text-sm font-semibold transition-colors',
              view === v.value ? 'bg-navy-800 text-white' : 'text-muted hover:bg-canvas hover:text-ink',
            )}
          >
            <Icon name={v.icon} className="size-4" />
            {v.label}
          </button>
        ))}
      </nav>

      {panels[view]}
    </div>
  )
}
