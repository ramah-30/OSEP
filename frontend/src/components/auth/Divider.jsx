export default function Divider({ children = 'or' }) {
  return (
    <div className="flex items-center gap-4">
      <span className="h-px flex-1 bg-line" />
      <span className="text-sm font-medium text-muted">{children}</span>
      <span className="h-px flex-1 bg-line" />
    </div>
  )
}
