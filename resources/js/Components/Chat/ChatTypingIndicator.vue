<template>
    <div v-if="isVisible" class="flex items-end gap-2 justify-start flex-row">
      <!-- Avatar -->
      <ChatAvatar 
        src="https://i.pravatar.cc/40?img=5"
        :alt="user.name"
        size="md"
      />
      
      <!-- Typing bubble -->
      <div class="bg-gray-200 text-gray-900 rounded-2xl rounded-bl-sm px-4 py-2 max-w-[70%] text-sm shadow-sm flex items-center gap-2">
        <svg width="24" height="24" viewBox="0 0 24 24" class="inline-block" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="5" cy="12" r="2" fill="#9ca3af">
            <animate attributeName="opacity" values="1;0.3;1" dur="1.2s" repeatCount="indefinite" begin="0s"/>
          </circle>
          <circle cx="12" cy="12" r="2" fill="#9ca3af">
            <animate attributeName="opacity" values="1;0.3;1" dur="1.2s" repeatCount="indefinite" begin="0.2s"/>
          </circle>
          <circle cx="19" cy="12" r="2" fill="#9ca3af">
            <animate attributeName="opacity" values="1;0.3;1" dur="1.2s" repeatCount="indefinite" begin="0.4s"/>
          </circle>
        </svg>
        <span>{{ user.name }} is typing...</span>
      </div>
    </div>
  </template>
  
  <script setup>
  import ChatAvatar from './ChatAvatar.vue'
  
  defineProps({
    isVisible: {
      type: Boolean,
      default: false
    },
    user: {
      type: Object,
      required: true
    }
  })
  </script>