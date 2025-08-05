<template>
    <Head :title="`Chat with ${user.name}`" />
  
    <AuthenticatedLayout>
  
      <div class="flex flex-col h-[75vh] max-w-3xl mx-auto bg-gray-50 shadow rounded-lg mt-2 border">
        <ChatHeader 
          :user="user" 
          :is-typing="isOtherUserTyping"
          :is-online="isUserOnline" 
        />
        
        <ChatMessageList
          ref="messageListRef"
          :messages="chatMessages"
          :auth-user-id="authUserId"
          :other-user="user"
          :is-typing="isOtherUserTyping"
        />
        
        <ChatInput 
          @send="handleSendMessage"
          @typing="handleTyping"
        />
      </div>
    </AuthenticatedLayout>
  </template>
  
  <script setup>
  import { onMounted, ref, watch } from 'vue'
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
  import { Head, usePage } from '@inertiajs/vue3'
  
  // Components
  import ChatHeader from '@/Components/Chat/ChatHeader.vue'
  import ChatMessageList from '@/Components/Chat/ChatMessageList.vue'
  import ChatInput from '@/Components/Chat/ChatInput.vue'
  
  // Composables
  import { useChatMessages } from '@/Composables/useChatMessages'
  import { useChatRealtime } from '@/Composables/useChatRealtime'
  import { useChatTyping } from '@/Composables/useChatTyping'
  import { useChatScroll } from '@/Composables/useChatScroll'
  
  
  const props = defineProps({
    user: Object,
    messages: Array,
    authUserId: Number
  })
  
  // Refs
  const messageListRef = ref(null)
  const isUserOnline = ref(false);
  
  // Setup composables
  const { chatMessages, addMessage, updateMessageStatus, sendMessage, markAsDelivered, markAsRead } = 
    useChatMessages(props.messages, props.user, props.authUserId)
  
  const { isOtherUserTyping, handleTypingEvent, updateLastTypedAt, getLastTypedAt } = 
    useChatTyping()
  
  const { scrollToBottom } = useChatScroll(chatMessages, messageListRef, isOtherUserTyping)
  
  const { sendTypingEvent } = useChatRealtime(props.user, props.authUserId, {
  onMessageReceived: (message) => {
    addMessage(message)
    markAsDelivered()
    markAsRead()
  },
  onMessageDelivered: (message) => {
    updateMessageStatus(message.id, {
      delivered_at: message.delivered_at,
      status: 'delivered',
      message: message.message,
      sender_id: message.sender_id
    })
  },
  onMessageRead: (message) => {
    updateMessageStatus(message.id, {
      is_read: true,
      status: 'read',
      delivered_at: message.delivered_at,
      message: message.message,
      sender_id: message.sender_id
    })
  },
  onTyping: (data) => {
    handleTypingEvent(data)
  },

  checkUserStatus: (value) => {    
    isUserOnline.value = value
  }
})
  
  // Event handlers
  const handleSendMessage = (messageText) => {  
    sendMessage(messageText)
  }
  
  const handleTyping = () => {
    const newTimestamp = sendTypingEvent(getLastTypedAt())
    if (newTimestamp) {
      updateLastTypedAt(newTimestamp)
    }
  }
  
  onMounted(() => {
    markAsDelivered()
    markAsRead()
    scrollToBottom()
  })
  </script>
  