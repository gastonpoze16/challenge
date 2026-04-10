function parseYmd (s: string): Date | null {
  if (!s || !/^\d{4}-\d{2}-\d{2}$/.test(s)) return null
  const d = new Date(`${s}T12:00:00`)
  return Number.isNaN(d.getTime()) ? null : d
}

function formatYmd (d: Date | null): string {
  if (!d) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function normalizeCurrencyQuery (raw: string | undefined | null): string | null {
  if (raw === undefined || raw === null) return null
  const s = String(raw).trim().toUpperCase()
  if (!/^[A-Z]{3}$/.test(s)) return null
  return s
}

export function usePaymentFilters () {
  const route = useRoute()
  const router = useRouter()

  const limit = computed(() => {
    const raw = Number(route.query.limit ?? 15)
    if (!Number.isFinite(raw)) return 15
    return Math.min(100, Math.max(1, Math.trunc(raw)))
  })

  const page = computed(() => {
    const raw = Number(route.query.page ?? 1)
    if (!Number.isFinite(raw)) return 1
    return Math.max(1, Math.trunc(raw))
  })

  const currencyFilterWarning = ref('')
  const dateFromModel = ref<Date | null>(null)
  const dateToModel = ref<Date | null>(null)

  const filterForm = reactive({
    event: '',
    currency: ''
  })

  watch(
    () => route.query,
    (q) => {
      filterForm.event = (q.event as string) || (q.status as string) || ''
      filterForm.currency = (q.currency as string) || ''
      dateFromModel.value = parseYmd((q.date_from as string) || '')
      dateToModel.value = parseYmd((q.date_to as string) || '')
    },
    { immediate: true }
  )

  const filterKeys = ['event', 'status', 'date_from', 'date_to', 'currency'] as const

  const queryString = computed(() => {
    const params = new URLSearchParams()
    params.set('limit', String(limit.value))
    params.set('page', String(page.value))
    for (const key of filterKeys) {
      const raw = route.query[key]
      if (raw === undefined || raw === null) continue
      const s = Array.isArray(raw) ? raw[0] : raw
      if (s === '' || s === undefined) continue
      if (key === 'currency') {
        const c = normalizeCurrencyQuery(s)
        if (c) params.set('currency', c)
        continue
      }
      params.set(key, String(s))
    }
    return params.toString()
  })

  const applyFilters = async () => {
    currencyFilterWarning.value = ''
    const q: Record<string, string> = {
      page: '1',
      limit: String(limit.value)
    }
    if (filterForm.event) q.event = filterForm.event
    const df = formatYmd(dateFromModel.value)
    const dt = formatYmd(dateToModel.value)
    if (df) q.date_from = df
    if (dt) q.date_to = dt

    const cur = filterForm.currency.trim()
    if (cur.length > 0) {
      if (!/^[A-Za-z]{3}$/.test(cur)) {
        currencyFilterWarning.value =
          'Currency must be a 3-letter ISO code (e.g. USD). Fix it or leave the field empty.'
        return
      }
      q.currency = cur.toUpperCase()
    }

    await router.replace({ query: q })
  }

  const clearFilters = async () => {
    currencyFilterWarning.value = ''
    filterForm.event = ''
    filterForm.currency = ''
    dateFromModel.value = null
    dateToModel.value = null
    await router.replace({ query: { page: '1', limit: String(limit.value) } })
  }

  const goToPage = async (targetPage: number) => {
    await router.replace({
      query: {
        ...route.query,
        page: String(Math.max(1, targetPage)),
        limit: String(limit.value)
      }
    })
  }

  const onPaginatorPage = (e: { first: number; rows: number }) => {
    void goToPage(Math.floor(e.first / e.rows) + 1)
  }

  return {
    limit,
    page,
    filterForm,
    dateFromModel,
    dateToModel,
    currencyFilterWarning,
    queryString,
    applyFilters,
    clearFilters,
    goToPage,
    onPaginatorPage
  }
}
