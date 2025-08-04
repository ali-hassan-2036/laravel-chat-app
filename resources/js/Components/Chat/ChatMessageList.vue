<template>
    <div ref="container" class="flex-1 overflow-y-auto p-4 space-y-3">
      <ChatMessage
        v-for="msg in messages"
        :key="msg.id"
        :message="msg"
        :auth-user-id="authUserId"
        :other-user="otherUser"
      />
      
      <ChatTypingIndicator 
        :is-visible="isTyping" 
        :user="otherUser" 
      />
    </div>
  </template>
  
  <script setup>
  import { ref, defineExpose } from 'vue'
  import ChatMessage from './ChatMessage.vue'
  import ChatTypingIndicator from './ChatTypingIndicator.vue'
  
  defineProps({
    messages: {
      type: Array,
      required: true
    },
    authUserId: {
      type: Number,
      required: true
    },
    otherUser: {
      type: Object,
      required: true
    },
    isTyping: {
      type: Boolean,
      default: false
    }
  })
  
  const container = ref(null)
  
  defineExpose({
    container
  })
  </script>