export function parseYmd (s: string): Date | null {
  if (!s || !/^\d{4}-\d{2}-\d{2}$/.test(s)) return null
  const d = new Date(`${s}T12:00:00`)
  return Number.isNaN(d.getTime()) ? null : d
}

export function formatYmd (d: Date | null): string {
  if (!d) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

export function normalizeCurrencyQuery (raw: string | undefined | null): string | null {
  if (raw === undefined || raw === null) return null
  const s = String(raw).trim().toUpperCase()
  if (!/^[A-Z]{3}$/.test(s)) return null
  return s
}
