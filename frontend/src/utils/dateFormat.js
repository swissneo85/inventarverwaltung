/**
 * Format a date value as dd.mm.yyyy with leading zeros.
 * Accepts ISO date strings (YYYY-MM-DD), Date objects, or datetime strings.
 * Date-only strings are parsed without timezone conversion.
 */
export function formatDate(val) {
  if (!val) return '–'
  const s = String(val)
  // Fast path: ISO date string YYYY-MM-DD → parse directly, no timezone shift
  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/)
  if (m) return `${m[3]}.${m[2]}.${m[1]}`
  const d = new Date(val)
  if (isNaN(d)) return s
  return `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}.${d.getFullYear()}`
}

/**
 * Format a datetime value as dd.mm.yyyy HH:MM with leading zeros.
 * Accepts ISO datetime strings or Date objects.
 */
export function formatDateTime(val) {
  if (!val) return '–'
  const d = new Date(val)
  if (isNaN(d)) return String(val)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${day}.${month}.${d.getFullYear()} ${hours}:${minutes}`
}
