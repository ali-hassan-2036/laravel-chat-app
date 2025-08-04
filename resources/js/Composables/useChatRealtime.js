import { onMounted } from 'vue'
import echo from '@/echo'

export function useChatRealtime(user, authUserId, { onMessageReceived, onMessageDelivered, onMessageRead, onTyping, checkUserStatus }) {
    
    const setupRealtimeListeners = () => {
        
        // Return true/false for online status of the user
        echo.join('presence.chat')
            .here(users => {
                // Immediately set online status based on current users
                checkUserStatus(users.some(u => u.id === user.id));
            })
            .joining(u => {
                if (u.id === user.id) checkUserStatus(true);
            })
            .leaving(u => {
                if (u.id === user.id) checkUserStatus(false);
            })
        
        // Listen for incoming messages
        echo.private(`chat.${authUserId}`)
            .listen('MessageSent', (e) => {
                console.log('MessageSent event received:', e.message)
                if (e.message.sender_id == user.id) {
                    onMessageReceived(e.message)
                }
            })
            .listenForWhisper('typing', (data) => {
                if (data.sender_id === user.id && data.receiver_id === authUserId) {
                    onTyping(data)
                }
            })

        // FIXED: Listen for delivery/read updates
        echo.private(`chat.${authUserId}`)
            .listen('MessageDelivered', (e) => {
                console.log('MessageDelivered event received:', e.message)
                onMessageDelivered(e.message)
            })
            .listen('MessageRead', (e) => {
                console.log('MessageRead event received:', e.message)
                onMessageRead(e.message)
            })
    }

    const sendTypingEvent = (lastTypedAt) => {
        const now = Date.now()
        if (now - lastTypedAt < 1000) return now // Throttle

        echo.private(`chat.${user.id}`).whisper('typing', {
            sender_id: authUserId,
            receiver_id: user.id,
        })

        return now
    }

    onMounted(() => {
        setupRealtimeListeners()
    })

    return {
        sendTypingEvent
    }
}