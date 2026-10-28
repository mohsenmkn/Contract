<!-- src/views/Organization/UnitForm.vue -->
<template>
  <div class="min-h-screen bg-slate-50 p-4 md:p-6">
    <div class="max-w-3xl mx-auto">
      <!-- هدر -->
      <div class="page-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6 mb-6">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
            <Building2 class="w-7 h-7 text-blue-600" />
          </div>
          <div class="flex-1">
            <h1 class="text-2xl font-bold text-gray-800">
              {{ isEdit ? 'ویرایش واحد سازمانی' : 'واحد سازمانی جدید' }}
            </h1>
            <p class="text-gray-500 mt-1 text-sm">
              {{ isEdit ? 'ویرایش اطلاعات واحد' : 'ایجاد واحد سازمانی جدید' }}
            </p>
          </div>
          <Button
              label="بازگشت"
              icon="pi pi-arrow-right"
              severity="secondary"
              outlined
              @click="$router.push('/units')"
          />
        </div>
      </div>

      <!-- فرم -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">
              <Building2 class="label-icon" />
              نام واحد
              <span class="required">*</span>
            </label>
            <InputText
                v-model="form.name"
                :class="{ 'p-invalid': errors.name }"
                placeholder="مثال: واحد فناوری اطلاعات"
                class="form-input"
            />
            <small class="error-message" v-if="errors.name">{{ errors.name }}</small>
          </div>

          <div class="form-group full-width">
            <label class="form-label">
              <Hash class="label-icon" />
              کد واحد
              <span class="required">*</span>
            </label>
            <InputText
                v-model="form.code"
                :class="{ 'p-invalid': errors.code }"
                placeholder="مثال: IT"
                class="form-input"
                dir="ltr"
            />
            <small class="error-message" v-if="errors.code">{{ errors.code }}</small>
          </div>

          <div class="form-group full-width">
            <label class="form-label">
              <FileText class="label-icon" />
              توضیحات
            </label>
            <Textarea
                v-model="form.description"
                rows="4"
                placeholder="توضیحات مربوط به واحد..."
                class="form-input"
            />
          </div>

          <div class="form-group full-width">
            <label class="form-label">
              <Activity class="label-icon" />
              وضعیت واحد
            </label>
            <div class="flex items-center gap-3">
              <Checkbox v-model="form.is_active" :binary="true" inputId="is_active" />
              <label for="is_active" class="text-sm font-medium text-gray-700">
                {{ form.is_active ? 'فعال' : 'غیرفعال' }}
              </label>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <Button
              label="انصراف"
              severity="secondary"
              outlined
              @click="$router.push('/units')"
          />
          <Button
              label="ذخیره"
              icon="pi pi-check"
              :loading="saving"
              @click="saveUnit"
              class="bg-blue-600 hover:bg-blue-700"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import api from '@/api/axios.js'
import { Building2, Hash, FileText, Activity } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const { handleApiError } = useApiErrorHandler()

const isEdit = ref(false)
const saving = ref(false)

const form = ref({
  name: '',
  code: '',
  description: '',
  is_active: true,
})

const errors = ref({})

const validateForm = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.name?.trim()) {
    errors.value.name = 'نام واحد الزامی است'
    isValid = false
  }

  if (!form.value.code?.trim()) {
    errors.value.code = 'کد واحد الزامی است'
    isValid = false
  } else if (!/^[A-Z0-9_-]{2,20}$/i.test(form.value.code)) {
    errors.value.code = 'کد واحد باید 2 تا 20 کاراکتر و فقط شامل حروف، اعداد، خط تیره و زیرخط باشد'
    isValid = false
  }

  return isValid
}

const saveUnit = async () => {
  if (!validateForm()) return

  saving.value = true
  errors.value = {}

  try {
    if (isEdit.value) {
      await api.put(`/organizational-units/${route.params.id}`, form.value)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'واحد با موفقیت ویرایش شد', life: 3000 })
    } else {
      await api.post('/organizational-units', form.value)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'واحد جدید با موفقیت ایجاد شد', life: 3000 })
    }
    router.push('/units')
  } catch (error) {
    handleApiError(error, {
      setErrors: (validationErrors) => {
        errors.value = validationErrors
      }
    })
  } finally {
    saving.value = false
  }
}

const loadUnit = async () => {
  try {
    const response = await api.get(`/organizational-units/${route.params.id}`)
    const unit = response.data.data
    form.value = {
      name: unit.name,
      code: unit.code,
      description: unit.description || '',
      is_active: unit.is_active,
    }
  } catch (error) {
    handleApiError(error)
    router.push('/units')
  }
}

onMounted(() => {
  if (route.params.id) {
    isEdit.value = true
    loadUnit()
  }
})
</script>

<style scoped>
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: span 2;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

.label-icon {
  width: 16px;
  height: 16px;
  color: #3b82f6;
}

.required {
  color: #ef4444;
  margin-right: 0.25rem;
}

.form-input {
  width: 100%;
}

.error-message {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .form-group.full-width {
    grid-column: span 1;
  }
}
</style>