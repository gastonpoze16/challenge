import { defineStore } from 'pinia'
import { eventTypesApi } from '~/api/eventTypes'

export type PaymentEventTypeDto = {
  code: string
  label: string
  sort_order: number
  is_refunded: boolean
}

export const useEventTypesStore = defineStore('eventTypes', () => {
  const types = ref<PaymentEventTypeDto[]>([])
  const loaded = ref(false)

  async function fetch () {
    const res = await eventTypesApi.list()
    types.value = res.data
    loaded.value = true
  }

  const filterSelectOptions = computed(() => [
    { label: 'Any', value: '' },
    ...types.value.map(t => ({ label: t.label, value: t.code }))
  ])

  function toStatusLabel (eventValue?: string | null): string {
    if (!eventValue) return '-'
    const row = types.value.find(t => t.code === eventValue)
    return row?.label ?? eventValue
  }

  function isRefundedStatus (eventValue?: string | null): boolean {
    if (!eventValue) return false
    return types.value.some(t => t.code === eventValue && t.is_refunded)
  }

  return {
    types,
    loaded,
    fetch,
    filterSelectOptions,
    toStatusLabel,
    isRefundedStatus
  }
})
