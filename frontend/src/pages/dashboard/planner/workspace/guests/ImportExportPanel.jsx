import { useRef, useState } from 'react'
import Button from '../../../../../components/ui/Button'
import Card from '../../../../../components/ui/Card'
import Icon from '../../../../../components/ui/Icon'
import Alert from '../../../../../components/ui/Alert'
import { api } from '../../../../../lib/api'
import { formatDate } from '../../../../../lib/format'

export default function ImportExportPanel({ eventId, event, hideHeader = false }) {
  const fileRef = useRef(null)
  const [report, setReport] = useState(null)
  const [busy, setBusy] = useState(false)
  const [err, setErr] = useState(null)

  async function onFile(e) {
    const file = e.target.files?.[0]
    if (!file) return
    setBusy(true); setErr(null); setReport(null)
    try {
      const form = new FormData()
      form.append('file', file)
      const r = await api.post(`/events/${eventId}/guests/import`, form)
      setReport(r.data.data)
    } catch (ex) {
      setErr(ex.response?.data?.message ?? 'Import failed. Check the file format.')
    } finally {
      setBusy(false)
      if (fileRef.current) fileRef.current.value = ''
    }
  }

  async function exportCsv() {
    const r = await api.get(`/events/${eventId}/guests/export`, { responseType: 'blob' })
    const url = URL.createObjectURL(r.data)
    const link = document.createElement('a')
    link.href = url
    link.download = `guests-${event?.event_code ?? eventId}.csv`
    link.click()
    URL.revokeObjectURL(url)
  }

  async function exportPdf() {
    const r = await api.get(`/events/${eventId}/guests`)
    const guests = r.data.data.guests
    const rows = guests.map((g) => `<tr><td>${g.full_name}</td><td>${g.category ?? ''}</td><td>${g.email ?? g.phone ?? ''}</td><td>${g.rsvp_status_label}</td><td>${g.checkin_status_label}</td></tr>`).join('')
    const win = window.open('', '_blank')
    if (!win) return
    win.document.write(`<html><head><title>Guest list — ${event?.title ?? ''}</title>
      <style>body{font-family:Arial,sans-serif;padding:32px;color:#1e293b}h1{color:#1e3a8a}
      table{width:100%;border-collapse:collapse;font-size:13px;margin-top:12px}
      th,td{border:1px solid #e2e8f0;padding:6px 10px;text-align:left}th{background:#f8fafc}</style></head>
      <body><h1>${event?.title ?? 'Guest list'}</h1><p>${event?.event_code ?? ''} · ${event?.event_date ? formatDate(event.event_date) : ''} · ${guests.length} guests</p>
      <table><thead><tr><th>Name</th><th>Category</th><th>Contact</th><th>RSVP</th><th>Check-in</th></tr></thead><tbody>${rows}</tbody></table>
      <script>window.onload=()=>window.print()</script></body></html>`)
    win.document.close()
  }

  return (
    <div className="space-y-5">
      {!hideHeader && (
        <div>
          <h2 className="text-lg font-extrabold text-ink">Import & Export</h2>
          <p className="text-sm text-muted">Bring guests in from a spreadsheet, or export the list for sharing.</p>
        </div>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
        <Card className="space-y-3 p-5">
          <h3 className="flex items-center gap-2 font-bold text-ink"><Icon name="FileUp" className="size-5 text-navy-700" /> Import from CSV</h3>
          <p className="text-sm text-muted">
            Include a header row with <code className="rounded bg-canvas px-1">name</code> (or
            <code className="mx-1 rounded bg-canvas px-1">first_name</code>/<code className="rounded bg-canvas px-1">last_name</code>),
            plus optional <code className="mx-1 rounded bg-canvas px-1">email</code>, <code className="rounded bg-canvas px-1">phone</code>,
            <code className="mx-1 rounded bg-canvas px-1">category</code>, <code className="rounded bg-canvas px-1">meal</code>. Duplicates are skipped automatically.
          </p>
          <input ref={fileRef} type="file" accept=".csv,text/csv" onChange={onFile} className="hidden" />
          <Button variant="secondary" onClick={() => fileRef.current?.click()} loading={busy}>
            <Icon name="Upload" className="size-4" /> Choose CSV file
          </Button>

          {err && <Alert tone="error">{err}</Alert>}
          {report && (
            <div className="space-y-2">
              <Alert tone="success">{report.imported} guests imported.</Alert>
              {report.duplicates?.length > 0 && (
                <div className="rounded-btn border border-line p-3 text-sm">
                  <p className="mb-1 font-semibold text-warning">{report.duplicates.length} duplicates skipped</p>
                  <p className="text-muted">{report.duplicates.join(', ')}</p>
                </div>
              )}
              {report.errors?.length > 0 && (
                <div className="rounded-btn border border-line p-3 text-sm">
                  <p className="mb-1 font-semibold text-danger">{report.errors.length} rows had errors</p>
                  <ul className="space-y-0.5 text-muted">
                    {report.errors.map((e, i) => <li key={i}>Row {e.row}: {e.message}</li>)}
                  </ul>
                </div>
              )}
            </div>
          )}
        </Card>

        <Card className="space-y-3 p-5">
          <h3 className="flex items-center gap-2 font-bold text-ink"><Icon name="Download" className="size-5 text-navy-700" /> Export</h3>
          <p className="text-sm text-muted">Download the full guest list including RSVP and check-in status.</p>
          <div className="flex flex-wrap gap-2">
            <Button variant="secondary" onClick={exportCsv}><Icon name="FileText" className="size-4" /> Export CSV</Button>
            <Button variant="secondary" onClick={exportPdf}><Icon name="FileText" className="size-4" /> Export PDF</Button>
          </div>
          <p className="text-xs text-muted">CSV opens in Excel or Google Sheets. PDF opens a print-ready view.</p>
        </Card>
      </div>
    </div>
  )
}
