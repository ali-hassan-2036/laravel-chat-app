<template>
    <div 
      class="flex items-end gap-2"
      :class="isOwn ? 'justify-content-end flex-row-reverse' : 'justify-start flex-row'"
    >
      <!-- Avatar -->
      <ChatAvatar 
        :src="avatarSrc"
        :alt="avatarAlt"
        size="md"
      />
  
      <!-- Message bubble -->
      <div :class="bubbleClasses">
        <span class="flex items-end justify-between gap-2">
          <span class="break-words">{{ message.message }}</span>
          <ChatMessageStatus 
            v-if="isOwn" 
            :status="messageStatus" 
          />
        </span>
      </div>
    </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import ChatAvatar from './ChatAvatar.vue'
import ChatMessageStatus from './ChatMessageStatus.vue'
import { getMessageStatus } from '@/Utils/chatHelpers'

const props = defineProps({
  message: {
    type: Object,
    required: true
  },
  authUserId: {
    type: Number,
    required: true  
  },
  otherUser: {
    type: Object,
    required: true
  }
})

const isOwn = computed(() => props.message.sender_id === props.authUserId)

// Use a ref for messageStatus to allow reactive updates
const messageStatus = ref(getMessageStatus(props.message, props.authUserId))

// Watch for changes in message object to update status in realtime
watch(
  () => [props.message.is_read, props.message.delivered_at, props.message.status],
  () => {
    messageStatus.value = getMessageStatus(props.message, props.authUserId)
  },
  { immediate: true }
)

const avatarSrc = computed(() => 
  isOwn.value 
    ? 'https://i.pravatar.cc/40?img=3' 
    : 'https://i.pravatar.cc/40?img=5'
)

const avatarAlt = computed(() => 
  isOwn.value ? 'You' : props.otherUser.name
)

const bubbleClasses = computed(() => [
  'relative px-4 py-2 rounded-2xl max-w-[70%] break-words text-sm shadow-sm',
  isOwn.value
    ? 'bg-linear-gradiant text-white rounded-br-sm'
    : 'bg-gray-200 text-gray-900 rounded-bl-sm'
])
</script>

<style scoped>
.bg-linear-gradiant {
  background: linear-gradient(90deg, #16a34a 0%, #08180e 100%);
}
</style>