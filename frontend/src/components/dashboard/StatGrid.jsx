import StatCard from '../ui/StatCard'

/** Renders the API's stats array as a responsive tile grid. */
export default function StatGrid({ stats = [] }) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      {stats.map(({ key, ...stat }) => (
        <StatCard key={key} {...stat} />
      ))}
    </div>
  )
}
