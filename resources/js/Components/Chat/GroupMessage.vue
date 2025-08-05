<template>
    <div
      :class="[
        'flex',
        'items-start',
        'space-x-3',
        isOwn ? 'justify-end' : 'justify-start'
      ]"
    >
      <!-- Avatar for other users (reuse ChatAvatar) -->
      <template v-if="!isOwn">
        <ChatAvatar
          :src="message.user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(message.user.name)}&background=random`"
          :alt="message.user.name"
          size="md"
        />
      </template>
  
      <div
        class="flex-1 min-w-0 flex flex-col"
        :class="isOwn ? 'items-end' : 'items-start'"
      >
        <!-- Name and time for other users -->
        <div
          v-if="!isOwn"
          class="flex items-center space-x-2 mb-1"
        >
          <span class="font-semibold text-gray-900">{{ message.user.name }}</span>
          <span class="text-xs text-gray-500 ml-1">
            {{ formatMessageTime(message.created_at) }}
          </span>
        </div>
        
        <!-- Reply Context -->
        <div
          v-if="message.replyTo"
          class="bg-gray-200 border-l-4 border-blue-500 pl-3 py-2 mb-2 rounded-r"
          :class="isOwn ? 'ml-auto' : ''"
        >
          <div class="text-xs text-gray-600 font-medium">
            Replying to {{ message.replyTo.user.name }}
          </div>
          <div class="text-sm text-gray-700 truncate">
            {{ message.replyTo.message }}
          </div>
        </div>
        
        <!-- Message bubble (similar to ChatMessage but with group styling) -->
        <div
          @contextmenu.prevent="$emit('context-menu', { message, event: $event })"
          :class="[
            'rounded-full',
            'px-4',
            'py-2',
            'shadow-sm',
            'inline-block',
            'max-w-lg',
            'break-words',
            isOwn
              ? 'bg-gradient-to-r from-green-500 via-green-700 to-green-900 text-white ml-auto'
              : 'bg-white text-gray-900'
          ]"
        >
          {{ message.message }}
        </div>

        <ChatMessageStatus 
            v-if="isOwn" 
            :status="messageStatus" 
            :message="message"
            :authUserId="authUserId"
          />
  
        <!-- Time for own messages (WhatsApp style) -->
        <div
          v-if="isOwn"
          class="text-xs text-gray-500 mt-1 mr-1"
        >
          {{ formatMessageTime(message.created_at) }}
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed, ref } from 'vue'
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
    }
  })

  const messageStatus = ref(getMessageStatus(props.message, props.authUserId))

  
  defineEmits(['context-menu'])
  
  const isOwn = computed(() => props.message.user.id === props.authUserId)
  
  const formatMessageTime = (timestamp) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffInMinutes = (now - date) / (1000 * 60)
    
    if (diffInMinutes < 1) {
      return 'Just now'
    } else if (diffInMinutes < 60) {
      return `${Math.floor(diffInMinutes)}m ago`
    } else {
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
  }
  </script>