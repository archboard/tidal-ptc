<template>
  <div class="relative">
    <div class="absolute top-0 bottom-0 left-0 flex items-center justify-center px-4">
      <CashIcon
        class="w-4 h-4"
        :class="{
          'text-gray-500': !hasError,
          'text-red-300': hasError,
        }"
      />
    </div>
    <input
      :id="id"
      type="text"
      class="pl-10 shadow-sm focus:ring-2 block w-full rounded-md transition"
      :class="{
        'focus:ring-primary-500 focus:border-primary-500 border-gray-300': !hasError,
        'pr-10 border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500': hasError,
      }"
      ref="inputRef"
      placeholder="$0.00"
    />
  </div>
</template>

<script>
import { defineComponent, watch } from 'vue'
import useCurrencyInput from 'vue-currency-input'
import { CashIcon } from '@heroicons/vue/24/outline'

export default defineComponent({
  components: {
    CashIcon,
  },
  props: {
    id: String,
    modelValue: {
      type: [String, Number],
    },
    hasError: Boolean,
  },

  setup (props) {
    const currencyOptions = {
      currency: 'USD',
      locale: 'en',
      valueRange: {
        min: 0
      },
      hideCurrencySymbolOnFocus: true,
      hideGroupingSeparatorOnFocus: true,
      hideNegligibleDecimalDigitsOnFocus: true,
      autoDecimalDigits: false,
      exportValueAsInteger: true,
      autoSign: true,
      useGrouping: true,
    }
    const {
      inputRef,
      setValue
    } = useCurrencyInput(currencyOptions)

    watch(() => props.modelValue, value => {
      setValue(value)
    })

    return {
      inputRef,
    }
  }
})
</script>
