import { useEcho, useEchoPresence } from '@laravel/echo-vue';
import { onMounted, onBeforeUnmount, ref, unref } from 'vue'

export function useChatRealtime(user, authUserId, { onMessageReceived, onMessageDelivered, onMessageRead, onTyping, checkUserStatus }) {
    const presenceChannelRef = ref(null);
    const messageChannelRef = ref(null);
    const isConnected = ref(false);

    // Initialize presence channel hook for online status and typing
    const { channel: getPresenceChannel } = useEchoPresence("presence.chat");

    // Initialize private channel for messages
    const { channel: getMessageChannel } = useEcho(`chat.${authUserId}`);

    const sendTypingEvent = (lastTypedAt) => {
        const now = Date.now()
        if (now - lastTypedAt < 1000) return now // Throttle

        // Whisper typing event on the other user's private channel
        if (messageChannelRef.value && window.Echo) {
            const typingChannel = window.Echo.private(`chat.${user.id}`);
            typingChannel.whisper('typing', {
                sender_id: authUserId,
                receiver_id: user.id,
            });
        }

        return now
    }

    const setupPresenceChannel = () => {
        const presenceChannel = getPresenceChannel();
        if (!presenceChannel) return;

        presenceChannelRef.value = presenceChannel;

        // Check initial users online status
        presenceChannel.here((currentUsers) => {
            console.log('Users currently in presence channel:', currentUsers);
            const userOnline = currentUsers.some(u => u.id === unref(user).id);
            checkUserStatus(userOnline);
        });

        // Listen for users joining
        presenceChannel.joining((joiningUser) => {
            console.log('User joining:', joiningUser);
            if (joiningUser.id === unref(user).id) {
                checkUserStatus(true);
            }
        });

        // Listen for users leaving
        presenceChannel.leaving((leavingUser) => {
            console.log('User leaving:', leavingUser);
            if (leavingUser.id === unref(user).id) {
                checkUserStatus(false);
            }
        });
    }

    const setupMessageChannel = () => {
        const messageChannel = getMessageChannel();
        if (!messageChannel) return;

        messageChannelRef.value = messageChannel;

        // Listen for incoming messages
        messageChannel.listen('MessageSent', (e) => {
            console.log('MessageSent event received:', e.message);
            if (e.message.sender_id == unref(user).id) {
                onMessageReceived(e.message);
            }
        });

        // Listen for message delivered events
        messageChannel.listen('MessageDelivered', (e) => {
            console.log('MessageDelivered event received:', e.message);
            onMessageDelivered(e.message);
        });

        // Listen for message read events
        messageChannel.listen('MessageRead', (e) => {
            console.log('MessageRead event received:', e.message);
            onMessageRead(e.message);
        });

        // Listen for typing events
        messageChannel.listenForWhisper('typing', (data) => {
            console.log('Typing event received:', data);
            if (data.sender_id === unref(user).id) {
                onTyping(data);
            }
        });

        isConnected.value = true;
    }

    const cleanup = () => {
        // Stop listening to events and leave channels
        if (window.Echo) {
            if (presenceChannelRef.value) {
                window.Echo.leave('presence.chat');
                presenceChannelRef.value = null;
            }
            if (messageChannelRef.value) {
                window.Echo.leave(`chat.${authUserId}`);
                messageChannelRef.value = null;
            }
        }
        isConnected.value = false;
    }

    // Setup channels when component mounts
    onMounted(() => {
        setupPresenceChannel();
        setupMessageChannel();
    });

    // Cleanup when component unmounts
    onBeforeUnmount(() => {
        cleanup();
    });

    return {
        sendTypingEvent,
        isConnected,
        cleanup
    }
}
