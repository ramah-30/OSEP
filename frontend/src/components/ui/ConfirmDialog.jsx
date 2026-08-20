import Modal from './Modal'
import Button from './Button'

/**
 * A small confirm/cancel dialog built on Modal, used before destructive actions
 * so the workspace never relies on a native browser confirm.
 */
export default function ConfirmDialog({
  open,
  onClose,
  onConfirm,
  title = 'Are you sure?',
  description,
  confirmLabel = 'Confirm',
  tone = 'danger',
  loading = false,
}) {
  return (
    <Modal
      open={open}
      onClose={onClose}
      title={title}
      description={description}
      footer={
        <>
          <Button variant="ghost" size="sm" onClick={onClose} disabled={loading}>
            Cancel
          </Button>
          <Button variant={tone} size="sm" onClick={onConfirm} loading={loading}>
            {confirmLabel}
          </Button>
        </>
      }
    >
      <p className="text-sm text-muted">This action can’t be undone.</p>
    </Modal>
  )
}
