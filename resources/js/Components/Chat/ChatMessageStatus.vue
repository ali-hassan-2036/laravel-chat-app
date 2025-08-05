<template>
  <span 
    v-if="computedStatus" 
    class="text-xs flex-shrink-0 flex items-end"
    v-html="statusIcons[computedStatus]"
  ></span>
</template>

<script setup>
import { statusIcons, getMessageStatus } from '@/Utils/chatHelpers'
import { computed, toRefs } from 'vue'

const props = defineProps({
  status: {
    type: String,
    validator: (value) => ['sending', 'sent', 'delivered', 'read'].includes(value)
  },
  // Accept the full message and authUserId for real-time status computation
  message: {
    type: Object,
    required: false
  },
  authUserId: {
    type: Number,
    required: false
  }
})

// If message and authUserId are provided, always compute status in real-time
const computedStatus = computed(() => {  
  if (props.message && props.authUserId !== undefined) {
    return getMessageStatus(props.message, props.authUserId)
  }
  // fallback to passed status prop for backward compatibility
  return props.status
})
</script>

<style scoped>
:deep(.msg-status-icon) {
  vertical-align: middle;
  margin-left: 4px;
  margin-bottom: 1px;
  display: inline-block;
}

:deep(.msg-status-sending) {
  color: #9ca3af;
  opacity: 0.7;
  animation: msg-spin 1.2s linear infinite;
}

@keyframes msg-spin {
  100% { transform: rotate(360deg); }
}

:deep(.msg-status-sent) {
  color: #9ca3af;
}

:deep(.msg-status-delivered) {
  color: #22c55e;
}

:deep(.msg-status-read) {
  color: #04142c;
}
</style>