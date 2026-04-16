import { defineComponent, h } from 'vue'

function stub (name) {
  return defineComponent({
    name,
    props: {
      intent: String,
      variant: String,
      size: String,
      type: String,
      loading: Boolean,
      disabled: Boolean,
      class: [String, Array, Object]
    },
    setup (props, { slots, attrs }) {
      return () =>
        h('div', { ...attrs, 'data-ci-stub': name, class: props.class }, slots.default?.())
    }
  })
}

export const SpButton = stub('SpButton')
export const SpBadge = stub('SpBadge')
export const SpLabel = stub('SpLabel')
export const SpInput = stub('SpInput')
export const SpSelect = stub('SpSelect')
export const SpDatePicker = stub('SpDatePicker')
