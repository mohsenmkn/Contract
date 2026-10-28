<!-- src/components/contract/WorkflowTimeline.vue -->
<script setup>
import { computed } from 'vue'
import { useDateFormatter } from '@/composables/useDateFormatter'

const props = defineProps({
  status: { type: String, required: true },
  history: { type: Array, default: () => [] },
})

const { toJalaliDateTime } = useDateFormatter()

const workflowSteps = [
  { key: 'draft', label: 'پیش‌نویس', icon: 'pi pi-file-edit', actor: 'نماینده واحد' },
  { key: 'pending_legal_review', label: 'بررسی حقوقی', icon: 'pi pi-gavel', actor: 'واحد حقوقی' },
  { key: 'legal_draft', label: 'پیش‌نویس حقوقی', icon: 'pi pi-file', actor: 'کارشناس حقوقی' },
  { key: 'pending_contractor_sign', label: 'امضای پیمانکار', icon: 'pi pi-signature', actor: 'پیمانکار' },
  { key: 'pending_executive_sign', label: 'امضای اجرایی', icon: 'pi pi-user-tie', actor: 'مدیرعامل/معاون/هیئت مدیره' },
  { key: 'pending_notification', label: 'ابلاغ', icon: 'pi pi-send', actor: 'واحد حقوقی' },
  { key: 'active', label: 'فعال', icon: 'pi pi-check-circle', actor: 'سیستم' },
]

const currentStepIndex = computed(() => {
  return workflowSteps.findIndex(s => s.key === props.status)
})

const isStepCompleted = (index) => index < currentStepIndex.value
const isStepActive = (index) => index === currentStepIndex.value
const isStepPending = (index) => index > currentStepIndex.value

const getStepColor = (index) => {
  if (isStepCompleted(index)) return 'bg-green-500'
  if (isStepActive(index)) return 'bg-primary'
  return 'bg-surface-300 dark:bg-surface-600'
}

const getStepLabel = (step) => {
  const historyItem = props.history.find(h => h.status === step.key)
  return {
    ...step,
    completedAt: historyItem?.created_at,
    actor: historyItem?.actor_name || step.actor,
  }
}
</script>

<template>
  <div class="workflow-timeline">
    <div class="timeline-header">
      <h3 class="font-semibold text-sm flex items-center gap-2">
        <i class="pi pi-history text-primary" />
        فرآیند گردش کار قرارداد
      </h3>
    </div>

    <div class="timeline-steps">
      <div
          v-for="(step, index) in workflowSteps"
          :key="step.key"
          class="timeline-step"
          :class="{
          'completed': isStepCompleted(index),
          'active': isStepActive(index),
          'pending': isStepPending(index),
        }"
      >
        <div class="step-connector" v-if="index > 0" />

        <div class="step-content">
          <div class="step-circle" :class="getStepColor(index)">
            <i :class="[step.icon, 'step-icon']" />
          </div>

          <div class="step-info">
            <div class="step-label">{{ step.label }}</div>
            <div class="step-actor">{{ getStepLabel(step).actor }}</div>
            <div v-if="getStepLabel(step).completedAt" class="step-date">
              {{ toJalaliDateTime(getStepLabel(step).completedAt) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.workflow-timeline {
  padding: 1rem;
  background: var(--surface-0, #fff);
  border: 1px solid var(--surface-200, #e2e8f0);
  border-radius: 12px;
}

.timeline-header {
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--surface-200, #e2e8f0);
}

.timeline-steps {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.timeline-step {
  position: relative;
  padding: 0.75rem 0;
}

.step-connector {
  position: absolute;
  right: 19px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--surface-200, #e2e8f0);
  z-index: 0;
}

.timeline-step.completed .step-connector {
  background: #22c55e;
}

.timeline-step.active .step-connector {
  background: linear-gradient(to bottom, var(--primary-color, #1d4ed8) 50%, var(--surface-200, #e2e8f0) 50%);
}

.step-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  position: relative;
  z-index: 1;
}

.step-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.step-icon {
  color: white;
  font-size: 1rem;
}

.timeline-step.pending .step-circle {
  opacity: 0.5;
}

.timeline-step.active .step-circle {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 2px 8px rgba(29, 78, 216, 0.3); }
  50% { box-shadow: 0 2px 16px rgba(29, 78, 216, 0.6); }
}

.step-info {
  flex: 1;
  min-width: 0;
}

.step-label {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--text-color, #0f172a);
  margin-bottom: 0.25rem;
}

.timeline-step.pending .step-label {
  color: var(--text-muted, #94a3b8);
}

.step-actor {
  font-size: 0.75rem;
  color: var(--text-muted, #64748b);
}

.step-date {
  font-size: 0.7rem;
  color: var(--text-subtle, #94a3b8);
  margin-top: 0.25rem;
}

@media (max-width: 640px) {
  .step-circle {
    width: 32px;
    height: 32px;
  }
  .step-icon {
    font-size: 0.8rem;
  }
}
</style>