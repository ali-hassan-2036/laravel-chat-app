<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, nextTick, onMounted, watch } from 'vue'
import echo from '@/echo'

const props = defineProps({
    user: Object,
    messages: Array,
    authUserId: Number
})

const newMessage = ref('')
const chatMessages = ref(
    props.messages.map(m => ({ ...m }))
)
const chatContainer = ref(null)

const statusIcons = {
    sending: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24' class='msg-status-icon msg-status-sending'><circle cx='12' cy='12' r='10' stroke='currentColor' stroke-width='2' fill='none'/><path d='M12 6v6l4 2' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`,
    sent: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24' class='msg-status-icon msg-status-sent'><path d='M20 6L9 17l-5-5' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`,
    delivered: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24' class='msg-status-icon msg-status-delivered'><path d='M17 7l-7.5 7.5-3.5-3.5' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M22 7l-7.5 7.5-1.5-1.5' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`,
    read: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24' class='msg-status-icon msg-status-read'><path d='M17 7l-7.5 7.5-3.5-3.5' stroke='#3b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M22 7l-7.5 7.5-1.5-1.5' stroke='#3b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`,
}

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight
        }
    })
}

watch(chatMessages, scrollToBottom)

const getMessageStatus = (msg) => {
    if (msg.sender_id !== props.authUserId) return null;
    if (msg.is_read) return 'read';
    if (msg.delivered_at) return 'delivered';
    if (msg.status === 'sent') return 'sent';
    if (msg.status === 'sending') return 'sending';
    return 'sent';
}

// Helper to mark all undelivered messages as delivered (for real-time delivery)
const markUndeliveredAsDelivered = () => {
    // Find all messages sent by the other user to me that are not delivered
    const undelivered = chatMessages.value.filter(
        msg =>
            msg.sender_id === props.user.id &&
            msg.receiver_id === props.authUserId &&
            !msg.delivered_at
    )
    if (undelivered.length > 0) {
        router.post(route('chat.markAsDelivered', { user: props.user.id }), {}, {
            preserveScroll: true
        })
    }
}

// Helper to mark all unread messages as read (for real-time read)
const markUnreadAsRead = () => {
    // Find all messages sent by the other user to me that are not read
    const unread = chatMessages.value.filter(
        msg =>
            msg.sender_id === props.user.id &&
            msg.receiver_id === props.authUserId &&
            !msg.is_read
    )
    if (unread.length > 0) {
        router.post(route('chat.markAsRead', { user: props.user.id }), {}, {
            preserveScroll: true
        })
    }
}

// --- Typing indicator logic ---
const isOtherUserTyping = ref(false)
let typingTimeout = null
let lastTypedAt = 0

// Notify the receiver when sender is typing
const sendTypingEvent = () => {    
    
    
    // Only send if not empty and not just whitespace
    if (!newMessage.value.trim()) return
    // Throttle: only send if at least 1s since last sent
    const now = Date.now()
    if (now - lastTypedAt < 1000) return
    lastTypedAt = now

    // Send whisper to the other user's channel
    echo.private(`chat.${props.user.id}`).whisper('typing', {
        sender_id: props.authUserId,
        receiver_id: props.user.id,
    })
}

// Listen for typing events from the other user
onMounted(() => {
    // RECEIVER: Listen for incoming messages on chat.{authUserId}
    echo.private(`chat.${props.authUserId}`)
        .listen('MessageSent', (e) => {
            // Only add if from the user we're chatting with
            if (e.message.sender_id == props.user.id) {
                chatMessages.value.push({ ...e.message })
                // Mark as delivered in real time
                router.post(route('chat.markAsDelivered', { user: props.user.id }), {}, {
                    preserveScroll: true
                })
                // Mark as read in real time if chat is open
                router.post(route('chat.markAsRead', { user: props.user.id }), {}, {
                    preserveScroll: true
                })
            }
            nextTick(scrollToBottom)
        })
        .listenForWhisper('typing', (data) => {
            nextTick(scrollToBottom)
            // Only show typing if from the user we're chatting with
            if (data.sender_id === props.user.id && data.receiver_id === props.authUserId) {                
                isOtherUserTyping.value = true
                if (typingTimeout) clearTimeout(typingTimeout)
                typingTimeout = setTimeout(() => {
                    isOtherUserTyping.value = false
                }, 2000)
            }            
        })

    // SENDER: Listen for delivery/read updates on chat.{authUserId} (your own channel)
    echo.private(`chat.${props.authUserId}`)
        .listen('MessageDelivered', (e) => {

            const msg = chatMessages.value.find(m =>
                m.id === e.message.id ||
                (m.message === e.message.message && m.sender_id === props.authUserId)
            )
            if (msg) {
                msg.delivered_at = e.message.delivered_at
                msg.status = 'delivered'
            }
        })
        .listen('MessageRead', (e) => {

            const msg = chatMessages.value.find(m =>
                m.id === e.message.id ||
                (m.message === e.message.message && m.sender_id === props.authUserId)
            )
            if (msg) {
                msg.is_read = true
                msg.status = 'read'
                if (e.message.delivered_at) {
                    msg.delivered_at = e.message.delivered_at
                }
            }
        })

    // Mark undelivered messages as delivered on mount (for real-time, not just reload)
    markUndeliveredAsDelivered();

    // Mark unread messages as read on mount (for real-time, not just reload)
    markUnreadAsRead();

    scrollToBottom()
})

// Also watch for new incoming messages and mark as delivered/read if needed (for real-time delivery/read)
watch(chatMessages, () => {
    markUndeliveredAsDelivered();
    markUnreadAsRead();
})

const sendMessage = () => {
    if (!newMessage.value.trim()) return

    const tempId = Date.now()
    const tempMessage = {
        id: tempId,
        sender_id: props.authUserId,
        receiver_id: props.user.id,
        message: newMessage.value,
        status: 'sending',
        delivered_at: null,
        is_read: false,
    }
    chatMessages.value.push({ ...tempMessage })
    nextTick(scrollToBottom)

    const sentText = newMessage.value
    newMessage.value = ''

    router.post(route('chat.store'), {
        receiver_id: props.user.id,
        message: sentText
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            nextTick(scrollToBottom)
        }
    })
}

const markAsReadMessages = () => {
    router.post(route('chat.markAsRead', { user: props.user.id }), {}, {
        preserveScroll: true
    })
}
</script>

<template>
    <Head :title="`Chat with ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Chat with {{ user.name }}
            </h2>
        </template>

        <div class="flex flex-col h-[75vh] max-w-3xl mx-auto bg-gray-50 shadow rounded-lg mt-2 border">
            <!-- Messages -->
            <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
                <div v-for="msg in chatMessages" :key="msg.id"
                    class="flex items-end gap-2"
                    :class="msg.sender_id === authUserId ? 'justify-content-end flex-row-reverse' : 'justify-start flex-row'">

                    <!-- Avatar -->
                    <img
                        :src="msg.sender_id === authUserId ? 'https://i.pravatar.cc/40?img=3' : 'https://i.pravatar.cc/40?img=5'"
                        class="w-8 h-8 rounded-full shrink-0"
                        :alt="msg.sender_id === authUserId ? 'You' : user.name"
                    />

                    <!-- Message bubble -->
                    <div :class="[
                        'relative px-4 py-2 rounded-2xl max-w-[70%] break-words text-sm shadow-sm',
                        msg.sender_id === authUserId
                            ? 'bg-green-400 text-white rounded-br-sm'
                            : 'bg-gray-200 text-gray-900 rounded-bl-sm'
                    ]">
                        <span class="flex items-end justify-between gap-2">
                            <span class="break-words">{{ msg.message }}</span>
                            <span v-if="msg.sender_id === authUserId" class="text-xs flex-shrink-0 flex items-end">
                                <span v-html="statusIcons[getMessageStatus(msg)]"></span>
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Typing indicator -->
                <div v-if="isOtherUserTyping" class="flex items-end gap-2 justify-start flex-row">
                    <!-- Avatar -->
                    <img
                        src="https://i.pravatar.cc/40?img=5"
                        class="w-8 h-8 rounded-full shrink-0"
                        :alt="user.name"
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
            </div>

            <!-- Input -->
            <div class="border-t p-3 bg-white flex gap-2 items-center">
                <input
                    v-model="newMessage"
                    @keyup.enter="sendMessage"
                    @input="sendTypingEvent"
                    type="text"
                    placeholder="Type a message..."
                    class="flex-1 px-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-green-400"
                />
                <button @click="sendMessage"
                    class="bg-green-600 text-white px-5 py-2 rounded-full hover:bg-green-700 transition">
                    Send
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>



<style scoped>
.msg-status-icon {
  vertical-align: middle;
  margin-left: 4px;
  margin-bottom: 1px;
  display: inline-block;
}
.msg-status-sending {
  color: #9ca3af;
  opacity: 0.7;
  animation: msg-spin 1.2s linear infinite;
}
@keyframes msg-spin {
  100% { transform: rotate(360deg); }
}
.msg-status-sent {
  color: #9ca3af;
}
.msg-status-delivered {
  color: #22c55e;
}
.msg-status-read {
  color: #04142c;
}
</style>
