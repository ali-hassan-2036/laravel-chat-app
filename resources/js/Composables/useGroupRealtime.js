// composables/useGroupRealtime.js
import { onMounted, onUnmounted } from 'vue'
import echo from '@/echo'

export function useGroupRealtime(groupId, {onMessageReceived, onUserJoined, onUserLeft}) {
    let channel = null

    const setupGroupRealtimeListeners = () => {        
        // Fix 1: Use the correct channel name with groupId
        channel = echo.private(`group.${groupId}`)
            .listen('GroupMessageSent', (e) => {
                if (onMessageReceived) {
                    onMessageReceived(e.message)
                }
            })
            .listen('UserJoinedGroup', (e) => {
                if (onUserJoined) {
                    onUserJoined(e.user)
                }
            })
            .listen('UserLeftGroup', (e) => {
                if (onUserLeft) {
                    onUserLeft(e.user)
                }
            })
            .error((error) => {
                console.error('Echo error:', error)
            })
    }

    const cleanup = () => {
        if (channel) {
            echo.leave(`group.${groupId}`)
            channel = null
        }
    }

    onMounted(() => {
        setupGroupRealtimeListeners()
    })

    onUnmounted(() => {
        cleanup()
    })

    // Return cleanup function for manual cleanup if needed
    return cleanup
}