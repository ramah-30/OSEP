import { useEffect, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import {
  DndContext,
  DragOverlay,
  PointerSensor,
  closestCorners,
  useSensor,
  useSensors,
} from '@dnd-kit/core'
import { SortableContext, arrayMove, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable'
import { useDroppable } from '@dnd-kit/core'
import { CSS } from '@dnd-kit/utilities'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Drawer from '../../../../components/ui/Drawer'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import { Field, SelectField } from '../../../../components/ui/Field'
import Textarea from '../../../../components/ui/Textarea'
import { api } from '../../../../lib/api'
import { formatDate } from '../../../../lib/format'
import { PRIORITY_OPTIONS, PRIORITY_TONE, TASK_COLUMNS } from '../../../../lib/eventConstants'

/** Build { status: [taskId,…] } and a lookup, ordered by position. */
function buildBoard(tasks) {
  const board = Object.fromEntries(TASK_COLUMNS.map((c) => [c.value, []]))
  const byId = {}
  const ordered = [...tasks].sort((a, b) => a.position - b.position)
  for (const t of ordered) {
    const id = String(t.id)
    byId[id] = t
    ;(board[t.status] ??= []).push(id)
  }
  return { board, byId }
}

export default function Tasks() {
  const { event, reload } = useOutletContext()
  const [{ board, byId }, setState] = useState(() => buildBoard(event.tasks ?? []))
  const [activeId, setActiveId] = useState(null)
  const [drawer, setDrawer] = useState({ open: false, editing: null, status: 'not_started' })
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)

  // Re-sync whenever the event reloads.
  useEffect(() => { setState(buildBoard(event.tasks ?? [])) }, [event.tasks])

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 6 } }))

  function findContainer(id) {
    if (board[id]) return id
    return Object.keys(board).find((k) => board[k].includes(id))
  }

  async function persist(status, orderedIds, taskId) {
    try {
      await api.put(`/events/${event.id}/tasks/reorder`, {
        task_id: Number(taskId),
        status,
        ordered_ids: orderedIds.map(Number),
      })
    } finally {
      reload()
    }
  }

  function handleDragEnd({ active, over }) {
    setActiveId(null)
    if (!over) return

    const from = findContainer(active.id)
    const to = findContainer(over.id)
    if (!from || !to) return

    if (from === to) {
      const oldIndex = board[from].indexOf(active.id)
      const overIndex = board[to].indexOf(over.id)
      if (oldIndex === overIndex || overIndex === -1) return
      const next = arrayMove(board[from], oldIndex, overIndex)
      setState((s) => ({ ...s, board: { ...s.board, [from]: next } }))
      persist(from, next, active.id)
      return
    }

    // Move across columns.
    const source = board[from].filter((id) => id !== active.id)
    const insertAt = board[to].includes(over.id) ? board[to].indexOf(over.id) : board[to].length
    const dest = [...board[to]]
    dest.splice(insertAt, 0, active.id)
    const nextBoard = { ...board, [from]: source, [to]: dest }
    setState((s) => ({ ...s, board: nextBoard }))
    persist(to, dest, active.id)
  }

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/events/${event.id}/tasks/${removing.id}`)
      setRemoving(null)
      reload()
    } finally {
      setBusy(false)
    }
  }

  const activeTask = activeId ? byId[activeId] : null

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Tasks</h2>
          <p className="text-sm text-muted">Drag cards between columns to update their status.</p>
        </div>
        <Button size="sm" onClick={() => setDrawer({ open: true, editing: null, status: 'not_started' })}>
          <Icon name="Plus" className="size-4" /> Add task
        </Button>
      </div>

      <DndContext
        sensors={sensors}
        collisionDetection={closestCorners}
        onDragStart={({ active }) => setActiveId(active.id)}
        onDragEnd={handleDragEnd}
        onDragCancel={() => setActiveId(null)}
      >
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {TASK_COLUMNS.map((col) => (
            <Column
              key={col.value}
              column={col}
              ids={board[col.value]}
              byId={byId}
              onAdd={() => setDrawer({ open: true, editing: null, status: col.value })}
              onEdit={(t) => setDrawer({ open: true, editing: t, status: t.status })}
              onDelete={(t) => setRemoving(t)}
            />
          ))}
        </div>

        <DragOverlay>
          {activeTask ? <TaskCard task={activeTask} overlay /> : null}
        </DragOverlay>
      </DndContext>

      <TaskDrawer
        key={drawer.editing?.id ?? `new-${drawer.status}`}
        open={drawer.open}
        editing={drawer.editing}
        status={drawer.status}
        eventId={event.id}
        onClose={() => setDrawer({ open: false, editing: null, status: 'not_started' })}
        onSaved={() => { setDrawer({ open: false, editing: null, status: 'not_started' }); reload() }}
      />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Delete task?" confirmLabel="Delete" loading={busy} />
    </div>
  )
}

function Column({ column, ids, byId, onAdd, onEdit, onDelete }) {
  const { setNodeRef, isOver } = useDroppable({ id: column.value })
  return (
    <div className="flex flex-col rounded-card border border-line/80 bg-canvas/40 p-3">
      <div className="mb-3 flex items-center justify-between px-1">
        <span className="text-sm font-bold text-ink">{column.label}</span>
        <span className="rounded-full bg-line/70 px-2 py-0.5 text-xs font-semibold text-muted">{ids.length}</span>
      </div>
      <SortableContext items={ids} strategy={verticalListSortingStrategy}>
        <div ref={setNodeRef} className={`flex min-h-24 flex-1 flex-col gap-2 rounded-btn p-1 transition-colors ${isOver ? 'bg-navy-50/60' : ''}`}>
          {ids.map((id) => (
            <SortableTask key={id} id={id} task={byId[id]} onEdit={onEdit} onDelete={onDelete} />
          ))}
          {ids.length === 0 && <p className="px-2 py-6 text-center text-xs text-muted">Drop tasks here</p>}
        </div>
      </SortableContext>
      <button type="button" onClick={onAdd} className="mt-2 flex items-center gap-1.5 rounded-btn px-2 py-1.5 text-xs font-semibold text-muted hover:bg-surface hover:text-ink">
        <Icon name="Plus" className="size-3.5" /> Add task
      </button>
    </div>
  )
}

function SortableTask({ id, task, onEdit, onDelete }) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id })
  const style = { transform: CSS.Transform.toString(transform), transition, opacity: isDragging ? 0.4 : 1 }
  return (
    <div ref={setNodeRef} style={style} {...attributes} {...listeners}>
      <TaskCard task={task} onEdit={onEdit} onDelete={onDelete} />
    </div>
  )
}

function TaskCard({ task, onEdit, onDelete, overlay = false }) {
  return (
    <div className={`rounded-btn border border-line bg-surface p-3 shadow-card ${overlay ? 'rotate-2 shadow-lift' : 'cursor-grab active:cursor-grabbing'}`}>
      <div className="flex items-start justify-between gap-2">
        <p className="text-sm font-semibold text-ink">{task.title}</p>
        {onEdit && (
          <div className="flex shrink-0 items-center gap-0.5">
            <button type="button" onPointerDown={(e) => e.stopPropagation()} onClick={() => onEdit(task)}
              className="grid size-6 place-items-center rounded text-muted hover:bg-canvas hover:text-ink">
              <Icon name="PenLine" className="size-3.5" />
            </button>
            <button type="button" onPointerDown={(e) => e.stopPropagation()} onClick={() => onDelete(task)}
              className="grid size-6 place-items-center rounded text-muted hover:bg-danger-soft hover:text-danger">
              <Icon name="Trash2" className="size-3.5" />
            </button>
          </div>
        )}
      </div>
      <div className="mt-2 flex flex-wrap items-center gap-2">
        <Badge tone={PRIORITY_TONE[task.priority] ?? 'muted'}>{task.priority_label}</Badge>
        {task.due_date && <span className="flex items-center gap-1 text-xs text-muted"><Icon name="Calendar" className="size-3" />{formatDate(task.due_date)}</span>}
        {task.assignee && <span className="flex items-center gap-1 text-xs text-muted"><Icon name="User" className="size-3" />{task.assignee.full_name}</span>}
        {task.comments_count > 0 && <span className="flex items-center gap-1 text-xs text-muted"><Icon name="MessageSquare" className="size-3" />{task.comments_count}</span>}
      </div>
    </div>
  )
}

function TaskDrawer({ open, editing, status, eventId, onClose, onSaved }) {
  const { register, handleSubmit, formState: { errors, isSubmitting } } = useForm({
    defaultValues: editing
      ? { title: editing.title, description: editing.description ?? '', priority: editing.priority, status: editing.status, due_date: editing.due_date ?? '' }
      : { priority: 'medium', status },
  })

  const submit = handleSubmit(async (values) => {
    const payload = Object.fromEntries(Object.entries(values).filter(([, v]) => v !== ''))
    if (editing) {
      await api.put(`/events/${eventId}/tasks/${editing.id}`, payload)
    } else {
      await api.post(`/events/${eventId}/tasks`, payload)
    }
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title={editing ? 'Edit task' : 'Add task'}>
      <form onSubmit={submit} className="space-y-4">
        <Field label="Title" error={errors.title?.message} {...register('title', { required: 'A title is required' })} />
        <Textarea label="Description" rows={3} {...register('description')} />
        <div className="grid grid-cols-2 gap-4">
          <SelectField label="Priority" options={PRIORITY_OPTIONS} {...register('priority')} />
          <SelectField label="Status" options={TASK_COLUMNS} {...register('status')} />
        </div>
        <Field type="date" label="Due date" {...register('due_date')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{editing ? 'Save' : 'Add task'}</Button>
        </div>
      </form>
    </Drawer>
  )
}
