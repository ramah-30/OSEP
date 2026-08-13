/**
 * Client-side messaging "gateways" for reaching a guest directly through the
 * planner's own device — no paid API. WhatsApp uses the wa.me deep link and SMS
 * uses the sms: URI; both open the relevant app with the invite pre-filled.
 */

/** Digits only — wa.me needs a bare international number (no +, spaces or dashes). */
export function sanitizePhone(phone) {
  return String(phone ?? '').replace(/\D/g, '')
}

/** The public RSVP portal link for a guest. */
export function rsvpLink(guest) {
  return guest?.rsvp_token ? `${window.location.origin}/rsvp/${guest.rsvp_token}` : ''
}

/** A friendly invite message, personalised to the guest and event. */
export function inviteText(guest, event) {
  const name = guest?.first_name || (guest?.full_name || '').split(' ')[0] || 'there'
  const title = event?.title ? `"${event.title}"` : 'our event'
  const link = rsvpLink(guest)
  const parts = [
    `Hi ${name}! You're invited to ${title}.`,
    event?.event_date ? `Date: ${event.event_date}.` : null,
    link ? `Please RSVP here: ${link}` : null,
  ]
  return parts.filter(Boolean).join(' ')
}

/** wa.me deep link, or null when the guest has no phone. */
export function whatsappUrl(guest, event) {
  const phone = sanitizePhone(guest?.phone)
  if (!phone) return null
  return `https://wa.me/${phone}?text=${encodeURIComponent(inviteText(guest, event))}`
}

/** sms: deep link, or null when the guest has no phone. */
export function smsUrl(guest, event) {
  const phone = sanitizePhone(guest?.phone)
  if (!phone) return null
  return `sms:${phone}?body=${encodeURIComponent(inviteText(guest, event))}`
}
