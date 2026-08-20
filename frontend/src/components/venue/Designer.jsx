import { useCallback, useEffect, useRef, useState } from 'react'
import Icon from '../ui/Icon'
import Dropdown, { DropdownItem } from '../ui/Dropdown'
import { cn } from '../../lib/cn'
import { api } from '../../lib/api'
import { DEFAULT_LAYERS } from '../../lib/venueCatalog'
import { validateLayout } from '../../lib/venueValidation'
import DesignerCanvas from './DesignerCanvas'
import ObjectLibrary from './ObjectLibrary'
import PropertiesPanel from './PropertiesPanel'
import LayersPanel from './LayersPanel'
import StatsPanel from './StatsPanel'
import SeatingModal from './SeatingModal'

const uid = () => (crypto.randomUUID ? crypto.randomUUID() : `o-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`)

const LAYOUT_KEYS = ['venue_name', 'venue_type', 'setting', 'width', 'height', 'unit', 'max_capacity', 'entry_points', 'exit_points']

function normalize(layout) {
  return {
    objects: (layout.objects ?? []).map((o) => ({
      id: o.id, uid: o.uid, object_type: o.object_type, object_name: o.object_name ?? '',
      x: o.x, y: o.y, width: o.width, height: o.height, rotation: o.rotation ?? 0,
      color: o.color, layer: o.layer ?? 'furniture', properties: o.properties ?? {}, seating: o.seating ?? [],
    })),
    layoutProps: Object.fromEntries(LAYOUT_KEYS.map((k) => [k, layout[k]])),
    layers: layout.meta?.layers?.length ? layout.meta.layers : DEFAULT_LAYERS,
  }
}

/**
 * The full Venue Designer for one layout: toolbar, object library, canvas,
 * properties, layers, live stats and seating — with undo/redo and manual save.
 */
export default function Designer({ layout, eventId, guests, onSaved }) {
  const [doc, setDocState] = useState(() => normalize(layout))
  const [past, setPast] = useState([])
  const [future, setFuture] = useState([])
  const [selectedUid, setSelectedUid] = useState(null)
  const [view, setView] = useState({ zoom: 1, gridOn: layout.meta?.grid !== false })
  const [snapOn, setSnapOn] = useState(layout.meta?.snap !== false)
  const [dirty, setDirty] = useState(0)
  const [saving, setSaving] = useState(false)
  const [savedAt, setSavedAt] = useState(null)
  const [seatingFor, setSeatingFor] = useState(null)
  const [seatingSaving, setSeatingSaving] = useState(false)

  const docRef = useRef(doc)
  docRef.current = doc
  const canvasRef = useRef(null)

  // Reload when a different version is opened.
  useEffect(() => {
    setDocState(normalize(layout))
    setPast([]); setFuture([]); setSelectedUid(null); setDirty(0)
  }, [layout])

  const applyDoc = useCallback((next, { history = true } = {}) => {
    if (history) { setPast((p) => [...p.slice(-49), docRef.current]); setFuture([]) }
    setDocState(next)
    if (history) setDirty((d) => d + 1)
  }, [])

  // ---- mutations -------------------------------------------------------
  const addObject = (item) => {
    const w = item.w, h = item.h
    const cx = Math.max(0, (Number(doc.layoutProps.width) || 20) / 2 - w / 2)
    const cy = Math.max(0, (Number(doc.layoutProps.height) || 15) / 2 - h / 2)
    const obj = {
      uid: uid(), object_type: item.type, object_name: item.label,
      x: Math.round(cx), y: Math.round(cy), width: w, height: h, rotation: 0,
      color: item.color, layer: item.layer, properties: item.seats ? { seats: item.seats } : {}, seating: [],
    }
    applyDoc({ ...doc, objects: [...doc.objects, obj] })
    setSelectedUid(obj.uid)
  }

  const changeObject = (u, patch) =>
    applyDoc({ ...doc, objects: doc.objects.map((o) => (o.uid === u ? { ...o, ...patch } : o)) })

  const deleteObject = (u) => {
    applyDoc({ ...doc, objects: doc.objects.filter((o) => o.uid !== u) })
    setSelectedUid((s) => (s === u ? null : s))
  }

  const changeLayout = (patch) => applyDoc({ ...doc, layoutProps: { ...doc.layoutProps, ...patch } })
  const changeLayers = (layers) => applyDoc({ ...doc, layers })

  const undo = () => {
    if (!past.length) return
    const prev = past[past.length - 1]
    setFuture((f) => [docRef.current, ...f]); setPast((p) => p.slice(0, -1))
    setDocState(prev); setDirty((d) => d + 1)
  }
  const redo = () => {
    if (!future.length) return
    const nextDoc = future[0]
    setPast((p) => [...p, docRef.current]); setFuture((f) => f.slice(1))
    setDocState(nextDoc); setDirty((d) => d + 1)
  }

  // ---- save (autosave + manual) ---------------------------------------
  const save = useCallback(async () => {
    const current = docRef.current
    setSaving(true)
    try {
      const payload = {
        ...current.layoutProps,
        meta: { layers: current.layers, grid: view.gridOn, snap: snapOn },
        objects: current.objects.map((o) => ({
          uid: o.uid, object_type: o.object_type, object_name: o.object_name,
          x: o.x, y: o.y, width: o.width, height: o.height, rotation: o.rotation,
          color: o.color, layer: o.layer, properties: o.properties ?? {},
        })),
      }
      const r = await api.put(`/events/${eventId}/venue-layouts/${layout.id}`, payload)
      // Merge server ids back so seating can reference persisted objects.
      const idByUid = Object.fromEntries((r.data.data.layout.objects ?? []).map((o) => [o.uid, o.id]))
      setDocState((d) => ({ ...d, objects: d.objects.map((o) => (o.id ? o : { ...o, id: idByUid[o.uid] ?? o.id })) }))
      setSavedAt(new Date())
      setDirty(0) // changes are now persisted
      onSaved?.()
    } finally {
      setSaving(false)
    }
  }, [eventId, layout.id, view.gridOn, snapOn, onSaved])

  const isDirty = dirty > 0

  // Manual save only — warn before leaving with unsaved changes.
  useEffect(() => {
    if (!isDirty) return
    const handler = (e) => { e.preventDefault(); e.returnValue = '' }
    window.addEventListener('beforeunload', handler)
    return () => window.removeEventListener('beforeunload', handler)
  }, [isDirty])

  // ---- seating ---------------------------------------------------------
  async function saveSeating(seats, seatCount) {
    const obj = docRef.current.objects.find((o) => o.uid === seatingFor)
    if (!obj?.id) return
    setSeatingSaving(true)
    try {
      const r = await api.put(`/events/${eventId}/venue-layouts/${layout.id}/objects/${obj.id}/seating`, { seats })
      const seating = r.data.data.object.seating ?? []
      setDocState((d) => ({
        ...d,
        objects: d.objects.map((o) => (o.uid === obj.uid ? { ...o, seating, properties: { ...o.properties, seats: seatCount } } : o)),
      }))
      setSeatingFor(null)
      onSaved?.()
    } finally {
      setSeatingSaving(false)
    }
  }

  // ---- export ----------------------------------------------------------
  function exportPNG() {
    const url = canvasRef.current?.toDataURL({ pixelRatio: 2 })
    if (!url) return
    const a = document.createElement('a')
    a.href = url
    a.download = `${(doc.layoutProps.venue_name || 'layout').replace(/\s+/g, '-').toLowerCase()}.png`
    a.click()
  }
  function exportPDF() {
    const url = canvasRef.current?.toDataURL({ pixelRatio: 2 })
    if (!url) return
    const w = window.open('', '_blank')
    if (!w) return
    w.document.write(`<html><head><title>${doc.layoutProps.venue_name || 'Layout'}</title></head><body style="margin:0"><img src="${url}" style="width:100%"/><script>window.onload=()=>window.print()</script></body></html>`)
    w.document.close()
  }

  const { warnings, byUid, seats } = validateLayout(doc.objects, doc.layoutProps)
  const selected = doc.objects.find((o) => o.uid === selectedUid) ?? null
  const seatingObject = doc.objects.find((o) => o.uid === seatingFor) ?? null
  // z-order: objects render bottom-to-top by their layer's order in the panel.
  const layerOrder = Object.fromEntries(doc.layers.map((l, i) => [l.id, i]))
  const orderedObjects = [...doc.objects].sort((a, b) => (layerOrder[a.layer] ?? 0) - (layerOrder[b.layer] ?? 0))

  const zoomLabel = `${Math.round(view.zoom * 100)}%`

  return (
    <div className="space-y-3">
      {/* Toolbar */}
      <div className="flex flex-wrap items-center gap-1.5 rounded-card border border-line bg-surface p-2 shadow-card">
        <button
          type="button"
          onClick={save}
          disabled={saving || !isDirty}
          title="Save changes"
          className={cn('inline-flex h-9 items-center gap-1.5 rounded-btn px-3 text-sm font-semibold transition-colors disabled:cursor-default',
            isDirty ? 'bg-navy-800 text-white hover:bg-navy-900' : 'bg-canvas text-muted')}
        >
          <Icon name={saving ? 'Loader2' : 'Check'} className={cn('size-4', saving && 'animate-spin')} />
          {saving ? 'Saving…' : isDirty ? 'Save' : 'Saved'}
        </button>
        <Divider />
        <ToolButton icon="ArrowLeft" label="Undo" onClick={undo} disabled={!past.length} />
        <ToolButton icon="ArrowRight" label="Redo" onClick={redo} disabled={!future.length} />
        <Divider />
        <ToolButton icon="Search" label="Zoom out" onClick={() => setView((v) => ({ ...v, zoom: Math.max(0.3, v.zoom - 0.1) }))} />
        <span className="w-12 text-center text-xs font-semibold text-muted">{zoomLabel}</span>
        <ToolButton icon="Plus" label="Zoom in" onClick={() => setView((v) => ({ ...v, zoom: Math.min(2.5, v.zoom + 0.1) }))} />
        <Divider />
        <ToolButton icon="LayoutGrid" label="Grid" active={view.gridOn} onClick={() => setView((v) => ({ ...v, gridOn: !v.gridOn }))} />
        <ToolButton icon="Package" label="Snap" active={snapOn} onClick={() => setSnapOn((s) => !s)} />
        <Divider />
        <Dropdown align="left" trigger={() => (
          <span className="inline-flex h-9 items-center gap-1.5 rounded-btn px-2.5 text-sm font-semibold text-ink hover:bg-canvas">
            <Icon name="Download" className="size-4" /> Export
          </span>
        )}>
          {({ close }) => (
            <>
              <DropdownItem onClick={() => { exportPNG(); close() }}><Icon name="Image" className="size-4 text-muted" /> PNG</DropdownItem>
              <DropdownItem onClick={() => { exportPDF(); close() }}><Icon name="FileText" className="size-4 text-muted" /> PDF (print)</DropdownItem>
              <DropdownItem disabled className="opacity-50"><Icon name="FileText" className="size-4 text-muted" /> SVG (soon)</DropdownItem>
            </>
          )}
        </Dropdown>
        <span className={cn('ml-auto flex items-center gap-1.5 pr-1 text-xs font-medium',
          isDirty ? 'text-amber-600' : 'text-muted')}>
          {isDirty && <span className="size-1.5 rounded-full bg-amber-500" />}
          {saving ? 'Saving…' : isDirty ? 'Unsaved changes' : savedAt ? `Saved ${savedAt.toLocaleTimeString()}` : 'All changes saved'}
        </span>
        <span className="inline-flex h-9 cursor-not-allowed items-center gap-1.5 rounded-btn bg-purple-50 px-2.5 text-sm font-semibold text-purple-700 opacity-80" title="Arriving in Phase 4">
          <Icon name="Sparkles" className="size-4" /> AI <span className="text-[0.6rem]">Soon</span>
        </span>
      </div>

      {/* Body: library · canvas · properties */}
      <div className="grid gap-3 xl:grid-cols-[210px_1fr_260px]">
        <div className="rounded-card border border-line bg-surface p-3 shadow-card">
          <ObjectLibrary onAdd={addObject} />
        </div>

        <div className="min-w-0">
          <DesignerCanvas
            ref={canvasRef}
            layout={doc.layoutProps}
            objects={orderedObjects}
            layers={doc.layers}
            view={view}
            snapOn={snapOn}
            selectedUid={selectedUid}
            onSelect={setSelectedUid}
            onChange={changeObject}
            warningsByUid={byUid}
          />
        </div>

        <div className="space-y-3">
          <div className="rounded-card border border-line bg-surface p-3 shadow-card">
            <PropertiesPanel
              layout={doc.layoutProps}
              selected={selected}
              layers={doc.layers}
              onLayoutChange={changeLayout}
              onObjectChange={changeObject}
              onDelete={deleteObject}
              onEditSeating={(o) => setSeatingFor(o.uid)}
            />
          </div>
        </div>
      </div>

      {/* Bottom: layers · stats · validation */}
      <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_1fr]">
        <div className="rounded-card border border-line bg-surface p-3 shadow-card">
          <LayersPanel layers={doc.layers} onChange={changeLayers} />
        </div>
        <div className="rounded-card border border-line bg-surface p-3 shadow-card">
          <StatsPanel objects={doc.objects} layout={doc.layoutProps} seats={seats} warnings={warnings} />
        </div>
      </div>

      <SeatingModal
        open={!!seatingObject}
        object={seatingObject}
        guests={guests}
        needsSave={!!seatingObject && !seatingObject.id}
        onRequestSave={save}
        onSave={saveSeating}
        saving={seatingSaving || saving}
        onClose={() => setSeatingFor(null)}
      />
    </div>
  )
}

function ToolButton({ icon, label, onClick, active, disabled, loading }) {
  return (
    <button type="button" onClick={onClick} disabled={disabled || loading} title={label}
      className={cn('grid size-9 place-items-center rounded-btn text-muted transition-colors hover:bg-canvas hover:text-ink disabled:opacity-40',
        active && 'bg-navy-50 text-navy-700')}>
      <Icon name={loading ? 'Loader2' : icon} className={cn('size-4', loading && 'animate-spin')} />
    </button>
  )
}

function Divider() {
  return <span className="mx-0.5 h-6 w-px bg-line" />
}
