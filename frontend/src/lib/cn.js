/**
 * Tiny class joiner. Skips falsy values so conditional classes read cleanly
 * without pulling in a dependency.
 */
export function cn(...classes) {
  return classes.filter(Boolean).join(' ')
}
