<template>
    <div class="flex items-center gap-3 p-4 border-b bg-white">
      <ChatAvatar 
        :src="user.avatar || 'https://i.pravatar.cc/40?img=5'"
        :alt="user.name"
        size="lg"
      />
      <div class="ml-2">
        <h3 class="font-semibold text-gray-900">{{ user.name }}</h3>
        <p class="text-sm text-gray-500">
          {{ isTyping ? 'typing...' : isOnline ? 'Active now' : 'Offline' }}
        </p>
      </div>
    </div>
  </template>
  
  <script setup>
  import ChatAvatar from './ChatAvatar.vue'
  
  defineProps({
    user: {
      type: Object,
      required: true
    },
    isTyping: {
      type: Boolean,
      default: false
    },
    isOnline: {
      type: Boolean,
      default: false
    }
  })
  </script>