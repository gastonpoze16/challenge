import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'

const mockQuery = ref<Record<string, string>>({})
const replaceMock = vi.fn()

mockNuxtImport('useRoute', () => () => ({
  query: mockQuery.value,
}))

mockNuxtImport('useRouter', () => () => ({
  replace: replaceMock,
  afterEach: vi.fn(),
  beforeEach: vi.fn(),
  beforeResolve: vi.fn(),
  onError: vi.fn(),
}))

describe('usePaymentFilters', () => {
  beforeEach(() => {
    mockQuery.value = {}
    replaceMock.mockClear()
  })

  it('limit defaults to 15', async () => {
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { limit } = usePaymentFilters()
    expect(limit.value).toBe(15)
  })

  it('limit clamps between 1 and 100', async () => {
    mockQuery.value = { limit: '200' }
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { limit } = usePaymentFilters()
    expect(limit.value).toBe(100)
  })

  it('page defaults to 1', async () => {
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { page } = usePaymentFilters()
    expect(page.value).toBe(1)
  })

  it('page floors to 1 for non-numeric', async () => {
    mockQuery.value = { page: 'abc' }
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { page } = usePaymentFilters()
    expect(page.value).toBe(1)
  })

  it('queryString includes limit and page', async () => {
    mockQuery.value = { limit: '10', page: '2' }
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { queryString } = usePaymentFilters()

    const params = new URLSearchParams(queryString.value)
    expect(params.get('limit')).toBe('10')
    expect(params.get('page')).toBe('2')
  })

  it('queryString normalizes currency to uppercase', async () => {
    mockQuery.value = { currency: 'eur' }
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { queryString } = usePaymentFilters()

    const params = new URLSearchParams(queryString.value)
    expect(params.get('currency')).toBe('EUR')
  })

  it('queryString omits invalid currency', async () => {
    mockQuery.value = { currency: 'xx' }
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { queryString } = usePaymentFilters()

    const params = new URLSearchParams(queryString.value)
    expect(params.has('currency')).toBe(false)
  })

  it('applyFilters sets currency warning for invalid input', async () => {
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { filterForm, currencyFilterWarning, applyFilters } = usePaymentFilters()

    filterForm.currency = 'XX'
    await applyFilters()

    expect(currencyFilterWarning.value).toContain('3-letter ISO code')
    expect(replaceMock).not.toHaveBeenCalled()
  })

  it('applyFilters navigates with valid filters', async () => {
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { filterForm, applyFilters } = usePaymentFilters()

    filterForm.event = 'payment.completed'
    filterForm.currency = 'USD'
    await applyFilters()

    expect(replaceMock).toHaveBeenCalledWith({
      query: expect.objectContaining({
        event: 'payment.completed',
        currency: 'USD',
        page: '1',
      }),
    })
  })

  it('clearFilters resets all fields and navigates', async () => {
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { filterForm, dateFromModel, dateToModel, clearFilters } = usePaymentFilters()

    filterForm.event = 'payment.created'
    filterForm.currency = 'EUR'
    dateFromModel.value = new Date()
    dateToModel.value = new Date()

    await clearFilters()

    expect(filterForm.event).toBe('')
    expect(filterForm.currency).toBe('')
    expect(dateFromModel.value).toBeNull()
    expect(dateToModel.value).toBeNull()
    expect(replaceMock).toHaveBeenCalled()
  })

  it('goToPage navigates to target page', async () => {
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { goToPage } = usePaymentFilters()

    await goToPage(3)

    expect(replaceMock).toHaveBeenCalledWith({
      query: expect.objectContaining({ page: '3' }),
    })
  })

  it('goToPage clamps to minimum 1', async () => {
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { goToPage } = usePaymentFilters()

    await goToPage(-5)

    expect(replaceMock).toHaveBeenCalledWith({
      query: expect.objectContaining({ page: '1' }),
    })
  })

  it('onPaginatorPage calculates correct page from PrimeVue event', async () => {
    const { usePaymentFilters } = await import('~/composables/usePaymentFilters')
    const { onPaginatorPage } = usePaymentFilters()

    onPaginatorPage({ first: 30, rows: 15 })

    expect(replaceMock).toHaveBeenCalledWith({
      query: expect.objectContaining({ page: '3' }),
    })
  })
})
