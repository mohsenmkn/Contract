<!-- src/components/common/OtpInput.vue -->
<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  length: { type: Number, default: 6 },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
  ariaLabel: { type: String, default: 'کد تایید' }
})

const emit = defineEmits(['update:modelValue', 'complete', 'submit'])

const inputs = ref([])
const digits = ref(Array(props.length).fill(''))

// تبدیل ارقام فارسی/عربی به لاتین و حذف هر کاراکتر غیر عددی
const toLatin = (value) =>
    String(value ?? '')
        .replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 0x06f0))
        .replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660))
        .replace(/\D/g, '')

// همگام‌سازی با v-model (مثلاً پاک شدن کد از بیرون)
watch(
    () => props.modelValue,
    (value) => {
      const clean = toLatin(value).slice(0, props.length)
      if (clean !== digits.value.join('')) {
        digits.value = Array.from({ length: props.length }, (_, i) => clean[i] ?? '')
      }
    },
    { immediate: true }
)

const commit = () => {
  const value = digits.value.join('')
  emit('update:modelValue', value)
  if (value.length === props.length) emit('complete', value)
}

const focusAt = (index) => {
  const target = inputs.value[Math.max(0, Math.min(index, props.length - 1))]
  target?.focus()
  target?.select?.()
}

// نوشتن رقم‌ها از یک خانه به بعد (برای تایپ، paste و autofill پیامک)
const fillFrom = (start, chars) => {
  let i = start
  for (const char of chars) {
    if (i >= props.length) break
    digits.value[i] = char
    i++
  }
  commit()
  focusAt(i)
}

const onBeforeInput = (e) => {
  if (e.inputType?.startsWith('insert') && e.data && !/^[\d۰-۹٠-٩]+$/.test(e.data)) {
    e.preventDefault()
  }
}

const onInput = (e, index) => {
  const previous = digits.value[index]
  let raw = toLatin(e.target.value)

  if (!raw) {
    digits.value[index] = ''
    e.target.value = ''
    commit()
    return
  }

  // اگر خانه پر بوده و کاربر رقم جدید زده، رقم قدیمی را حذف کن
  if (raw.length > 1 && previous) raw = raw.replace(previous, '')

  e.target.value = digits.value[index]
  fillFrom(index, raw)
}

const onKeydown = (e, index) => {
  switch (e.key) {
    case 'Backspace':
      e.preventDefault()
      if (digits.value[index]) {
        digits.value[index] = ''
        commit()
      } else if (index > 0) {
        digits.value[index - 1] = ''
        commit()
        focusAt(index - 1)
      }
      break
    case 'Delete':
      e.preventDefault()
      digits.value[index] = ''
      commit()
      break
    case 'ArrowLeft':
      e.preventDefault()
      focusAt(index - 1)
      break
    case 'ArrowRight':
      e.preventDefault()
      focusAt(index + 1)
      break
    case 'Home':
      e.preventDefault()
      focusAt(0)
      break
    case 'End':
      e.preventDefault()
      focusAt(props.length - 1)
      break
    case 'Enter':
      if (digits.value.join('').length === props.length) emit('submit', digits.value.join(''))
      break
  }
}

const onPaste = (e, index) => {
  e.preventDefault()
  const text = toLatin(e.clipboardData?.getData('text'))
  if (!text) return
  fillFrom(text.length >= props.length ? 0 : index, text.slice(0, props.length))
}

const focus = () => {
  const firstEmpty = digits.value.findIndex((d) => !d)
  focusAt(firstEmpty === -1 ? props.length - 1 : firstEmpty)
}

defineExpose({ focus })
</script>

<template>
  <!-- dir="ltr" مهم است: در صفحه RTL ترتیب خانه‌ها را برعکس می‌کرد -->
  <div class="otp" dir="ltr" role="group" :aria-label="ariaLabel">
    <input
        v-for="(digit, i) in digits"
        :key="i"
        :ref="(el) => (inputs[i] = el)"
        :value="digit"
        :disabled="disabled"
        :autocomplete="i === 0 ? 'one-time-code' : 'off'"
        :aria-label="`${ariaLabel} - رقم ${i + 1}`"
        :aria-invalid="invalid"
        :class="['otp__cell', { 'otp__cell--filled': digit, 'otp__cell--invalid': invalid }]"
        type="text"
        inputmode="numeric"
        pattern="[0-9]*"
        enterkeyhint="done"
        spellcheck="false"
        @beforeinput="onBeforeInput"
        @input="onInput($event, i)"
        @keydown="onKeydown($event, i)"
        @paste="onPaste($event, i)"
        @focus="$event.target.select()"
        @click="$event.target.select()"
    />
  </div>
</template>

<style scoped>
.otp {
  display: flex;
  justify-content: center;
  gap: clamp(0.35rem, 2vw, 0.6rem);
  direction: ltr;
}

.otp__cell {
  box-sizing: border-box;
  width: clamp(2.5rem, 11vw, 3.1rem);
  height: clamp(3.1rem, 13vw, 3.6rem);
  padding: 0;
  text-align: center;
  direction: ltr;
  font-size: 1.45rem;
  font-weight: 700;
  font-variant-numeric: lining-nums tabular-nums;
  color: var(--p-inputtext-color, inherit);
  background: var(--p-inputtext-background, #fff);
  border: 1px solid var(--p-inputtext-border-color, #cbd5e1);
  border-radius: 0.75rem;
  outline: none;
  caret-color: transparent;
  transition: border-color 160ms ease, box-shadow 160ms ease;
}

.otp__cell--filled {
  border-color: var(--p-primary-color, #2563eb);
}

.otp__cell:focus {
  border-color: var(--p-primary-color, #2563eb);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--p-primary-color, #2563eb) 20%, transparent);
}

.otp__cell--invalid {
  border-color: var(--p-red-500, #ef4444);
}

.otp__cell--invalid:focus {
  border-color: var(--p-red-500, #ef4444);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--p-red-500, #ef4444) 20%, transparent);
}

.otp__cell:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>