/**
 * Locale fijo: `undefined` en toLocaleString hace que SSR (Node) y el cliente
 * usen configuraciones distintas y Vue marque hydration mismatch.
 */
const PAYMENT_DATETIME_LOCALE = 'en-US'

export function formatPaymentDateTime (iso: string): string {
  try {
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) {
      return iso
    }
    return new Intl.DateTimeFormat(PAYMENT_DATETIME_LOCALE, {
      dateStyle: 'short',
      timeStyle: 'short',
      hour12: true
    }).format(d)
  } catch {
    return iso
  }
}
