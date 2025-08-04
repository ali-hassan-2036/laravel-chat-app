<script setup>
import { ref, reactive, onMounted, nextTick, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import AddGroupMemeberModal from '@/Components/Group/AddGroupMemeberModal.vue'
import { useChatScroll } from '@/Composables/useChatScroll.js'
import { useGroupRealtime } from '@/Composables/useGroupRealtime.js'


const props = defineProps({
  group: Object,
  messages: Object,
  members: Array,
  users: Array,
})

const page = usePage()
const messagesContainer = ref(null)
const showMembers = ref(false)
const showSettings = ref(false)
const newMessage = ref('')
const sending = ref(false)
const replyingTo = ref(null)

const allMessages = ref([...props.messages.data])
const openModal = ref(false)

const contextMenu = reactive({
  show: false,
  x: 0,
  y: 0,
  message: null
})

// Use the chat scroll composable
// Fix: useChatScroll must be called after DOM is mounted and messagesContainer is available
let stopChatScrollWatch = null

onMounted(() => {
  // Close context menu on click outside
  document.addEventListener('click', () => {
    contextMenu.show = false
  })

  // Setup chat scroll watcher after DOM is mounted and messagesContainer is available
  stopChatScrollWatch = useChatScroll(allMessages, messagesContainer)
})

/**
 * If you want to clean up the watcher when the component is unmounted,
 * you can add:
 * 
 * import { onUnmounted } from 'vue'
 * onUnmounted(() => {
 *   if (stopChatScrollWatch) stopChatScrollWatch()
 * })
 */

 useGroupRealtime(props.group.id, {
    onMessageReceived: (message) => {
        console.log('message', message);
        
        allMessages.value.push(message)
    },
    onUserJoined: (user) => {
        console.log('User joined:', user.name)
    },
    onUserLeft: (user) => {
        console.log('User left:', user.name)
    }
})

const sendMessage = async () => {
  if (!newMessage.value.trim() || sending.value) return
  
  sending.value = true
  
  try {
    const response = await axios.post(route('group-messages.store', props.group.id), {
      message: newMessage.value.trim(),
      reply_to: replyingTo.value?.id || null
    })
    
    allMessages.value.push(response.data.message)
    newMessage.value = ''
    replyingTo.value = null
    // No need to manually scroll, useChatScroll handles it
  } catch (error) {
    console.error('Error sending message:', error)
  } finally {
    sending.value = false
  }
}

// Removed scrollToBottom, handled by useChatScroll

const showMessageMenu = (message, event) => {
  contextMenu.message = message
  contextMenu.x = event.clientX
  contextMenu.y = event.clientY
  contextMenu.show = true
}

const replyToMessage = (message) => {
  replyingTo.value = message
  contextMenu.show = false
}

const cancelReply = () => {
  replyingTo.value = null
}

const canDeleteMessage = (message) => {
  return message.user.id === page.props.auth.user.id || props.group.is_moderator
}

const deleteMessage = async (message) => {
  if (!confirm('Are you sure you want to delete this message?')) return
  
  try {
    await axios.delete(route('group-messages.destroy', message.id))
    const index = allMessages.value.findIndex(m => m.id === message.id)
    if (index > -1) {
      allMessages.value.splice(index, 1)
    }
  } catch (error) {
    console.error('Error deleting message:', error)
  }
  
  contextMenu.show = false
}

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

const showMemberMenu = (member, event) => {
  // Implementation for member management menu
  console.log('Show member menu for:', member.name)
}
</script>

<template>
    <AuthenticatedLayout>
      <div class="flex flex-col h-[90vh]"> <!-- Set to 75% of viewport height -->
        <!-- Group Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4 flex-shrink-0">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <button
                @click="$inertia.visit(route('users.index'))"
                class="text-gray-500 hover:text-gray-700"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
              </button>
              
              <div class="flex items-center space-x-3">
                <img
                  v-if="group.image"
                  :src="`/storage/${group.image}`"
                  :alt="group.name"
                  class="w-10 h-10 rounded-full object-cover"
                >
                <div
                  v-else
                  class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center"
                >
                  <span class="text-gray-600 font-semibold">
                    {{ group.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                
                <div>
                  <h1 class="text-xl font-semibold text-gray-900">{{ group.name }}</h1>
                  <p class="text-sm text-gray-500">{{ members.length }} members</p>
                </div>
              </div>
            </div>
            
            <div class="flex items-center space-x-2">

              <button
                v-if="group.is_moderator"
                @click="openModal = true"
                class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100"
                title="Add Member"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
              </button>

              <button
                @click="showMembers = !showMembers"
                class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100"
                title="View Members"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
              </button>
              
              <!-- <button
                v-if="group.is_admin"
                @click="showSettings = !showSettings"
                class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100"
                title="Group Settings"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </button> -->
            </div>
          </div>
        </div>
  
        <div class="flex flex-1 min-h-0 overflow-hidden"> <!-- min-h-0 ensures children flex properly -->
          <!-- Main Chat Area -->
          <div class="flex-1 flex flex-col min-h-0">
            <!-- Messages Container -->
            <div
              ref="messagesContainer"
              class="flex-1 min-h-0 overflow-y-auto p-4 space-y-4 bg-gray-50"
              style="max-height: 100%;"
            >
              <div
                v-for="message in allMessages"
                :key="message.id"
                class="flex items-start space-x-3"
              >
                <img
                  v-if="message.user.avatar"
                  :src="message.user.avatar"
                  :alt="message.user.name"
                  class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                >
                <div
                  v-else
                  class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center flex-shrink-0"
                >
                  <span class="text-gray-600 text-sm font-semibold">
                    {{ message.user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                
                <div class="flex-1 min-w-0">
                  <div class="flex items-center space-x-2 mb-1">
                    <span class="font-semibold text-gray-900">{{ message.user.name }}</span>
                    <span class="text-xs text-gray-500 ml-1">
                      {{ formatMessageTime(message.created_at) }}
                    </span>
                  </div>
                  
                  <!-- Reply Context -->
                  <div
                    v-if="message.replyTo"
                    class="bg-gray-200 border-l-4 border-blue-500 pl-3 py-2 mb-2 rounded-r"
                  >
                    <div class="text-xs text-gray-600 font-medium">
                      Replying to {{ message.replyTo.user.name }}
                    </div>
                    <div class="text-sm text-gray-700 truncate">
                      {{ message.replyTo.message }}
                    </div>
                  </div>
                  
                  <div
                    class="bg-white rounded-lg px-4 py-2 shadow-sm inline-block max-w-lg break-words"
                    @contextmenu.prevent="showMessageMenu(message, $event)"
                  >
                    {{ message.message }}
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Reply Preview -->
            <div
              v-if="replyingTo"
              class="bg-blue-50 border-l-4 border-blue-500 px-4 py-2 flex items-center justify-between flex-shrink-0"
            >
              <div>
                <div class="text-sm font-medium text-blue-900">
                  Replying to {{ replyingTo.user.name }}
                </div>
                <div class="text-sm text-blue-700 truncate">
                  {{ replyingTo.message }}
                </div>
              </div>
              <button
                @click="cancelReply"
                class="text-blue-500 hover:text-blue-700"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
  
            <!-- Message Input -->
            <form @submit.prevent="sendMessage" class="bg-white border-t border-gray-200 p-4 flex-shrink-0">
              <div class="flex items-center space-x-4">
                <div class="flex-1">
                  <input
                    v-model="newMessage"
                    type="text"
                    placeholder="Type a message..."
                    class="w-full border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :disabled="sending"
                  >
                </div>
                
                <button
                  type="submit"
                  :disabled="!newMessage.trim() || sending"
                  class="bg-green-500 hover:bg-green-600 disabled:bg-gray-300 text-white rounded-full p-2 ml-2 transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                  </svg>
                </button>
              </div>
            </form>
          </div>
  
          <!-- Members Sidebar -->
          <div
            v-if="showMembers"
            class="w-80 bg-white border-l border-gray-200 flex flex-col min-h-0"
          >
            <div class="p-4 border-b border-gray-200 flex-shrink-0">
              <h3 class="text-lg font-semibold text-gray-900">Members ({{ members.length }})</h3>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 min-h-0">
              <div class="space-y-3">
                <div
                  v-for="member in members"
                  :key="member.id"
                  class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50"
                >
                  <div class="flex items-center space-x-3">
                    <img
                      v-if="member.avatar"
                      :src="member.avatar"
                      :alt="member.name"
                      class="w-8 h-8 rounded-full object-cover"
                    >
                    <div
                      v-else
                      class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center"
                    >
                      <span class="text-gray-600 text-sm font-semibold">
                        {{ member.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    
                    <div>
                      <div class="font-medium text-gray-900">{{ member.name }}</div>
                      <div class="text-sm text-gray-500">{{ member.email }}</div>
                    </div>
                  </div>
                  
                  <div class="flex items-center space-x-2">
                    <span
                      v-if="member.role === 'admin'"
                      class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs"
                    >
                      Admin
                    </span>
                    <span
                      v-else-if="member.role === 'moderator'"
                      class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs"
                    >
                      Moderator
                    </span>
                    
                    <button
                      v-if="group.is_admin && member.id !== $page.props.auth.user.id"
                      @click="showMemberMenu(member, $event)"
                      class="text-gray-400 hover:text-gray-600"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Context Menus -->
      <div
        v-if="contextMenu.show"
        :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
        class="fixed bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50"
      >
        <button
          @click="replyToMessage(contextMenu.message)"
          class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
        >
          Reply
        </button>
        <button
          v-if="canDeleteMessage(contextMenu.message)"
          @click="deleteMessage(contextMenu.message)"
          class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
        >
          Delete
        </button>
    </div>
        <AddGroupMemeberModal :users="users" :group-id="group.id" :openModal="openModal" @close="openModal = false"/>
    </AuthenticatedLayout>
  </template>