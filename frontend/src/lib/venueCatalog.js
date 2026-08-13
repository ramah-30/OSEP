/**
 * The Venue Designer object library. Sizes are in metres (the canvas base unit);
 * the editor scales them to pixels. `shape` drives how the canvas draws an item;
 * `seats` marks table-like objects that support seating assignments.
 */

export const DEFAULT_LAYERS = [
  { id: 'furniture', name: 'Furniture', hidden: false, locked: false },
  { id: 'stage', name: 'Stage', hidden: false, locked: false },
  { id: 'decoration', name: 'Decoration', hidden: false, locked: false },
  { id: 'lighting', name: 'Lighting', hidden: false, locked: false },
  { id: 'audio', name: 'Audio', hidden: false, locked: false },
  { id: 'facilities', name: 'Facilities', hidden: false, locked: false },
  { id: 'security', name: 'Security', hidden: false, locked: false },
]

export const OBJECT_CATEGORIES = [
  {
    key: 'tables', label: 'Tables', icon: 'LayoutGrid',
    items: [
      { type: 'round_table', label: 'Round Table', shape: 'circle', w: 2, h: 2, color: '#ffffff', layer: 'furniture', seats: 8 },
      { type: 'rectangle_table', label: 'Rectangle Table', shape: 'rect', w: 2.4, h: 1, color: '#ffffff', layer: 'furniture', seats: 8 },
      { type: 'cocktail_table', label: 'Cocktail Table', shape: 'circle', w: 0.8, h: 0.8, color: '#f1f5f9', layer: 'furniture', seats: 4 },
      { type: 'vip_table', label: 'VIP Table', shape: 'circle', w: 2.2, h: 2.2, color: '#fde68a', layer: 'furniture', seats: 10 },
      { type: 'buffet_table', label: 'Buffet Table', shape: 'rect', w: 3, h: 0.9, color: '#e2e8f0', layer: 'furniture' },
    ],
  },
  {
    key: 'seating', label: 'Seating', icon: 'Users',
    items: [
      { type: 'chair', label: 'Chair', shape: 'rect', w: 0.5, h: 0.5, color: '#cbd5e1', layer: 'furniture' },
      { type: 'sofa', label: 'Sofa', shape: 'rect', w: 2, h: 0.9, color: '#94a3b8', layer: 'furniture' },
      { type: 'vip_chair', label: 'VIP Chair', shape: 'rect', w: 0.6, h: 0.6, color: '#fbbf24', layer: 'furniture' },
      { type: 'bench', label: 'Bench', shape: 'rect', w: 1.8, h: 0.5, color: '#cbd5e1', layer: 'furniture' },
      { type: 'lounge_seat', label: 'Lounge Seat', shape: 'rect', w: 1.2, h: 1.2, color: '#94a3b8', layer: 'furniture' },
    ],
  },
  {
    key: 'stage', label: 'Stage', icon: 'PartyPopper',
    items: [
      { type: 'small_stage', label: 'Small Stage', shape: 'rect', w: 6, h: 3, color: '#7c3aed', layer: 'stage' },
      { type: 'medium_stage', label: 'Medium Stage', shape: 'rect', w: 10, h: 4, color: '#7c3aed', layer: 'stage' },
      { type: 'concert_stage', label: 'Concert Stage', shape: 'rect', w: 16, h: 6, color: '#6d28d9', layer: 'stage' },
      { type: 'runway_stage', label: 'Runway Stage', shape: 'rect', w: 12, h: 2, color: '#8b5cf6', layer: 'stage' },
      { type: 'podium', label: 'Podium', shape: 'rect', w: 1, h: 1, color: '#6d28d9', layer: 'stage' },
    ],
  },
  {
    key: 'dance_floor', label: 'Dance Floor', icon: 'Sparkles',
    items: [
      { type: 'dance_floor_small', label: 'Small', shape: 'rect', w: 5, h: 5, color: '#c4b5fd', layer: 'decoration' },
      { type: 'dance_floor_medium', label: 'Medium', shape: 'rect', w: 8, h: 8, color: '#c4b5fd', layer: 'decoration' },
      { type: 'dance_floor_large', label: 'Large', shape: 'rect', w: 12, h: 12, color: '#c4b5fd', layer: 'decoration' },
    ],
  },
  {
    key: 'decoration', label: 'Decoration', icon: 'Star',
    items: [
      { type: 'flower_arrangement', label: 'Flower Arrangement', shape: 'circle', w: 0.8, h: 0.8, color: '#fbcfe8', layer: 'decoration' },
      { type: 'balloon_stand', label: 'Balloon Stand', shape: 'circle', w: 1, h: 1, color: '#fda4af', layer: 'decoration' },
      { type: 'backdrop', label: 'Backdrop', shape: 'rect', w: 4, h: 0.4, color: '#f9a8d4', layer: 'decoration' },
      { type: 'archway', label: 'Archway', shape: 'rect', w: 3, h: 0.6, color: '#f472b6', layer: 'decoration' },
      { type: 'centerpiece', label: 'Centerpiece', shape: 'circle', w: 0.5, h: 0.5, color: '#fbcfe8', layer: 'decoration' },
      { type: 'red_carpet', label: 'Red Carpet', shape: 'rect', w: 6, h: 1.2, color: '#ef4444', layer: 'decoration' },
    ],
  },
  {
    key: 'lighting', label: 'Lighting', icon: 'Sun',
    items: [
      { type: 'spotlight', label: 'Spotlight', shape: 'circle', w: 0.6, h: 0.6, color: '#fde68a', layer: 'lighting' },
      { type: 'led_screen', label: 'LED Screen', shape: 'rect', w: 4, h: 0.5, color: '#0f172a', layer: 'lighting' },
      { type: 'moving_head', label: 'Moving Head', shape: 'circle', w: 0.5, h: 0.5, color: '#f59e0b', layer: 'lighting' },
      { type: 'chandelier', label: 'Chandelier', shape: 'circle', w: 1.5, h: 1.5, color: '#fcd34d', layer: 'lighting' },
      { type: 'ambient_lighting', label: 'Ambient Lighting', shape: 'rect', w: 2, h: 0.3, color: '#fef08a', layer: 'lighting' },
    ],
  },
  {
    key: 'audio', label: 'Audio', icon: 'MessageSquare',
    items: [
      { type: 'speaker', label: 'Speaker', shape: 'rect', w: 0.8, h: 0.8, color: '#1f2937', layer: 'audio' },
      { type: 'dj_booth', label: 'DJ Booth', shape: 'rect', w: 2.5, h: 1.5, color: '#111827', layer: 'audio' },
      { type: 'microphone_stand', label: 'Microphone Stand', shape: 'circle', w: 0.4, h: 0.4, color: '#374151', layer: 'audio' },
      { type: 'sound_mixer', label: 'Sound Mixer', shape: 'rect', w: 1.2, h: 0.8, color: '#1f2937', layer: 'audio' },
    ],
  },
  {
    key: 'facilities', label: 'Facilities', icon: 'Building2',
    items: [
      { type: 'entrance', label: 'Entrance', shape: 'rect', w: 3, h: 1, color: '#10b981', layer: 'facilities' },
      { type: 'exit', label: 'Exit', shape: 'rect', w: 3, h: 1, color: '#34d399', layer: 'facilities' },
      { type: 'emergency_exit', label: 'Emergency Exit', shape: 'rect', w: 2, h: 1, color: '#ef4444', layer: 'security' },
      { type: 'restrooms', label: 'Restrooms', shape: 'rect', w: 3, h: 3, color: '#93c5fd', layer: 'facilities' },
      { type: 'kitchen', label: 'Kitchen', shape: 'rect', w: 5, h: 4, color: '#fca5a5', layer: 'facilities' },
      { type: 'parking', label: 'Parking', shape: 'rect', w: 6, h: 5, color: '#e5e7eb', layer: 'facilities' },
      { type: 'bar', label: 'Bar', shape: 'rect', w: 4, h: 1.5, color: '#1e3a8a', layer: 'facilities' },
      { type: 'registration_desk', label: 'Registration Desk', shape: 'rect', w: 3, h: 1, color: '#60a5fa', layer: 'facilities' },
      { type: 'waiting_area', label: 'Waiting Area', shape: 'rect', w: 4, h: 3, color: '#bfdbfe', layer: 'facilities' },
    ],
  },
  {
    key: 'misc', label: 'Miscellaneous', icon: 'Package',
    items: [
      { type: 'generator', label: 'Generator', shape: 'rect', w: 2, h: 1.5, color: '#4b5563', layer: 'facilities' },
      { type: 'power_outlet', label: 'Power Outlet', shape: 'circle', w: 0.3, h: 0.3, color: '#6b7280', layer: 'facilities' },
      { type: 'fire_extinguisher', label: 'Fire Extinguisher', shape: 'circle', w: 0.3, h: 0.3, color: '#dc2626', layer: 'security' },
      { type: 'camera_position', label: 'Camera Position', shape: 'circle', w: 0.4, h: 0.4, color: '#111827', layer: 'security' },
      { type: 'security_checkpoint', label: 'Security Checkpoint', shape: 'rect', w: 2, h: 1.5, color: '#f97316', layer: 'security' },
    ],
  },
]

/** Flat lookup of every catalog item by its type. */
export const CATALOG_BY_TYPE = Object.fromEntries(
  OBJECT_CATEGORIES.flatMap((cat) => cat.items.map((item) => [item.type, item])),
)

/** Human label for an object type, falling back to a title-cased type. */
export function labelForType(type) {
  return CATALOG_BY_TYPE[type]?.label ?? type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

export function shapeForType(type) {
  return CATALOG_BY_TYPE[type]?.shape ?? 'rect'
}

export function isTable(type) {
  return CATALOG_BY_TYPE[type]?.seats != null || type.includes('table')
}
