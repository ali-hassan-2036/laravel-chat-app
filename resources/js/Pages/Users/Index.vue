<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import CreateGroupModal from '@/Components/Group/CreateGroupModal.vue'
import { ref, computed } from 'vue'

const props = defineProps({ users: Array, groups: Array })
const { users, groups } = props

const showCreateModal = ref(false)

// Helper to extract last message and time for group
function getGroupLastMessage(group) {
    if (!group.latest_message) return null
    // Some APIs may return latest_message as null or as an object with user/message/created_at
    // Defensive: user may be object or string
    let userName = ''
    if (group.latest_message.user) {
        userName = typeof group.latest_message.user === 'object'
            ? group.latest_message.user.name
            : group.latest_message.user
    }
    return {
        message: group.latest_message.message,
        created_at: group.latest_message.created_at,
        user: userName,
    }
}

// Combine groups and users into a single chat list, sorted by latest message (like WhatsApp)
const chatList = computed(() => {
    // Map groups to a unified chat object
    const groupChats = (groups || []).map(group => {
        const lastMsg = getGroupLastMessage(group)
        return {
            id: `group-${group.id}`,
            type: 'group',
            name: group.name,
            image: group.image ? `/storage/${group.image}` : null,
            description: group.description,
            members_count: group.members_count,
            is_admin: group.is_admin,
            is_moderator: group.is_moderator,
            latest_message: lastMsg,
            link: route('groups.show', group.id),
        }
    })

    // Map users to a unified chat object
    const userChats = (users || []).map(user => ({
        id: `user-${user.id}`,
        type: 'user',
        name: user.name,
        image: null,
        description: null,
        members_count: null,
        is_admin: false,
        is_moderator: false,
        latest_message: user.last_message
            ? {
                message: user.last_message,
                created_at: user.last_message_at,
                user: user.name,
            }
            : null,
        link: route('chat.show', user.id),
    }))

    // Merge and sort by latest message date (descending)
    const allChats = [...groupChats, ...userChats]
    return allChats.sort((a, b) => {
        const aTime = a.latest_message && a.latest_message.created_at ? new Date(a.latest_message.created_at).getTime() : 0
        const bTime = b.latest_message && b.latest_message.created_at ? new Date(b.latest_message.created_at).getTime() : 0
        return bTime - aTime
    })
})
</script>

<template>
    <Head title="Chats" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Chats
                </h2>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-500 via-green-700 to-green-900 border-0 rounded-full font-bold text-sm text-white shadow-lg hover:from-green-600 hover:to-green-800 hover:scale-105 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-green-300"
                >
                    Create Chat Group
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-6">
                <ul v-if="chatList.length">
                    <li
                        v-for="chat in chatList"
                        :key="chat.id"
                        class="border-b last:border-0"
                    >
                        <Link
                            :href="chat.link"
                            class="block p-4 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <!-- Avatar for group or user -->
                                    <img
                                        v-if="chat.type === 'group' && chat.image"
                                        :src="chat.image"
                                        alt="Group Image"
                                        class="w-10 h-10 rounded-full object-cover"
                                    />
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                                        <span>{{ chat.name.charAt(0).toUpperCase() }}</span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 flex items-center gap-2">
                                            {{ chat.name }}
                                        </div>
                                        <!-- User chat last message -->
                                        <span
                                            v-if="chat.latest_message && chat.type !== 'group'"
                                            class="block font-medium text-gray-700 truncate max-w-[180px]"
                                        >
                                            {{ chat.latest_message.message }}
                                        </span>
                                        <!-- Group chat last message -->
                                        <span
                                            v-if="chat.latest_message && chat.type === 'group'"
                                            class="block text-xs text-gray-500"
                                        >
                                            <template v-if="chat.latest_message.user">
                                                by {{ chat.latest_message.user }}:
                                            </template>
                                            {{ chat.latest_message.message }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4 text-xs text-gray-400 whitespace-nowrap" v-if="chat.latest_message && chat.latest_message.created_at">
                                    <span class="block">
                                        {{ new Date(chat.latest_message.created_at).toLocaleString() }}
                                    </span>
                                </div>
                                <div class="ml-4 text-xs text-gray-400 whitespace-nowrap" v-else>
                                    <span class="italic text-gray-400">
                                        No messages yet
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </li>
                </ul>
                <div v-else class="text-gray-400 italic text-center py-6">
                    No chats or groups yet.
                </div>
            </div>
        </div>
        <CreateGroupModal
            :showCreateModal="showCreateModal"
            @update:showCreateModal="showCreateModal = $event"
        />
    </AuthenticatedLayout>
</template>
