import { ref } from 'vue'

export function useChatTyping() {
    const isOtherUserTyping = ref(false)
    let typingTimeout = null
    let lastTypedAt = 0
    
    const handleTypingEvent = (data) => {
        isOtherUserTyping.value = true
        if (typingTimeout) clearTimeout(typingTimeout)
        typingTimeout = setTimeout(() => {
            isOtherUserTyping.value = false
        }, 2000)
    }

    const updateLastTypedAt = (timestamp) => {
        lastTypedAt = timestamp
    }

    const getLastTypedAt = () => lastTypedAt

    return {
        isOtherUserTyping,
        handleTypingEvent,
        updateLastTypedAt,
        getLastTypedAt
    }
}