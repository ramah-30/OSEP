/**
 * Layout validation used by the Venue Designer. Returns human warnings, the set
 * of object uids to flag on the canvas, and the total planned seats.
 */
export function validateLayout(objects, layout) {
  const byUid = {}
  const warnings = []
  const W = Number(layout.width) || 0
  const H = Number(layout.height) || 0

  // Objects outside the venue boundary.
  if (W > 0 && H > 0) {
    for (const o of objects) {
      if (o.x < 0 || o.y < 0 || o.x + o.width > W || o.y + o.height > H) byUid[o.uid] = true
    }
  }
  const outside = Object.keys(byUid).length
  if (outside) warnings.push({ type: 'boundary', tone: 'danger', message: `${outside} object${outside > 1 ? 's' : ''} outside the venue boundary` })

  // Overlapping objects on the same layer (axis-aligned check).
  let overlaps = 0
  for (let i = 0; i < objects.length; i += 1) {
    for (let j = i + 1; j < objects.length; j += 1) {
      const a = objects[i]
      const b = objects[j]
      if (a.layer !== b.layer) continue
      if (a.x < b.x + b.width && a.x + a.width > b.x && a.y < b.y + b.height && a.y + a.height > b.y) {
        overlaps += 1
        byUid[a.uid] = true
        byUid[b.uid] = true
      }
    }
  }
  if (overlaps) warnings.push({ type: 'overlap', tone: 'warning', message: `${overlaps} overlapping object${overlaps > 1 ? 's' : ''}` })

  // Planned seats vs. capacity.
  const seats = objects.reduce((sum, o) => sum + (o.properties?.seats ? Number(o.properties.seats) : 0), 0)
  if (layout.max_capacity && seats > layout.max_capacity) {
    warnings.push({ type: 'capacity', tone: 'danger', message: `Planned seats (${seats}) exceed capacity (${layout.max_capacity})` })
  }

  return { warnings, byUid, seats }
}
