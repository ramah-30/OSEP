import Icon from '../ui/Icon'
import { cn } from '../../lib/cn'

/**
 * Layer manager: toggle visibility/lock, rename, and reorder (which sets the
 * canvas z-order). Layers higher in the list render on top.
 */
export default function LayersPanel({ layers, onChange }) {
  function patch(id, changes) {
    onChange(layers.map((l) => (l.id === id ? { ...l, ...changes } : l)))
  }

  function move(index, dir) {
    const next = [...layers]
    const target = index + dir
    if (target < 0 || target >= next.length) return
    ;[next[index], next[target]] = [next[target], next[index]]
    onChange(next)
  }

  return (
    <div>
      <p className="mb-2 text-xs font-bold uppercase tracking-wide text-muted">Layers</p>
      <ul className="space-y-1">
        {layers.map((layer, i) => (
          <li key={layer.id} className="flex items-center gap-1 rounded-btn border border-line/70 px-2 py-1.5">
            <button type="button" onClick={() => patch(layer.id, { hidden: !layer.hidden })}
              title={layer.hidden ? 'Show' : 'Hide'}
              className={cn('grid size-7 place-items-center rounded text-muted hover:bg-canvas', layer.hidden && 'text-navy-700')}>
              <Icon name={layer.hidden ? 'EyeOff' : 'Eye'} className="size-4" />
            </button>
            <button type="button" onClick={() => patch(layer.id, { locked: !layer.locked })}
              title={layer.locked ? 'Unlock' : 'Lock'}
              className={cn('grid size-7 place-items-center rounded text-muted hover:bg-canvas', layer.locked && 'text-warning')}>
              <Icon name={layer.locked ? 'Lock' : 'Lock'} className={cn('size-4', !layer.locked && 'opacity-40')} />
            </button>
            <input
              value={layer.name}
              onChange={(e) => patch(layer.id, { name: e.target.value })}
              className="min-w-0 flex-1 bg-transparent px-1 text-sm text-ink outline-none focus:rounded focus:bg-canvas"
            />
            <div className="flex flex-col">
              <button type="button" onClick={() => move(i, -1)} className="grid h-3.5 w-5 place-items-center text-muted hover:text-ink"><Icon name="ChevronDown" className="size-3 rotate-180" /></button>
              <button type="button" onClick={() => move(i, 1)} className="grid h-3.5 w-5 place-items-center text-muted hover:text-ink"><Icon name="ChevronDown" className="size-3" /></button>
            </div>
          </li>
        ))}
      </ul>
    </div>
  )
}
