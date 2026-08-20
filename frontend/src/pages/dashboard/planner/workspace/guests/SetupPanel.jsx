import GuestCategoriesPanel from './GuestCategoriesPanel'
import GuestSettingsPanel from './GuestSettingsPanel'

/**
 * All the guest-list configuration in one place: categories, meal options and
 * invitation templates. Each section keeps its own CRUD and data fetching.
 */
export default function SetupPanel({ eventId }) {
  return (
    <div className="space-y-10">
      <GuestCategoriesPanel />
      <GuestSettingsPanel eventId={eventId} />
    </div>
  )
}
