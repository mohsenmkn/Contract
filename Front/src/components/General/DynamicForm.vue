<template>
  <form @submit.prevent="submitForm" class="dynamic-form">
    <!-- Server Error Alert -->
    <Message
        v-if="serverError"
        severity="error"
        :closable="true"
        @close="serverError = null"
        class="mb-4"
    >
      {{ serverError }}
    </Message>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-8">
      <ProgressSpinner style="width: 50px; height: 50px" />
    </div>

    <!-- Form Fields -->
    <div v-else class="form-grid">
      <div
          v-for="field in fields"
          :key="field.name"
          :class="[
          'field-wrapper',
          field.type === 'textarea' ? 'field-full' : ''
        ]"
      >
        <label :for="field.name" class="field-label">
          {{ field.label }}
          <span v-if="field.required" class="text-red-500 mr-1">*</span>
        </label>

        <!-- Text Input -->
        <InputText
            v-if="field.type === 'text'"
            :id="field.name"
            v-model="formData[field.name]"
            :placeholder="field.placeholder"
            :class="{ 'p-invalid': errors[field.name] }"
            class="w-full"
            @blur="handleBlur(field)"
            @input="handleInput(field)"
        />

        <!-- Number/Amount Input -->
        <InputNumber
            v-else-if="field.type === 'amount' || field.type === 'money' || field.type === 'number'"
            :id="field.name"
            v-model="formData[field.name]"
            :mode="field.type === 'amount' || field.type === 'money' ? 'currency' : 'decimal'"
            :currency="field.type === 'amount' || field.type === 'money' ? 'IRR' : undefined"
            locale="fa-IR"
            :placeholder="field.placeholder"
            :class="{ 'p-invalid': errors[field.name] }"
            :suffix="field.type === 'amount' || field.type === 'money' ? ' ریال' : ''"
            :min="field.min || field.rules?.min || 0"
            :max="field.max || field.rules?.max"
            class="w-full"
            @blur="handleBlur(field)"
        />

        <!-- Textarea -->
        <Textarea
            v-else-if="field.type === 'textarea'"
            :id="field.name"
            v-model="formData[field.name]"
            :placeholder="field.placeholder"
            :class="{ 'p-invalid': errors[field.name] }"
            :rows="field.rows || 4"
            class="w-full"
            @blur="handleBlur(field)"
            @input="handleInput(field)"
        />

        <!-- Dropdown/Select -->
        <Select
            v-else-if="field.type === 'select'"
            :id="field.name"
            v-model="formData[field.name]"
            :options="field.options"
            :optionLabel="field.optionLabel || 'label'"
            :optionValue="field.optionValue || 'value'"
            :placeholder="field.placeholder || 'انتخاب کنید'"
            :class="{ 'p-invalid': errors[field.name] }"
            class="w-full"
            :showClear="!field.required"
            @change="handleBlur(field)"
        />

        <!-- Date Picker -->
        <DatePicker
            v-else-if="field.type === 'date'"
            v-model="formData[field.name]"
            format="YYYY-MM-DD"
            display-format="jYYYY/jMM/jDD"
            :placeholder="field.placeholder || 'انتخاب تاریخ'"
            :input-class="errors[field.name] ? 'p-invalid-date' : ''"
            locale="fa"
            :auto-submit="true"
            class="w-full"
            @change="handleBlur(field)"
        />

        <!-- Checkbox -->
        <div v-else-if="field.type === 'checkbox'" class="flex items-center gap-2 mt-2">
          <Checkbox
              :id="field.name"
              v-model="formData[field.name]"
              :binary="true"
              @change="handleBlur(field)"
          />
          <label :for="field.name" class="cursor-pointer">
            {{ field.checkboxLabel || field.label }}
          </label>
        </div>

        <!-- Radio Group -->
        <div v-else-if="field.type === 'radio'" class="flex flex-col gap-2 mt-2">
          <div
              v-for="option in field.options || []"
              :key="option.value"
              class="flex items-center gap-2"
          >
            <RadioButton
                :id="`${field.name}_${option.value}`"
                :name="field.name"
                :value="option.value"
                v-model="formData[field.name]"
                @change="handleBlur(field)"
            />
            <label :for="`${field.name}_${option.value}`" class="cursor-pointer">
              {{ option.label }}
            </label>
          </div>
        </div>

        <!-- Error Message -->
        <small v-if="errors[field.name]" class="error-message">
          <i class="pi pi-exclamation-circle ml-1"></i>
          {{ Array.isArray(errors[field.name]) ? errors[field.name][0] : errors[field.name] }}
        </small>

        <!-- Help Text -->
        <small v-else-if="field.help" class="help-text">
          <i class="pi pi-info-circle ml-1"></i>
          {{ field.help }}
        </small>
      </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
      <Button
          label="انصراف"
          icon="pi pi-times"
          severity="secondary"
          text
          @click="$emit('cancel')"
          :disabled="submitting"
      />
      <Button
          :label="submitButtonText"
          icon="pi pi-check"
          type="submit"
          :loading="submitting"
          :disabled="loading"
      />
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Checkbox from 'primevue/checkbox'
import RadioButton from 'primevue/radiobutton'
import DatePicker from 'vue3-persian-datetime-picker'
import api from '@/api/axios.js'

const BASE = import.meta.env.VITE_API_BASE_URL || ''

const props = defineProps({
  schemaUrl: String,
  module: {
    type: String,
    default: null
  },
  submitUrl: String,
  modelId: {
    type: [String, Number, null],
    default: null
  },
  method: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['success', 'error', 'cancel'])

const fields = ref([])
const formData = reactive({})
const errors = reactive({})
const touchedFields = reactive({})
const loading = ref(false)
const submitting = ref(false)
const serverError = ref(null)

const isEditMode = computed(() => props.modelId !== null && props.modelId !== undefined)

const resolvedSchemaUrl = computed(() => {
  if (props.schemaUrl) return props.schemaUrl
  return `${BASE}/${props.module}/schema`
})

const resolvedSubmitUrl = computed(() => {
  if (props.submitUrl) return props.submitUrl
  return `${BASE}/${props.module}`
})

const submitButtonText = computed(() => {
  return isEditMode.value ? 'ذخیره تغییرات' : 'ایجاد'
})

const finalSubmitUrl = computed(() => {
  const base = resolvedSubmitUrl.value
  if (isEditMode.value) {
    return `${base}/${props.modelId}`
  }
  return base
})

const finalMethod = computed(() => {
  if (props.method) return props.method
  return isEditMode.value ? 'PUT' : 'POST'
})

// Validation Functions
function validateField(field) {
  const value = formData[field.name]
  delete errors[field.name]

  // Required validation
  if (field.required) {
    const empty = value === null || value === undefined || value === '' || value === false
    if (empty) {
      errors[field.name] = [`${field.label} الزامی است`]
      return false
    }
  }

  // Pattern validation
  if (field.pattern && value) {
    const regex = new RegExp(field.pattern)
    if (!regex.test(value)) {
      errors[field.name] = [field.patternMessage || `فرمت ${field.label} صحیح نیست`]
      return false
    }
  }

  // Min/Max length validation
  if (field.minLength && value && value.length < field.minLength) {
    errors[field.name] = [`${field.label} باید حداقل ${field.minLength} کاراکتر باشد`]
    return false
  }

  if (field.maxLength && value && value.length > field.maxLength) {
    errors[field.name] = [`${field.label} نمی‌تواند بیشتر از ${field.maxLength} کاراکتر باشد`]
    return false
  }

  // Number min/max validation
  const numericMin = field.min !== undefined ? field.min : field.rules?.min
  const numericMax = field.max !== undefined ? field.max : field.rules?.max

  if (numericMin !== undefined && value < numericMin) {
    errors[field.name] = [`${field.label} باید حداقل ${numericMin} باشد`]
    return false
  }

  if (numericMax !== undefined && value > numericMax) {
    errors[field.name] = [`${field.label} نمی‌تواند بیشتر از ${numericMax} باشد`]
    return false
  }

  // Email validation
  if (field.type === 'email' && value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(value)) {
      errors[field.name] = ['ایمیل معتبر وارد کنید']
      return false
    }
  }

  return true
}

function handleBlur(field) {
  touchedFields[field.name] = true
  validateField(field)
}

function handleInput(field) {
  if (touchedFields[field.name]) {
    validateField(field)
  }
}

function validateForm() {
  let isValid = true
  Object.keys(errors).forEach(key => delete errors[key])

  fields.value.forEach(field => {
    touchedFields[field.name] = true
    if (!validateField(field)) {
      isValid = false
    }
  })

  return isValid
}

// State Management Functions
function resetFormState() {
  fields.value = []
  serverError.value = null

  Object.keys(formData).forEach(key => delete formData[key])
  Object.keys(errors).forEach(key => delete errors[key])
  Object.keys(touchedFields).forEach(key => delete touchedFields[key])
}

function setDefaultValues() {
  fields.value.forEach(field => {
    if (field.default !== undefined && field.default !== null) {
      formData[field.name] = field.default
    } else if (field.type === 'checkbox') {
      formData[field.name] = false
    } else {
      formData[field.name] = ''
    }
  })
}

function fillFormData(data) {
  fields.value.forEach(field => {
    if (data[field.name] !== undefined && data[field.name] !== null) {
      formData[field.name] = data[field.name]
    }
  })
}

// Load Schema
async function loadSchema() {
  loading.value = true
  serverError.value = null

  try {
    console.log('Loading schema from:', resolvedSchemaUrl.value)
    const response = await api.get(resolvedSchemaUrl.value)

    if (response.status !== 200) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    fields.value = response.data
    setDefaultValues()

  } catch (error) {
    console.error('Schema load error:', error)
    serverError.value = 'خطا در دریافت ساختار فرم'
  } finally {
    loading.value = false
  }
}

async function loadEditData() {
  if (!isEditMode.value) return

  try {
    const response = await api.get(finalSubmitUrl.value)
    const result = response.data
    fillFormData(result.data || result)
  } catch (error) {
    console.error('Error loading edit data:', error)
    serverError.value = 'خطا در دریافت اطلاعات'
  }
}

async function initForm() {
  try {
    loading.value = true
    serverError.value = null
    resetFormState()

    await loadSchema()

    if (isEditMode.value) {
      await loadEditData()
    }
  } catch (error) {
    serverError.value = error.message
  } finally {
    loading.value = false
  }
}

function normalizePayload() {
  const payload = {}
  fields.value.forEach(field => {
    payload[field.name] = formData[field.name]
  })
  return payload
}

// Submit Form
async function submitForm() {
  if (!validateForm()) {
    serverError.value = 'لطفاً خطاهای فرم را برطرف کنید'
    return
  }

  submitting.value = true
  serverError.value = null

  try {
    const payload = normalizePayload()
    console.log('Submitting payload:', payload)

    let response
    if (isEditMode.value) {
      response = await api.put(finalSubmitUrl.value, payload)
    } else {
      response = await api.post(resolvedSubmitUrl.value, payload)
    }

    console.log('Submit response:', response.data)
    emit('success', response.data)

  } catch (error) {
    console.error('Submit error:', error)

    if (error.response?.data?.errors) {
      Object.entries(error.response.data.errors).forEach(([field, messages]) => {
        errors[field] = Array.isArray(messages) ? messages : [messages]
      })
      serverError.value = 'لطفاً خطاهای فرم را برطرف کنید'
    } else {
      serverError.value = error.response?.data?.message || 'خطا در ارسال فرم'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  initForm()
})

watch(() => props.modelId, () => {
  initForm()
})
</script>

<style scoped>
.dynamic-form {
  padding: 1.5rem;
  background: white;
  border-radius: 8px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.field-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-full {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 0.95rem;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  margin-top: 0.25rem;
}

.help-text {
  color: #6b7280;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  margin-top: 0.25rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

/* Date Picker Invalid State */
:deep(.p-invalid-date input) {
  border-color: #ef4444 !important;
}

/* Responsive */
@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .dynamic-form {
    padding: 1rem;
  }
}

/* RTL Support */
.dynamic-form {
  direction: rtl;
}

/* Utility Classes */
.flex {
  display: flex;
}

.flex-col {
  flex-direction: column;
}

.items-center {
  align-items: center;
}

.justify-center {
  justify-content: center;
}

.gap-2 {
  gap: 0.5rem;
}

.py-8 {
  padding-top: 2rem;
  padding-bottom: 2rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.w-full {
  width: 100%;
}

.cursor-pointer {
  cursor: pointer;
}

.text-red-500 {
  color: #ef4444;
}

.mr-1 {
  margin-right: 0.25rem;
}

.ml-1 {
  margin-left: 0.25rem;
}
</style>
