import Button from '../ui/Button'
import Icon from '../ui/Icon'
import { Field, SelectField } from '../ui/Field'
import Textarea from '../ui/Textarea'
import { isTable, labelForType } from '../../lib/venueCatalog'
import { VENUE_SETTING_OPTIONS } from '../../lib/eventConstants'

/**
 * The right-hand inspector: venue properties when nothing is selected, otherwise
 * the selected object's editable properties.
 */
export default function PropertiesPanel({ layout, selected, layers, onLayoutChange, onObjectChange, onDelete, onEditSeating }) {
  if (!selected) {
    return (
      <div className="space-y-3">
        <p className="text-xs font-bold uppercase tracking-wide text-muted">Venue properties</p>
        <Field label="Venue name" value={layout.venue_name ?? ''} onChange={(e) => onLayoutChange({ venue_name: e.target.value })} />
        <Field label="Venue type" value={layout.venue_type ?? ''} onChange={(e) => onLayoutChange({ venue_type: e.target.value })} />
        <SelectField label="Setting" value={layout.setting ?? ''} onChange={(e) => onLayoutChange({ setting: e.target.value })}>
          <option value="">Not set</option>
          {VENUE_SETTING_OPTIONS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
        </SelectField>
        <div className="grid grid-cols-2 gap-2">
          <Field type="number" min="0" label={`Width (${layout.unit})`} value={layout.width ?? 0} onChange={(e) => onLayoutChange({ width: Number(e.target.value) })} />
          <Field type="number" min="0" label={`Length (${layout.unit})`} value={layout.height ?? 0} onChange={(e) => onLayoutChange({ height: Number(e.target.value) })} />
        </div>
        <div className="grid grid-cols-2 gap-2">
          <SelectField label="Unit" value={layout.unit ?? 'm'} onChange={(e) => onLayoutChange({ unit: e.target.value })}>
            <option value="m">metres</option>
            <option value="ft">feet</option>
          </SelectField>
          <Field type="number" min="0" label="Max capacity" value={layout.max_capacity ?? ''} onChange={(e) => onLayoutChange({ max_capacity: e.target.value === '' ? null : Number(e.target.value) })} />
        </div>
        <div className="grid grid-cols-2 gap-2">
          <Field type="number" min="0" label="Entry points" value={layout.entry_points ?? ''} onChange={(e) => onLayoutChange({ entry_points: e.target.value === '' ? null : Number(e.target.value) })} />
          <Field type="number" min="0" label="Exit points" value={layout.exit_points ?? ''} onChange={(e) => onLayoutChange({ exit_points: e.target.value === '' ? null : Number(e.target.value) })} />
        </div>
        <p className="pt-2 text-xs text-muted">Select an object on the canvas to edit its properties.</p>
      </div>
    )
  }

  const table = isTable(selected.object_type)

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <p className="text-xs font-bold uppercase tracking-wide text-muted">{labelForType(selected.object_type)}</p>
        <button type="button" onClick={() => onDelete(selected.uid)} className="grid size-7 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger">
          <Icon name="Trash2" className="size-4" />
        </button>
      </div>

      <Field label="Name" value={selected.object_name ?? ''} onChange={(e) => onObjectChange(selected.uid, { object_name: e.target.value })} />

      <SelectField label="Layer" value={selected.layer} onChange={(e) => onObjectChange(selected.uid, { layer: e.target.value })}>
        {layers.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}
      </SelectField>

      <div className="grid grid-cols-2 gap-2">
        <Field type="number" min="0" step="0.1" label="Width (m)" value={selected.width} onChange={(e) => onObjectChange(selected.uid, { width: Number(e.target.value) })} />
        <Field type="number" min="0" step="0.1" label="Height (m)" value={selected.height} onChange={(e) => onObjectChange(selected.uid, { height: Number(e.target.value) })} />
      </div>

      <div className="grid grid-cols-2 gap-2">
        <Field type="number" step="1" label="Rotation (°)" value={selected.rotation ?? 0} onChange={(e) => onObjectChange(selected.uid, { rotation: Number(e.target.value) })} />
        <div>
          <label className="mb-1.5 block text-sm font-semibold text-ink">Color</label>
          <input type="color" value={selected.color ?? '#ffffff'} onChange={(e) => onObjectChange(selected.uid, { color: e.target.value })}
            className="h-12 w-full cursor-pointer rounded-btn border border-line bg-surface p-1" />
        </div>
      </div>

      {table && (
        <>
          <Field type="number" min="0" label="Seats" value={selected.properties?.seats ?? 0}
            onChange={(e) => onObjectChange(selected.uid, { properties: { ...selected.properties, seats: Number(e.target.value) } })} />
          <Button variant="secondary" size="sm" fullWidth onClick={() => onEditSeating(selected)}>
            <Icon name="Users" className="size-4" /> Edit seating
          </Button>
        </>
      )}

      <Textarea label="Notes" rows={2} value={selected.properties?.notes ?? ''}
        onChange={(e) => onObjectChange(selected.uid, { properties: { ...selected.properties, notes: e.target.value } })} />
    </div>
  )
}
