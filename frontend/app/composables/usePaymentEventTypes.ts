export async function usePaymentEventTypes () {
  const store = useEventTypesStore()

  if (!store.loaded) {
    await store.fetch()
  }

  return {
    filterSelectOptions: store.filterSelectOptions,
    toStatusLabel: store.toStatusLabel,
    isRefundedStatus: store.isRefundedStatus
  }
}
