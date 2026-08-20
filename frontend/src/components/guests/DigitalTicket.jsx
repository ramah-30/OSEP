import { useRef } from 'react'
import { QRCodeCanvas } from 'qrcode.react'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import { formatDate } from '../../lib/format'

/**
 * A printable/downloadable digital event pass. The QR encodes the guest's
 * check-in token so the Check-in scanner can validate it. `ticket` is the payload
 * from GET /guests/{id}/ticket.
 */
export default function DigitalTicket({ ticket }) {
  const cardRef = useRef(null)
  const canvasWrap = useRef(null)
  const event = ticket.event ?? {}
  const token = ticket.qr_token ?? ''

  function downloadPng() {
    const canvas = canvasWrap.current?.querySelector('canvas')
    if (!canvas) return
    const link = document.createElement('a')
    link.download = `ticket-${ticket.guest_name?.replace(/\s+/g, '-') ?? 'guest'}.png`
    link.href = canvas.toDataURL('image/png')
    link.click()
  }

  function printTicket() {
    const win = window.open('', '_blank', 'width=480,height=680')
    if (!win) return
    const canvas = canvasWrap.current?.querySelector('canvas')
    const qrImg = canvas ? `<img src="${canvas.toDataURL('image/png')}" width="180" height="180" />` : ''
    win.document.write(`
      <html><head><title>Ticket — ${ticket.guest_name}</title>
      <style>body{font-family:Arial,sans-serif;text-align:center;padding:32px;color:#1e293b}
      h1{color:#1e3a8a;margin:0 0 4px} .muted{color:#64748b;font-size:14px}
      .row{margin:6px 0;font-size:14px} .code{font-family:monospace;font-size:12px;color:#64748b;margin-top:8px}
      .card{border:2px solid #e2e8f0;border-radius:16px;padding:24px;max-width:340px;margin:0 auto}</style>
      </head><body><div class="card">
      <h1>${event.title ?? 'Event'}</h1>
      <p class="muted">${event.event_code ?? ''}</p>
      <div style="margin:16px 0">${qrImg}</div>
      <div class="row"><strong>${ticket.guest_name}</strong></div>
      <div class="row">${ticket.ticket_type ?? 'Standard'}${ticket.seat_number ? ' · Seat ' + ticket.seat_number : ''}</div>
      <div class="row">${event.date ? formatDate(event.date) : ''} ${event.start_time ?? ''}</div>
      <div class="row">${event.venue ?? ''}</div>
      <div class="code">${token}</div>
      </div><script>window.onload=()=>{window.print()}</script></body></html>`)
    win.document.close()
  }

  return (
    <div className="space-y-4">
      <div ref={cardRef} className="mx-auto max-w-xs overflow-hidden rounded-2xl border border-line bg-surface">
        <div className="bg-navy-800 px-5 py-4 text-center text-white">
          <p className="text-base font-extrabold leading-tight">{event.title}</p>
          <p className="text-xs text-white/70">{event.event_code}</p>
        </div>
        <div className="flex flex-col items-center gap-3 p-5">
          <div ref={canvasWrap} className="rounded-xl bg-white p-2">
            {token
              ? <QRCodeCanvas value={token} size={168} level="M" includeMargin={false} />
              : <div className="grid size-[168px] place-items-center text-center text-xs text-muted">No ticket issued yet</div>}
          </div>
          <p className="text-lg font-extrabold text-ink">{ticket.guest_name}</p>
          <div className="grid w-full grid-cols-2 gap-2 text-sm">
            <Detail label="Ticket" value={ticket.ticket_type} />
            <Detail label="Seat" value={ticket.seat_number ?? '—'} />
            <Detail label="Date" value={event.date ? formatDate(event.date) : '—'} />
            <Detail label="Time" value={event.start_time ?? '—'} />
          </div>
          <div className="w-full rounded-btn bg-canvas px-3 py-2 text-center">
            <p className="text-[0.65rem] uppercase tracking-wide text-muted">Venue</p>
            <p className="text-sm font-semibold text-ink">{event.venue ?? '—'}</p>
          </div>
          <p className="break-all font-mono text-[0.65rem] text-muted">{token}</p>
        </div>
      </div>

      <div className="flex justify-center gap-2">
        <Button size="sm" variant="secondary" onClick={downloadPng} disabled={!token}>
          <Icon name="Download" className="size-4" /> PNG
        </Button>
        <Button size="sm" onClick={printTicket} disabled={!token}>
          <Icon name="Ticket" className="size-4" /> Print / PDF
        </Button>
      </div>
    </div>
  )
}

function Detail({ label, value }) {
  return (
    <div className="rounded-btn bg-canvas px-3 py-2">
      <p className="text-[0.65rem] uppercase tracking-wide text-muted">{label}</p>
      <p className="truncate text-sm font-semibold text-ink">{value}</p>
    </div>
  )
}
