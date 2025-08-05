<template>
  <div class="border-t p-3 bg-white flex gap-2 items-center">
    <input
      v-model="messageText"
      @keyup.enter="handleSend"
      @input="handleInput"
      type="text"
      placeholder="Type a message..."
      class="flex-1 px-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-green-400"
      :disabled="disabled"
    />
    <button 
      @click="handleSend"
      :disabled="!messageText.trim() || disabled"
      class="bg-green-600 text-white px-5 py-2 rounded-full hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
    >
      Send
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['send', 'typing'])

const messageText = ref('')

const handleSend = () => {
  if (!messageText.value.trim()) return
  
  emit('send', messageText.value)
  messageText.value = ''
}

const handleInput = () => {
  if (messageText.value.trim()) {
    emit('typing')
  }
}
</script>