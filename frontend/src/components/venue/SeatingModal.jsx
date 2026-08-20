import { useEffect, useState } from 'react'
import Modal from '../ui/Modal'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import ListboxSelect from '../ui/ListboxSelect'

/**
 * Assign guests (or free-text labels like "Reserved") to a table's seats.
 * Requires the object to be persisted first, so seating can reference its id.
 */
export default function SeatingModal({ open, object, guests = [], needsSave, onRequestSave, onSave, saving, onClose }) {
  const [count, setCount] = useState(0)
  const [rows, setRows] = useState({})

  useEffect(() => {
    if (!object) return
    const seatCount = Number(object.properties?.seats) || object.seating?.length || 0
    setCount(seatCount)
    const map = {}
    for (const s of object.seating ?? []) map[s.seat_number] = { guest_id: s.guest_id ?? '', notes: s.notes ?? '' }
    setRows(map)
  }, [object])

  if (!object) return null

  function setRow(n, patch) {
    setRows((r) => ({ ...r, [n]: { ...r[n], ...patch } }))
  }

  function save() {
    const seats = []
    for (let n = 1; n <= count; n += 1) {
      const row = rows[n]
      if (row && (row.guest_id || row.notes)) {
        seats.push({ seat_number: n, guest_id: row.guest_id ? Number(row.guest_id) : null, notes: row.notes || null })
      }
    }
    onSave(seats, count)
  }

  return (
    <Modal open={open} onClose={onClose} title={`Seating · ${object.object_name || 'Table'}`}
      description={needsSave ? undefined : 'Assign a guest or a label to each seat.'}
      footer={
        needsSave ? (
          <Button size="sm" onClick={onRequestSave} loading={saving}>Save layout first</Button>
        ) : (
          <>
            <Button variant="ghost" size="sm" onClick={onClose}>Cancel</Button>
            <Button size="sm" onClick={save} loading={saving}>Save seating</Button>
          </>
        )
      }
    >
      {needsSave ? (
        <p className="text-sm text-muted">Save the layout once so this table has an id, then you can assign seats.</p>
      ) : (
        <div className="space-y-3">
          <div className="flex items-center gap-2">
            <span className="text-sm font-semibold text-ink">Seats</span>
            <button type="button" onClick={() => setCount((c) => Math.max(0, c - 1))} className="grid size-8 place-items-center rounded-btn border border-line hover:bg-canvas"><Icon name="ChevronDown" className="size-4" /></button>
            <span className="w-8 text-center text-sm font-bold">{count}</span>
            <button type="button" onClick={() => setCount((c) => Math.min(60, c + 1))} className="grid size-8 place-items-center rounded-btn border border-line hover:bg-canvas"><Icon name="Plus" className="size-4" /></button>
          </div>

          <div className="max-h-80 space-y-2 overflow-y-auto pr-1">
            {Array.from({ length: count }, (_, i) => i + 1).map((n) => (
              <div key={n} className="flex items-center gap-2">
                <span className="w-14 shrink-0 text-xs font-semibold text-muted">Seat {n}</span>
                <ListboxSelect
                  className="min-w-0 flex-1"
                  heightClass="h-9"
                  placeholder="— guest —"
                  value={rows[n]?.guest_id ?? ''}
                  onChange={(e) => setRow(n, { guest_id: e.target.value })}
                >
                  <option value="">— guest —</option>
                  {guests.map((g) => <option key={g.id} value={g.id}>{g.full_name}</option>)}
                </ListboxSelect>
                <input
                  value={rows[n]?.notes ?? ''}
                  onChange={(e) => setRow(n, { notes: e.target.value })}
                  placeholder="Label"
                  className="h-9 w-24 rounded-btn border border-line bg-surface px-2 text-sm"
                />
              </div>
            ))}
            {count === 0 && <p className="text-sm text-muted">Set the number of seats above.</p>}
          </div>
        </div>
      )}
    </Modal>
  )
}
