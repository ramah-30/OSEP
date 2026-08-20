import { useEffect, useState } from 'react'
import { api } from './api'

/**
 * Pending planner-booking-request count, used to badge the sidebar so a planner
 * sees at a glance that a client has booked them. Only fetches for planners.
 */
export function usePendingBookings(accountType) {
  const [count, setCount] = useState(0)

  useEffect(() => {
    if (accountType !== 'event_planner') return undefined

    let active = true
    api.get('/planner-booking-requests')
      .then((r) => {
        if (active) {
          setCount((r.data.data.requests ?? []).filter((x) => x.status === 'pending').length)
        }
      })
      .catch(() => {})

    return () => { active = false }
  }, [accountType])

  return count
}
