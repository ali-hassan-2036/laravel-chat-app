import { ref, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'

export function useChatMessages(initialMessages, user, authUserId) {
    const chatMessages = ref(initialMessages.map(m => ({ ...m })))

    const addMessage = (message) => {
        chatMessages.value.push({ ...message })
    }

    // FIXED: Better message finding logic
    const findMessage = (messageId, messageText, senderId) => {
        return chatMessages.value.find(m => {
            // Try to match by real ID first
            if (m.id === messageId) return true
            
            // For temporary messages, match by content and sender
            if (messageText && senderId && 
                m.message === messageText && 
                m.sender_id === senderId &&
                m.status === 'sending') {
                return true
            }
            
            return false
        })
    }

    const updateMessageStatus = (messageId, updates) => {
        const msg = findMessage(messageId, updates.message, updates.sender_id || authUserId)
        if (msg) {
            Object.assign(msg, updates)
        } else {
            console.log('Message not found for status update:', messageId, updates)
        }
    }

    // FIXED: Replace temporary message with real message from server
    const replaceTemporaryMessage = (tempMessage, realMessage) => {
        const index = chatMessages.value.findIndex(m => 
            m.message === tempMessage.message && 
            m.sender_id === tempMessage.sender_id &&
            m.status === 'sending'
        )
        
        if (index !== -1) {
            // Replace temp message with real message and set initial status
            chatMessages.value[index] = {
                ...realMessage,
                status: 'sent', // Set to sent initially
                delivered_at: realMessage.delivered_at || null,
                is_read: realMessage.is_read || false
            }
        }
    }

    const sendMessage = (messageText) => {
        if (!messageText.trim()) return

        const tempId = `temp_${Date.now()}_${Math.random()}`
        const tempMessage = {
            id: tempId,
            sender_id: authUserId,
            receiver_id: user.id,
            message: messageText,
            status: 'sending',
            delivered_at: null,
            is_read: false,
            created_at: new Date().toISOString(),
        }
        
        addMessage(tempMessage)

        router.post(route('chat.store'), {
            receiver_id: user.id,
            message: messageText
        }, {
            preserveScroll: true,
            onSuccess: (page) => {                
                // This depends on your backend returning the new message
                if (page.props && page.props.messages) {
                    const newMessage = page.props.messages.find(m => 
                        m.message === messageText && 
                        m.sender_id === authUserId &&
                        !chatMessages.value.find(existing => existing.id === m.id)
                    )
                    
                    if (newMessage) {
                        replaceTemporaryMessage(tempMessage, newMessage)
                    } else {
                        // Fallback: just update status to sent
                        updateMessageStatus(tempId, { 
                            status: 'sent',
                            message: messageText,
                            sender_id: authUserId 
                        })
                    }
                } else {
                    // Fallback: just update status to sent  
                    updateMessageStatus(tempId, { 
                        status: 'sent',
                        message: messageText,
                        sender_id: authUserId 
                    })
                }
            },
            onError: (errors) => {
                console.error('Message send failed:', errors)
                // Update temp message to show error
                updateMessageStatus(tempId, { 
                    status: 'failed',
                    message: messageText,
                    sender_id: authUserId 
                })
            }
        })
    }

    const markAsDelivered = () => {
        const undelivered = chatMessages.value.filter(
            msg => msg.sender_id === user.id && 
                   msg.receiver_id === authUserId && 
                   !msg.delivered_at
        )
        
        if (undelivered.length > 0) {
            router.post(route('chat.markAsDelivered', { user: user.id }), {}, {
                preserveScroll: true
            })
        }
    }

    const markAsRead = () => {
        const unread = chatMessages.value.filter(
            msg => msg.sender_id === user.id && 
                   msg.receiver_id === authUserId && 
                   !msg.is_read
        )
        
        if (unread.length > 0) {
            router.post(route('chat.markAsRead', { user: user.id }), {}, {
                preserveScroll: true
            })
        }
    }

    return {
        chatMessages,
        addMessage,
        updateMessageStatus,
        sendMessage,
        markAsDelivered,
        markAsRead,
        findMessage // Export for debugging
    }
}