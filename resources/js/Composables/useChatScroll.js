import { nextTick, watch } from 'vue'

export function useChatScroll(chatMessages, containerRef, isTyping = null) {
    const scrollToBottom = () => {
        nextTick(() => {
            if (containerRef && containerRef.value) {
                const element = containerRef.value.container || containerRef.value
                if (element) {
                    element.scrollTop = element.scrollHeight
                }
            }
        })
    }

    // Watch for message changes and auto-scroll
    watch(chatMessages, () => {
        scrollToBottom()
    }, { deep: true })

    // Watch for typing indicator changes and auto-scroll
    if (isTyping) {
        watch(isTyping, (newValue) => {
            if (newValue) {
                scrollToBottom()
            }
        })
    }

    return {
        scrollToBottom
    }
}