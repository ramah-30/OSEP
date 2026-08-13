import { useEffect, useState } from 'react'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Card from '../../../../components/ui/Card'
import Spinner from '../../../../components/ui/Spinner'
import { cn } from '../../../../lib/cn'
import { Table, THead, TH, TBody, TR, TD } from '../../../../components/ui/Table'
import { api, parseApiError } from '../../../../lib/api'
import { formatCurrency } from '../../../../lib/format'

const REPORTS = [
  { type: 'budget', label: 'Budget', icon: 'Wallet' },
  { type: 'expense', label: 'Expenses', icon: 'ReceiptText' },
  { type: 'revenue', label: 'Revenue', icon: 'TrendingUp' },
  { type: 'vendor-payments', label: 'Vendor payments', icon: 'Handshake' },
  { type: 'outstanding', label: 'Outstanding', icon: 'Clock' },
  { type: 'profit-loss', label: 'Profit & loss', icon: 'Scale' },
  { type: 'event-summary', label: 'Event summary', icon: 'LayoutGrid' },
]

export default function Reports() {
  const [type, setType] = useState('budget')
  const [report, setReport] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    let alive = true
    setLoading(true)
    setError(null)
    api.get(`/finance/reports/${type}`)
      .then((r) => alive && setReport(r.data.data.report))
      .catch((e) => alive && setError(parseApiError(e).message))
      .finally(() => alive && setLoading(false))
    return () => { alive = false }
  }, [type])

  function exportCsv() {
    if (!report) return
    const header = report.columns.map((c) => `"${c.label}"`).join(',')
    const lines = report.rows.map((row) =>
      report.columns.map((c) => `"${String(row[c.key] ?? '').replace(/"/g, '""')}"`).join(','),
    )
    const csv = [header, ...lines].join('\n')
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }))
    const a = document.createElement('a')
    a.href = url
    a.download = `${type}-report.csv`
    a.click()
    URL.revokeObjectURL(url)
  }

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Financial reports</h2>
          <p className="text-sm text-muted">Slice your finances and export to CSV or print to PDF.</p>
        </div>
        <div className="flex gap-2">
          <Button size="sm" variant="secondary" onClick={exportCsv} disabled={!report?.rows?.length}>
            <Icon name="Download" className="size-4" /> CSV
          </Button>
          <Button size="sm" variant="secondary" onClick={() => window.print()} disabled={!report?.rows?.length}>
            <Icon name="Printer" className="size-4" /> Print
          </Button>
        </div>
      </div>

      <div className="no-scrollbar flex gap-2 overflow-x-auto">
        {REPORTS.map((r) => (
          <button
            key={r.type}
            onClick={() => setType(r.type)}
            className={cn(
              'flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-2 text-sm font-semibold transition-colors',
              type === r.type ? 'border-navy-600 bg-navy-50 text-navy-800' : 'border-line bg-surface text-muted hover:text-ink',
            )}
          >
            <Icon name={r.icon} className="size-4" /> {r.label}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="grid min-h-64 place-items-center"><Spinner className="size-7" /></div>
      ) : error ? (
        <Card className="p-6 text-sm text-danger">{error}</Card>
      ) : report ? (
        <div>
          <h3 className="mb-3 text-base font-bold text-ink">{report.title}</h3>
          {report.rows.length ? (
            <Table>
              <THead>
                <TR>{report.columns.map((c) => <TH key={c.key} className={c.money ? 'text-right' : ''}>{c.label}</TH>)}</TR>
              </THead>
              <TBody>
                {report.rows.map((row, i) => (
                  <TR key={i}>
                    {report.columns.map((c) => (
                      <TD key={c.key} className={c.money ? 'text-right tabular-nums' : ''}>
                        {c.money ? formatCurrency(row[c.key]) : (row[c.key] ?? '—')}
                      </TD>
                    ))}
                  </TR>
                ))}
              </TBody>
            </Table>
          ) : (
            <Card className="p-6 text-sm text-muted">No data for this report yet.</Card>
          )}
        </div>
      ) : null}
    </div>
  )
}
